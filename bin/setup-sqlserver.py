#!/usr/bin/env python3
"""Installation locale autorisée de SQL Server ; à lancer dans un terminal interactif."""
import os
import secrets
import shutil
import subprocess
import sys
from pathlib import Path
from urllib.parse import quote

ROOT = Path(__file__).resolve().parent.parent
os.chdir(ROOT)
os.umask(0o077)


def run(args, **kwargs):
    return subprocess.run(args, check=True, **kwargs)


def private_write(path, content):
    with path.open('x') as stream:
        stream.write(content)
    path.chmod(0o600)


def main():
    if not sys.stdin.isatty():
        raise RuntimeError('Lancer cette commande depuis un terminal interactif pour sudo.')
    # Ne pas lancer pacman si le module compilé n'est plus disponible.
    module = ROOT / 'var/setup/pdo_sqlsrv.so'
    run(['php', '-d', f'extension={module}', '-r',
         'exit(extension_loaded("pdo_sqlsrv") ? 0 : 1);'])
    run(['sudo', '-v'])
    if not shutil.which('docker') or subprocess.run(
            ['docker', 'compose', 'version'], stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL).returncode:
        run(['sudo', 'pacman', '-S', '--needed', 'docker', 'docker-compose'])
    extension_dir = subprocess.check_output(['php-config', '--extension-dir'], text=True).strip()
    target = Path(extension_dir) / 'pdo_sqlsrv.so'
    if not target.exists():
        run(['sudo', 'install', '-m', '755', str(module), str(target)])
    elif target.read_bytes() != module.read_bytes():
        raise RuntimeError('Un autre pdo_sqlsrv.so existe déjà : aucun écrasement effectué.')
    if subprocess.run(['php', '-r', 'exit(extension_loaded("pdo_sqlsrv") ? 0 : 1);']).returncode:
        ini = Path('/etc/php/conf.d/99-art2rue-sqlserver.ini')
        if ini.exists():
            raise RuntimeError('Configuration PHP SQL Server existante à vérifier manuellement.')
        local_ini = ROOT / 'var/setup/99-art2rue-sqlserver.ini'
        if not local_ini.exists():
            private_write(local_ini, 'extension=pdo_sqlsrv\n')
        run(['sudo', 'install', '-m', '644', str(local_ini), str(ini)])
    run(['php', '--ri', 'pdo_sqlsrv'])
    run(['sudo', 'systemctl', 'start', 'docker'])

    secret_file = ROOT / '.env.sqlserver.local'
    if not secret_file.exists():
        private_write(secret_file, 'MSSQL_ACCEPT_EULA=Y\n'
                      f'MSSQL_SA_PASSWORD=Aa1!{secrets.token_hex(24)}\n'
                      f'ART2RUE_APP_PASSWORD=Aa1!{secrets.token_hex(24)}\n')
    secret_file.chmod(0o600)
    values = dict(line.split('=', 1) for line in secret_file.read_text().splitlines()
                  if line and not line.startswith('#') and '=' in line)
    if values.get('MSSQL_ACCEPT_EULA') != 'Y' or not values.get('ART2RUE_APP_PASSWORD'):
        raise RuntimeError('Configuration secrète existante incomplète : conservée sans modification.')
    password = values['ART2RUE_APP_PASSWORD']
    # Limiter les caractères avant insertion dans le SQL ; les valeurs générées respectent cela.
    if any(c not in 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!' for c in password):
        raise RuntimeError('Le mot de passe applicatif doit être alphanumérique avec ! autorisé.')
    compose = ['sudo', 'docker', 'compose', '--env-file', str(secret_file),
               '-f', 'compose.sqlserver.yaml']
    run(compose + ['config', '--quiet'])
    run(compose + ['up', '-d', '--wait', '--wait-timeout', '240'])
    sql = """SET NOCOUNT ON;
IF DB_ID(N'art2rue') IS NULL CREATE DATABASE [art2rue];
GO
USE [master];
IF SUSER_ID(N'art2rue_app') IS NULL
    CREATE LOGIN [art2rue_app] WITH PASSWORD = N'%s', CHECK_POLICY = ON;
GO
USE [art2rue];
IF USER_ID(N'art2rue_app') IS NULL
    CREATE USER [art2rue_app] FOR LOGIN [art2rue_app];
IF IS_ROLEMEMBER(N'db_datareader', N'art2rue_app') <> 1
    ALTER ROLE [db_datareader] ADD MEMBER [art2rue_app];
IF IS_ROLEMEMBER(N'db_datawriter', N'art2rue_app') <> 1
    ALTER ROLE [db_datawriter] ADD MEMBER [art2rue_app];
GRANT VIEW DEFINITION TO [art2rue_app];
GO
""" % password
    # Le secret passe sur stdin, sans argument de commande ni affichage du SQL.
    result = subprocess.run(compose + ['exec', '-T', 'sqlserver', 'sh', '-c',
                            'export SQLCMDPASSWORD="$MSSQL_SA_PASSWORD"; '
                            'exec /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -C -b'],
                            input=sql, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    if result.returncode:
        raise RuntimeError('Initialisation SQL refusée ; sortie masquée pour protéger les secrets.')

    # Connexion réelle avec le compte applicatif AVANT de changer Symfony.
    probe = ('try { $pdo = new PDO("sqlsrv:Server=127.0.0.1,1433;Database=art2rue;'
             'Encrypt=yes;TrustServerCertificate=yes;LoginTimeout=5", '
             '"art2rue_app", getenv("ART2RUE_APP_PASSWORD")); '
             'echo "SQL Server : ", $pdo->query("SELECT DB_NAME()")->fetchColumn(), PHP_EOL; '
             '} catch (PDOException $e) { fwrite(STDERR, "Connexion refusée : " . '
             '$e->getCode() . PHP_EOL); exit(1); }')
    run(['php', '-r', probe], env={**os.environ, 'ART2RUE_APP_PASSWORD': password})

    local = ROOT / '.env.local'
    backup = ROOT / 'var/setup/env.local.before-sqlserver'
    if local.exists() and not backup.exists():
        private_write(backup, local.read_text())
    # Ne modifier que DATABASE_URL et conserver les autres variables.
    lines = local.read_text().splitlines() if local.exists() else []
    lines = [line for line in lines if not line.startswith('DATABASE_URL=')]
    lines.append(f'DATABASE_URL="pdo-sqlsrv://art2rue_app:{quote(password, safe="")}@127.0.0.1:1433/art2rue?serverVersion=16"')
    if local.exists():
        local.chmod(0o600)
    local.write_text('\n'.join(lines) + '\n')
    local.chmod(0o600)
    config = ROOT / 'config/packages/dev/art2rue_sqlserver.yaml'
    expected = ("doctrine:\n    dbal:\n        options:\n"
                "            Encrypt: 'yes'\n            TrustServerCertificate: 'yes'\n")
    config.parent.mkdir(parents=True, exist_ok=True)
    if config.exists() and config.read_text() != expected:
        raise RuntimeError('Configuration Doctrine existante différente : conservée.')
    if not config.exists():
        private_write(config, expected)
    run(['php', 'bin/console', 'cache:clear'])
    run(['php', 'bin/console', 'lint:container'])
    run(['php', 'bin/console', 'dbal:run-sql', 'SELECT DB_NAME() AS database_name'])
    print('Connexion Symfony / SQL Server validée. Aucune migration exécutée.')
    print('Compte applicatif limité à lecture/écriture ; les droits de migration seront attribués séparément.')
    print('Redémarrer le serveur PHP déjà ouvert pour charger le nouveau module.')


if __name__ == '__main__':
    try:
        main()
    except (RuntimeError, subprocess.CalledProcessError) as exc:
        print(f'Arrêt : {exc}', file=sys.stderr)
        sys.exit(1)

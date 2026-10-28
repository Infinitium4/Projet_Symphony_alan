# Art-2-Rue : préparation SQL Server

Le Compose SQL Server est indépendant du Compose PostgreSQL existant : toujours
utiliser `-f compose.sqlserver.yaml`. Aucun volume existant n'est remplacé.

## Étapes soumises à autorisation

1. Installer Docker et Compose sur Arch : `sudo pacman -S --needed docker docker-compose`.
2. Démarrer le service : `sudo systemctl start docker` (sans activation au démarrage).
3. Installer le module `pdo_sqlsrv` compilé pour le PHP courant dans le répertoire
   retourné par `php-config --extension-dir`, puis créer un fichier dédié dans
   `/etc/php/conf.d/` contenant `extension=pdo_sqlsrv`. Redémarrer le serveur PHP.
4. Accepter les conditions SQL Server Developer avant le premier démarrage.

Les en-têtes communs `source/shared` doivent être copiés dans le dossier `shared`
du module lors d'une compilation depuis les sources Microsoft. Leur absence
provoque `Cannot find PDO_SQLSRV headers` pendant `./configure`.

## Après autorisation

Un script regroupe ces étapes avec génération locale des secrets et vérification
de la connexion, sans lancer de migration. Depuis un terminal interactif :

```bash
python3 bin/setup-sqlserver.py
```

Il demande le mot de passe sudo dans le terminal et la confirmation pacman.
Le module compilé est conservé dans `var/setup/pdo_sqlsrv.so` (ignoré par Git).
Le compte applicatif reçoit uniquement les droits de lecture/écriture ; les droits
pour les futures migrations seront configurés séparément. `.env.local` est
sauvegardé avant modification dans `var/setup/env.local.before-sqlserver`.

Procédure manuelle équivalente :

Créer `.env.sqlserver.local` à partir de `.env.sqlserver.example`, avec droits
`600`, et renseigner un mot de passe fort sans le transmettre dans le chat.
Ce fichier est déjà couvert par la règle Git `.env.*.local`.

```bash
sudo docker compose --env-file .env.sqlserver.local -f compose.sqlserver.yaml up -d
sudo docker compose --env-file .env.sqlserver.local -f compose.sqlserver.yaml ps
```

Créer ensuite une base `art2rue` et un compte applicatif distinct de `sa`, après
vérification de leur absence. Configurer `DATABASE_URL` dans `.env.local` avec le
schéma `pdo-sqlsrv` montré dans l'exemple. Conserver la base SQLite existante.

ODBC 18 chiffre les connexions par défaut. Le certificat autosigné du conteneur
nécessite en développement l'option DBAL `options: {TrustServerCertificate: 'yes'}`.
Cette exception doit rester dans une configuration de développement, pas en production.
Le pilote DBAL installé transmet ces options textuelles au DSN PDO ; ajouter un
simple paramètre `TrustServerCertificate` à l'URL ne suffit pas avec cette version.

## Contrôles avant création des entités

```bash
php --ri pdo_sqlsrv
php bin/console dbal:run-sql 'SELECT DB_NAME() AS database_name, @@VERSION AS server_version'
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
php bin/console lint:container
```

`doctrine:migrations:status` peut demander une initialisation du stockage des
migrations sur une base neuve. Aucune migration ni initialisation n'a été lancée
sur SQL Server pendant l'audit.

La création des entités, des formulaires de connexion/inscription et des CRUD
reste conditionnée à une connexion SQL Server validée et à la clarification des
relations UML et des droits de gestion des œuvres, artistes, lieux et techniques.

## État actuel : base de données uniquement

La migration initiale est appliquée et les tables ont été conservées. Le compte
applicatif dispose de `VIEW DEFINITION` pour l’inspection Doctrine, en complément
de ses droits de lecture/écriture. L’interface et l’authentification web ajoutées
ont été retirées à la demande de l’utilisateur. Voir [l’état actuel](application.md).

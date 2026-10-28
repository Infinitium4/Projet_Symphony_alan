#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"

if [[ -f composer.json ]]; then
    echo "Un projet Composer existe déjà : exécute composer install."
    exit 1
fi

# Installation des outils et pilotes de bases de données sur Arch Linux.
sudo pacman -S --needed php php-intl php-sqlite php-pgsql composer unzip

# Arch fournit plusieurs extensions désactivées par défaut.
# Ne pas charger deux fois celles qui sont déjà actives.
extension_dir="$(php -r 'echo ini_get("extension_dir");')"
extension_config="$(mktemp)"
trap 'rm -f -- "$extension_config"' EXIT
for extension in curl iconv intl pdo_mysql pdo_pgsql pdo_sqlite sqlite3 zip; do
    if [[ -f "$extension_dir/$extension.so" ]] && ! php -r "exit(extension_loaded('$extension') ? 0 : 1);"; then
        printf 'extension=%s\n' "$extension" >> "$extension_config"
    fi
done
if [[ -s "$extension_config" ]]; then
    sudo install -m 644 "$extension_config" /etc/php/conf.d/99-symfony.ini
fi

# Créer le squelette à part pour conserver les fichiers déjà présents.
if [[ -e .symfony-install ]]; then
    echo "Le dossier .symfony-install existe déjà. Vérifie son contenu avant de relancer."
    exit 1
fi
composer create-project 'symfony/skeleton:7.4.*' .symfony-install --no-interaction
(
    cd .symfony-install
    composer require webapp --no-interaction
    composer check-platform-reqs
    php bin/console about
    php bin/console lint:container
)

shopt -s dotglob nullglob
for source in .symfony-install/*; do
    destination="${source##*/}"
    if [[ -e "$destination" ]]; then
        echo "Conflit avec $destination : projet conservé dans .symfony-install."
        exit 1
    fi
done
mv -- .symfony-install/* .
rmdir .symfony-install
# Le cache et l'autoload doivent être reconstruits depuis le chemin définitif.
composer dump-autoload --no-interaction
php bin/console cache:clear
printf '\nSymfony est installé. Pour démarrer :\nphp -S 127.0.0.1:8000 -t public\n'

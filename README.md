# Art-2-Rue — Base de données Symfony / SQL Server

Le projet conserve uniquement la préparation de la base de données et le socle
Symfony existant. Le site, les CRUD web, les formulaires de connexion/inscription
et les espaces bénévole/administrateur ajoutés ont été retirés.

## Éléments conservés

- Entités `Artiste`, `Lieu`, `Oeuvre`, `Technique`, `User` et `Photo`.
- Relations Doctrine, validations et contraintes d’unicité.
- Migration initiale appliquée à SQL Server, sans suppression de données.
- Connexion `pdo_sqlsrv` à la base `art2rue`, configurée dans `.env.local`.
- Docker Compose SQL Server et script de préparation de l’environnement.
- Quatre requêtes de l’exercice dans `src/Service/ExerciseQueries.php`.

Aucune table, donnée ou photo stockée n’a été supprimée lors du retrait du site.
La page minimale `Hello world` préexistante a été rétablie.

## Vérifications

Depuis le dossier du projet :

```bash
cd ~/Projets/Projet_Alan_Symphony/Projet_Symphony_alan
php bin/console about
php bin/console dbal:run-sql 'SELECT DB_NAME() AS database_name'
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
php bin/phpunit
```

Pour démarrer le conteneur existant si nécessaire :

```bash
sudo systemctl start docker
sudo docker compose --env-file .env.sqlserver.local -f compose.sqlserver.yaml up -d
```

Les mots de passe restent dans les fichiers locaux ignorés par Git.
Le compte applicatif possède les droits de lecture/écriture et de consultation
des métadonnées ; il ne possède pas les droits de modification du schéma.

Le script `php bin/check-sqlserver.php` vérifie les relations et les requêtes
avec des données temporaires dans une transaction annulée à la fin.

## Documentation

- [État actuel : base de données uniquement](docs/application.md)
- [Configuration SQL Server](docs/sqlserver.md)
- [Historique de l’audit initial](docs/compte-rendu.md)

PHP 8.5.12, Symfony 7.4, Doctrine ORM 3.7 et SQL Server 2022 sont utilisés sur ce poste.

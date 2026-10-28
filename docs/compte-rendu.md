# Art-2-Rue — Compte rendu technique

> Ce document conserve l’historique de l’audit initial. Le périmètre a depuis
> été limité à la base de données : consulter [le guide de l’application](application.md) pour l’état actuel.

État vérifié le **28 octobre 2026** dans `Projet_Symphony_alan`.

## 1. Objectif

Préparer le projet scolaire Art-2-Rue sur Arch Linux et obtenir une connexion
Symfony / Doctrine à Microsoft SQL Server avant de développer les fonctionnalités métier.

**Résultat actuel : la connexion Symfony à la base SQL Server `art2rue` est validée.**
Le modèle métier, l'authentification et les CRUD restent à développer.

## 2. Environnement constaté

| Composant | Version ou état vérifié |
| --- | --- |
| PHP CLI | 8.5.12 |
| Symfony | 7.4.21 LTS |
| Composer | 2.10.3 |
| Doctrine ORM | 3.7.5 |
| DoctrineBundle | 3.3.3 |
| DoctrineMigrationsBundle | 4.0.1 |
| MakerBundle | 1.68.0 |
| Symfony Security | 7.4.21 |
| PHPUnit | 13.4.5 |
| unixODBC | 2.3.14 |
| Microsoft ODBC | 18.7.1.1 |
| Extension `pdo_sqlsrv` | 5.13.3, chargée dans PHP CLI |
| Docker Compose | 5.6.1 |
| Service Docker | Actif lors de la vérification |
| SQL Server | 2022 Developer Edition, version 16.0.4305.5 |

Les extensions PHP nécessaires ont été contrôlées : PDO, `pdo_mysql`, `pdo_pgsql`,
`pdo_sqlite`, `mbstring`, `intl`, `xml`, DOM, SimpleXML, `iconv`, `ctype`, `curl`,
OpenSSL et ZIP. `pdo_sqlsrv` a été ajouté ensuite.
Symfony CLI était absent lors de l'audit initial ; il n'est pas nécessaire au
démarrage avec `php -S`.

Les dépendances verrouillées du projet, outils de test compris, nécessitent
PHP 8.4.1 minimum, même si `composer.json` annonce encore PHP >= 8.2.

## 3. Réparations initiales du projet

- Reconstruction du cache Symfony qui référençait encore le dossier temporaire
  `.symfony-install` utilisé pendant l'installation.
- Ajout d'une reconstruction de l'autoload et du cache dans
  `installer-symfony.sh`, après déplacement vers le dossier définitif.
- Complément de `templates/base.html.twig` : viewport, blocs `title`,
  `stylesheets`, `javascripts`, `importmap` et `body`.
- Rétablissement du chargement des fichiers CSS/JavaScript via AssetMapper.
- Ajout de `public/favicon.svg` et de son lien dans le template.
- Ajout du nom et de la description du projet dans `composer.json`, puis
  synchronisation de `composer.lock`, sans changement de versions de paquets.
- Ajout de `tests/HomeControllerTest.php` : contrôle de la réponse de l'accueil,
  de son titre, du contenu, de l'importmap et de la présence de l'icône.

La route `/` était déjà présente dans `HomeController` lors de l'audit.
Les anciennes erreurs 404 sur cette route ont été distinguées des erreurs actuelles.

Une base SQLite locale avait d'abord été configurée pour débloquer le travail
sans serveur PostgreSQL. Ses transports Messenger avaient été préparés.
Cette configuration provisoire a ensuite été remplacée par SQL Server ; le
fichier SQLite n'a pas été supprimé.

## 4. Installation et configuration SQL Server

### Problème du pilote PHP

ODBC Microsoft 18 était installé, mais `pdo_sqlsrv` était absent des modules PHP.
Les sources ont été retrouvées dans `/tmp/msphpsql`.
La compilation précédente échouait avec `Cannot find PDO_SQLSRV headers` : les
en-têtes communs `source/shared` n'avaient pas été copiés dans le dossier attendu.

La compilation a été refaite dans un nouveau dossier temporaire avec ces fichiers.
Le module obtenu a été testé par chargement temporaire avec `php -d extension=…`,
puis conservé dans `var/setup/pdo_sqlsrv.so`, ignoré par Git.
La vérification finale confirme que PHP CLI charge désormais `pdo_sqlsrv` 5.13.3.

### Fichiers préparés

| Fichier | Utilité |
| --- | --- |
| `compose.sqlserver.yaml` | Conteneur SQL Server 2022 Developer, port local 1433, volume persistant et contrôle de santé |
| `.env.sqlserver.example` | Exemple de variables sans secret réel |
| `bin/setup-sqlserver.py` | Installation, préparation et test de connexion depuis un terminal interactif |
| `docs/sqlserver.md` | Procédure d'installation et commandes de contrôle |

Le Compose PostgreSQL d'origine a été conservé. Pour SQL Server, utiliser
explicitement `-f compose.sqlserver.yaml`.

Le script a été préparé après autorisation de l'installation système et de
l'utilisation de SQL Server Developer. Son lancement depuis l'agent était bloqué
par la demande de mot de passe sudo ; il doit être exécuté dans le terminal utilisateur.
Les contrôles ultérieurs constatent Docker actif, le module installé, les fichiers
locaux créés et une connexion réelle fonctionnelle.

### Configuration locale constatée

- `.env.local` utilise le schéma `pdo-sqlsrv`, l'hôte `127.0.0.1`, le port 1433
  et la base `art2rue`.
- Le compte applicatif prévu est `art2rue_app`, distinct de l'administrateur `sa`.
  Le script lui attribue les rôles SQL de lecture et d'écriture ; il ne lui
  attribue pas les droits de modification du schéma pour les migrations.
- Les secrets sont générés localement ; aucun mot de passe n'est reproduit ici.
- `.env.local` et `.env.sqlserver.local` sont ignorés par Git.
- Une sauvegarde de l'ancien `.env.local` existe dans
  `var/setup/env.local.before-sqlserver`.
- `config/packages/dev/art2rue_sqlserver.yaml` active `Encrypt: 'yes'` et
  `TrustServerCertificate: 'yes'` pour le certificat local du conteneur.
  Cette exception de certificat est limitée à l'environnement de développement.
- `.env` conserve l'URL PostgreSQL de la recette initiale ; `.env.local` la
  surcharge sur ce poste. Un autre poste doit donc configurer sa propre connexion.

## 5. Vérifications et limites

| Vérification | Résultat |
| --- | --- |
| `php bin/console about` | Symfony démarre correctement |
| `composer validate --strict` | Validé après synchronisation du verrouillage |
| `composer check-platform-reqs` | Exigences satisfaites lors de l'audit |
| `php bin/console lint:container` | Validé lors de l'audit |
| Lint Twig et YAML | Validés lors de l'audit ; Compose SQL Server également contrôlé en syntaxe YAML |
| Accueil et icône via HTTP | HTTP 200 lors des contrôles initiaux |
| `php bin/phpunit` | Vérification finale : 1 test, 7 assertions, succès |
| `SELECT DB_NAME()` depuis Doctrine | Retourne `art2rue` sur SQL Server |
| Requête de version SQL Server | Retourne 16.0.4305.5, Developer Edition |
| État des migrations | 0 disponible, 0 exécutée |
| `doctrine:schema:validate` | Mapping valide ; schéma pas encore synchronisé |

Le diagnostic détaillé de la validation du schéma demande la table
`messenger_messages` et son index, absents de la nouvelle base SQL Server.
Ils n'ont pas été créés pendant la rédaction de ce compte rendu.
Ce résultat ne signifie pas que la connexion échoue : les requêtes SQL de lecture
fonctionnent bien. La préparation des tables et des droits DDL reste à organiser.

Un avertissement non bloquant de Symfony Flex 2.11.0 avec PHP 8.5 a également été
observé pour certaines invocations de Composer. La recherche de mise à jour
n'avait trouvé aucune version corrective disponible au moment de l'audit.

Aucune migration destructive, suppression de fichier ou réinitialisation de base
n'a été effectuée dans le cadre de ces interventions.

## 6. Ce qui reste à réaliser

Après validation du modèle UML et des permissions :

1. Préparer les droits nécessaires aux migrations et les tables techniques.
2. Créer les entités `Artiste`, `Lieu`, `Oeuvre`, `Technique`, `User` et `Photo`.
3. Implémenter les contraintes d'unicité et les relations, notamment les artistes
   multiples, les techniques, les œuvres recouvertes et l'auteur bénévole des photos.
4. Générer l'utilisateur, l'inscription et la connexion avec MakerBundle.
5. Garantir le hachage des mots de passe et empêcher l'auto-attribution de
   `ROLE_BENEVOLE` depuis l'inscription.
6. Créer les CRUD Twig / Symfony Forms et contrôler les rôles et la propriété des photos.
7. Vérifier les migrations proposées avant toute exécution, puis tester les parcours.

À ce jour, aucune page de connexion/inscription ni aucun CRUD métier n'est créé.
La présence de Security et MakerBundle ne constitue pas une authentification terminée.

## 7. Commandes utiles

Toujours se placer dans le dossier du projet, et non dans son parent :

```bash
cd ~/Projets/Projet_Alan_Symphony/Projet_Symphony_alan
php -S 127.0.0.1:8000 -t public
```

Ouvrir <http://127.0.0.1:8000/>. Si le port est déjà utilisé, réutiliser le serveur
existant ou le redémarrer depuis son terminal. Un processus PHP démarré avant
l'installation du module doit être redémarré pour charger celui-ci.

Contrôles en lecture :

```bash
php --ri pdo_sqlsrv
php bin/console dbal:run-sql 'SELECT DB_NAME() AS database_name'
php bin/console debug:router
php bin/console doctrine:migrations:status
php bin/console doctrine:schema:validate -vv
php bin/phpunit
sudo docker compose --env-file .env.sqlserver.local -f compose.sqlserver.yaml ps
```

La dernière commande demande sudo. Ne pas publier la sortie de commandes qui
affichent les variables d'environnement ou une configuration contenant des secrets.

## Références

- [Procédure locale SQL Server](sqlserver.md)
- [Matrice de compatibilité Microsoft PHP / SQL Server](https://learn.microsoft.com/en-us/sql/connect/php/microsoft-php-drivers-for-sql-server-support-matrix)
- [Déploiement SQL Server en conteneur](https://learn.microsoft.com/sql/linux/sql-server-linux-docker-container-deployment)

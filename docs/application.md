# Art-2-Rue — Base de données uniquement

## Correction du périmètre

À la demande de l’utilisateur, l’interface web ajoutée a été retirée. Les
contrôleurs CRUD, formulaires, pages Twig, espaces utilisateurs, authentification
web, service d’upload et commande de création d’administrateur ont été supprimés.
La page minimale et les fichiers Symfony préexistants ont été rétablis.

## Modèle conservé

| Entité | Contenu |
| --- | --- |
| Artiste | Pseudonyme unique, nom réel facultatif, biographie, nationalité facultative, œuvres |
| Lieu | Nom, adresse facultative, quartier, latitude et longitude decimal(10,7), œuvres |
| Oeuvre | Titre, date facultative, description, destruction par défaut faux, lieu obligatoire, artistes et techniques multiples, œuvre recouverte unique facultative, photos |
| Technique | Nom unique, œuvres |
| User | Email unique, mot de passe, nom, prénom, rôles, photos |
| Photo | Date de prise de vue, nom du fichier, état facultatif, œuvre et auteur obligatoires |

Les attributs Doctrine définissent les colonnes, index et clés étrangères.
Les contraintes Symfony complètent la validation des objets, notamment la présence
d’au moins une technique. Le champ de mot de passe doit recevoir une valeur hachée
lors de futures écritures ; il n’existe plus de formulaire ou de commande le faisant.

La migration `src/Migrations/Version20261028153151.php` a déjà été appliquée.
Son retour arrière est volontairement interdit pour protéger les données.

## SQL Server et données

La base `art2rue`, ses tables, ses données, le volume Docker, la configuration
locale et les éventuels fichiers de photos ont été conservés. Aucune migration
inverse, suppression de table ou réinitialisation n’a été exécutée pendant le retrait.

La connexion reste dans `.env.local`, les secrets Docker dans `.env.sqlserver.local`.
Ces fichiers ne doivent pas être publiés. Les instructions de démarrage et de
contrôle figurent dans [le README](../README.md) et [la procédure SQL Server](sqlserver.md).

## Requêtes de l’exercice

`src/Service/ExerciseQueries.php` conserve les quatre requêtes QueryBuilder :

1. Adresses et quartiers des lieux, triés par quartier.
2. Œuvres postérieures au 1er janvier 2023 et pseudonymes de leurs artistes.
3. Nombre d’œuvres par technique, y compris les techniques inutilisées.
4. Photographes des œuvres de C215 dans le quartier Centre.

`php bin/check-sqlserver.php` contrôle les écritures, les relations et ces requêtes
sur SQL Server dans une transaction annulée à la fin. Il ne conserve pas de données
exemples. Les tests fonctionnels propres au site retiré ont été supprimés ; le test
préexistant de la page minimale a été rétabli.

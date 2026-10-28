<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261028153151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création initiale Art-2-Rue : entités, relations et transport Messenger.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artiste (id INT IDENTITY NOT NULL, pseudonyme NVARCHAR(255) NOT NULL, nom_reel NVARCHAR(255), bio VARCHAR(MAX) NOT NULL, nationalite NVARCHAR(100), PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C07354F1FE3BDAF ON artiste (pseudonyme) WHERE pseudonyme IS NOT NULL');
        $this->addSql('CREATE TABLE lieu (id INT IDENTITY NOT NULL, nom NVARCHAR(255) NOT NULL, adresse NVARCHAR(255), quartier NVARCHAR(100) NOT NULL, latitude NUMERIC(10, 7) NOT NULL, longitude NUMERIC(10, 7) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE oeuvre (id INT IDENTITY NOT NULL, titre NVARCHAR(255) NOT NULL, date_creation DATE, description VARCHAR(MAX) NOT NULL, est_detruite BIT NOT NULL, lieu_id INT NOT NULL, oeuvre_recouverte_id INT, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_35FE2EFEEAFC6390 ON oeuvre (oeuvre_recouverte_id) WHERE oeuvre_recouverte_id IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_35FE2EFE6AB213CC ON oeuvre (lieu_id)');
        $this->addSql('ALTER TABLE oeuvre ADD DEFAULT 0 FOR est_detruite');
        $this->addSql('CREATE TABLE oeuvre_artiste (oeuvre_id INT NOT NULL, artiste_id INT NOT NULL, PRIMARY KEY (oeuvre_id, artiste_id))');
        $this->addSql('CREATE INDEX IDX_DB75DF1488194DE8 ON oeuvre_artiste (oeuvre_id)');
        $this->addSql('CREATE INDEX IDX_DB75DF1421D25844 ON oeuvre_artiste (artiste_id)');
        $this->addSql('CREATE TABLE oeuvre_technique (oeuvre_id INT NOT NULL, technique_id INT NOT NULL, PRIMARY KEY (oeuvre_id, technique_id))');
        $this->addSql('CREATE INDEX IDX_CEAB50CA88194DE8 ON oeuvre_technique (oeuvre_id)');
        $this->addSql('CREATE INDEX IDX_CEAB50CA1F8ACB26 ON oeuvre_technique (technique_id)');
        $this->addSql('CREATE TABLE photo (id INT IDENTITY NOT NULL, date_prise_vue DATETIME2(6) NOT NULL, nom_fichier NVARCHAR(255) NOT NULL, etat_oeuvre VARCHAR(MAX), oeuvre_id INT NOT NULL, auteur_id INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_14B7841888194DE8 ON photo (oeuvre_id)');
        $this->addSql('CREATE INDEX IDX_14B7841860BB6FE6 ON photo (auteur_id)');
        $this->addSql('CREATE TABLE technique (id INT IDENTITY NOT NULL, nom NVARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D73B98416C6E55B5 ON technique (nom) WHERE nom IS NOT NULL');
        $this->addSql('CREATE TABLE [user] (id INT IDENTITY NOT NULL, email NVARCHAR(180) NOT NULL, roles VARCHAR(MAX) NOT NULL, password NVARCHAR(255) NOT NULL, nom NVARCHAR(255) NOT NULL, prenom NVARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON [user] (email) WHERE email IS NOT NULL');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT IDENTITY NOT NULL, body VARCHAR(MAX) NOT NULL, headers VARCHAR(MAX) NOT NULL, queue_name NVARCHAR(190) NOT NULL, created_at DATETIME2(6) NOT NULL, available_at DATETIME2(6) NOT NULL, delivered_at DATETIME2(6), PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFE6AB213CC FOREIGN KEY (lieu_id) REFERENCES lieu (id)');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFEEAFC6390 FOREIGN KEY (oeuvre_recouverte_id) REFERENCES oeuvre (id) ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE oeuvre_artiste ADD CONSTRAINT FK_DB75DF1488194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre_artiste ADD CONSTRAINT FK_DB75DF1421D25844 FOREIGN KEY (artiste_id) REFERENCES artiste (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre_technique ADD CONSTRAINT FK_CEAB50CA88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oeuvre_technique ADD CONSTRAINT FK_CEAB50CA1F8ACB26 FOREIGN KEY (technique_id) REFERENCES technique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841888194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B7841860BB6FE6 FOREIGN KEY (auteur_id) REFERENCES [user] (id)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Retour arrière interdit : conserver les données Art-2-Rue.');
    }
}

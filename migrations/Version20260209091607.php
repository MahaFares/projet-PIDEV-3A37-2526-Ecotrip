<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209091607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, duration_minutes INT NOT NULL, location VARCHAR(150) NOT NULL, max_participants INT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, category_id INT NOT NULL, guide_id INT DEFAULT NULL, INDEX IDX_AC74095A12469DE2 (category_id), INDEX IDX_AC74095AD7ED1D4B (guide_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE activity_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE activity_schedule (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, available_spots INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_FA32A1F581C06096 (activity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie_hebergement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chambre (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, capacite INT NOT NULL, prix_par_nuit DOUBLE PRECISION NOT NULL, description LONGTEXT DEFAULT NULL, disponible TINYINT NOT NULL, hebergement_id INT NOT NULL, INDEX IDX_C509E4FF23BB0F66 (hebergement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guide (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(150) NOT NULL, phone VARCHAR(30) NOT NULL, bio LONGTEXT DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_CA9EC735E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hebergement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, nb_etoiles INT NOT NULL, image_principale VARCHAR(500) DEFAULT NULL, label_eco VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, actif TINYINT NOT NULL, categorie_id INT NOT NULL, propietaire_id INT DEFAULT NULL, INDEX IDX_4852DD9CBCF5E72D (categorie_id), INDEX IDX_4852DD9C42546783 (propietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hebergement_equipement (hebergement_id INT NOT NULL, equipement_id INT NOT NULL, INDEX IDX_4C1E14923BB0F66 (hebergement_id), INDEX IDX_4C1E149806F0F5C (equipement_id), PRIMARY KEY (hebergement_id, equipement_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, depart VARCHAR(255) NOT NULL, arrivee VARCHAR(255) NOT NULL, date_depart DATETIME NOT NULL, distancekm DOUBLE PRECISION NOT NULL, place_disponible INT NOT NULL, transport_id INT NOT NULL, INDEX IDX_2B5BA98C9909C13F (transport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transport (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(100) NOT NULL, capacite INT NOT NULL, emissionco2 DOUBLE PRECISION NOT NULL, prixparpersonne NUMERIC(10, 2) NOT NULL, disponible TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095A12469DE2 FOREIGN KEY (category_id) REFERENCES activity_category (id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AD7ED1D4B FOREIGN KEY (guide_id) REFERENCES guide (id)');
        $this->addSql('ALTER TABLE activity_schedule ADD CONSTRAINT FK_FA32A1F581C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT FK_C509E4FF23BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_hebergement (id)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9C42546783 FOREIGN KEY (propietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hebergement_equipement ADD CONSTRAINT FK_4C1E14923BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement_equipement ADD CONSTRAINT FK_4C1E149806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98C9909C13F FOREIGN KEY (transport_id) REFERENCES transport (id)');
        $this->addSql('ALTER TABLE categorie CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY `fk_commande_produit`');
        $this->addSql('ALTER TABLE commandes CHANGE id_user id_user INT DEFAULT NULL, CHANGE id_produit id_produit INT DEFAULT NULL, CHANGE type_commande type_commande VARCHAR(20) NOT NULL, CHANGE date_commande date_commande DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CF7384557 FOREIGN KEY (id_produit) REFERENCES produits (id_produit)');
        $this->addSql('ALTER TABLE commandes RENAME INDEX fk_commande_produit TO IDX_35D4282CF7384557');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY `fk_paiement_commande`');
        $this->addSql('ALTER TABLE paiement CHANGE id_commande id_commande INT DEFAULT NULL, CHANGE date_paiement date_paiement DATETIME NOT NULL, CHANGE reference_paiement reference_paiement VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E3E314AE8 FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)');
        $this->addSql('ALTER TABLE paiement RENAME INDEX fk_paiement_commande TO IDX_B1DC7A1E3E314AE8');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY `fk_produit_categorie`');
        $this->addSql('ALTER TABLE produits CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE id_categorie id_categorie INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT FK_BE2DDF8CC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)');
        $this->addSql('ALTER TABLE produits RENAME INDEX fk_produit_categorie TO IDX_BE2DDF8CC9486A13');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A12469DE2');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AD7ED1D4B');
        $this->addSql('ALTER TABLE activity_schedule DROP FOREIGN KEY FK_FA32A1F581C06096');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY FK_C509E4FF23BB0F66');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9CBCF5E72D');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9C42546783');
        $this->addSql('ALTER TABLE hebergement_equipement DROP FOREIGN KEY FK_4C1E14923BB0F66');
        $this->addSql('ALTER TABLE hebergement_equipement DROP FOREIGN KEY FK_4C1E149806F0F5C');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98C9909C13F');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_category');
        $this->addSql('DROP TABLE activity_schedule');
        $this->addSql('DROP TABLE categorie_hebergement');
        $this->addSql('DROP TABLE chambre');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE guide');
        $this->addSql('DROP TABLE hebergement');
        $this->addSql('DROP TABLE hebergement_equipement');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE transport');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE categorie CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CF7384557');
        $this->addSql('ALTER TABLE commandes CHANGE id_user id_user INT NOT NULL, CHANGE type_commande type_commande ENUM(\'achat\', \'location\') NOT NULL, CHANGE date_commande date_commande DATETIME DEFAULT \'current_timestamp()\', CHANGE id_produit id_produit INT NOT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT `fk_commande_produit` FOREIGN KEY (id_produit) REFERENCES produits (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commandes RENAME INDEX idx_35d4282cf7384557 TO fk_commande_produit');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E3E314AE8');
        $this->addSql('ALTER TABLE paiement CHANGE date_paiement date_paiement DATETIME DEFAULT \'current_timestamp()\', CHANGE reference_paiement reference_paiement VARCHAR(100) DEFAULT \'NULL\', CHANGE id_commande id_commande INT NOT NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT `fk_paiement_commande` FOREIGN KEY (id_commande) REFERENCES commandes (id_commande) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiement RENAME INDEX idx_b1dc7a1e3e314ae8 TO fk_paiement_commande');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY FK_BE2DDF8CC9486A13');
        $this->addSql('ALTER TABLE produits CHANGE description description TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE id_categorie id_categorie INT NOT NULL');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT `fk_produit_categorie` FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produits RENAME INDEX idx_be2ddf8cc9486a13 TO fk_produit_categorie');
    }
}

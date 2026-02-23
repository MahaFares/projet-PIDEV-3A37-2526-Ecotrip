<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208143528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, duration_minutes INT NOT NULL, location VARCHAR(150) NOT NULL, max_participants INT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, category_id INT NOT NULL, guide_id INT DEFAULT NULL, INDEX IDX_AC74095A12469DE2 (category_id), INDEX IDX_AC74095AD7ED1D4B (guide_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE activity_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE activity_schedule (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, available_spots INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_FA32A1F581C06096 (activity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id_categorie)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie_hebergement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE chambre (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, capacite INT NOT NULL, prix_par_nuit DOUBLE PRECISION NOT NULL, description LONGTEXT DEFAULT NULL, disponible TINYINT NOT NULL, hebergement_id INT NOT NULL, INDEX IDX_C509E4FF23BB0F66 (hebergement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commandes (id_commande INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, type_commande VARCHAR(20) NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, date_commande DATETIME NOT NULL, statut_commande VARCHAR(50) NOT NULL, id_produit INT DEFAULT NULL, INDEX IDX_35D4282CF7384557 (id_produit), PRIMARY KEY (id_commande)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE guide (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(150) NOT NULL, phone VARCHAR(30) NOT NULL, bio LONGTEXT DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_CA9EC735E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE hebergement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, nb_etoiles INT NOT NULL, image_principale VARCHAR(500) DEFAULT NULL, label_eco VARCHAR(100) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, actif TINYINT NOT NULL, categorie_id INT NOT NULL, propietaire_id INT DEFAULT NULL, INDEX IDX_4852DD9CBCF5E72D (categorie_id), INDEX IDX_4852DD9C42546783 (propietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE hebergement_equipement (hebergement_id INT NOT NULL, equipement_id INT NOT NULL, INDEX IDX_4C1E14923BB0F66 (hebergement_id), INDEX IDX_4C1E149806F0F5C (equipement_id), PRIMARY KEY (hebergement_id, equipement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE paiement (id_paiement INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, methode_paiement VARCHAR(50) NOT NULL, statut_paiement VARCHAR(50) NOT NULL, date_paiement DATETIME NOT NULL, reference_paiement VARCHAR(100) DEFAULT NULL, id_commande INT DEFAULT NULL, INDEX IDX_B1DC7A1E3E314AE8 (id_commande), PRIMARY KEY (id_paiement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE produits (id_produit INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, id_categorie INT DEFAULT NULL, INDEX IDX_BE2DDF8CC9486A13 (id_categorie), PRIMARY KEY (id_produit)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095A12469DE2 FOREIGN KEY (category_id) REFERENCES activity_category (id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AD7ED1D4B FOREIGN KEY (guide_id) REFERENCES guide (id)');
        $this->addSql('ALTER TABLE activity_schedule ADD CONSTRAINT FK_FA32A1F581C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
        $this->addSql('ALTER TABLE chambre ADD CONSTRAINT FK_C509E4FF23BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CF7384557 FOREIGN KEY (id_produit) REFERENCES produits (id_produit)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_hebergement (id)');
        $this->addSql('ALTER TABLE hebergement ADD CONSTRAINT FK_4852DD9C42546783 FOREIGN KEY (propietaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE hebergement_equipement ADD CONSTRAINT FK_4C1E14923BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement_equipement ADD CONSTRAINT FK_4C1E149806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E3E314AE8 FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)');
        $this->addSql('ALTER TABLE produits ADD CONSTRAINT FK_BE2DDF8CC9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A12469DE2');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AD7ED1D4B');
        $this->addSql('ALTER TABLE activity_schedule DROP FOREIGN KEY FK_FA32A1F581C06096');
        $this->addSql('ALTER TABLE chambre DROP FOREIGN KEY FK_C509E4FF23BB0F66');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CF7384557');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9CBCF5E72D');
        $this->addSql('ALTER TABLE hebergement DROP FOREIGN KEY FK_4852DD9C42546783');
        $this->addSql('ALTER TABLE hebergement_equipement DROP FOREIGN KEY FK_4C1E14923BB0F66');
        $this->addSql('ALTER TABLE hebergement_equipement DROP FOREIGN KEY FK_4C1E149806F0F5C');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E3E314AE8');
        $this->addSql('ALTER TABLE produits DROP FOREIGN KEY FK_BE2DDF8CC9486A13');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_category');
        $this->addSql('DROP TABLE activity_schedule');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE categorie_hebergement');
        $this->addSql('DROP TABLE chambre');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE guide');
        $this->addSql('DROP TABLE hebergement');
        $this->addSql('DROP TABLE hebergement_equipement');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE produits');
    }
}

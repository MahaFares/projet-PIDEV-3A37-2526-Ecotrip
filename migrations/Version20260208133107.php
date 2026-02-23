<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208133107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, depart VARCHAR(255) NOT NULL, arrivee VARCHAR(255) NOT NULL, date_depart DATETIME NOT NULL, distancekm DOUBLE PRECISION NOT NULL, place_disponible INT NOT NULL, transport_id INT NOT NULL, INDEX IDX_2B5BA98C9909C13F (transport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE transport (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(100) NOT NULL, capacite INT NOT NULL, emissionco2 DOUBLE PRECISION NOT NULL, prixparpersonne NUMERIC(10, 2) NOT NULL, disponible TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98C9909C13F FOREIGN KEY (transport_id) REFERENCES transport (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98C9909C13F');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE transport');
    }
}

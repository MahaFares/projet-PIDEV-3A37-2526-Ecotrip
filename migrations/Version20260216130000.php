<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TransportCategory, Chauffeur entities and Transport relations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE transport_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chauffeur (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, phone VARCHAR(30) NOT NULL, license_number VARCHAR(50) NOT NULL, experience INT NOT NULL, rating DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transport ADD category_id INT DEFAULT NULL, ADD chauffeur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_TRANSPORT_CATEGORY FOREIGN KEY (category_id) REFERENCES transport_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE transport ADD CONSTRAINT FK_TRANSPORT_CHAUFFEUR FOREIGN KEY (chauffeur_id) REFERENCES chauffeur (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_TRANSPORT_CATEGORY ON transport (category_id)');
        $this->addSql('CREATE INDEX IDX_TRANSPORT_CHAUFFEUR ON transport (chauffeur_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_TRANSPORT_CATEGORY');
        $this->addSql('ALTER TABLE transport DROP FOREIGN KEY FK_TRANSPORT_CHAUFFEUR');
        $this->addSql('DROP INDEX IDX_TRANSPORT_CATEGORY ON transport');
        $this->addSql('DROP INDEX IDX_TRANSPORT_CHAUFFEUR ON transport');
        $this->addSql('DROP TABLE transport_category');
        $this->addSql('DROP TABLE chauffeur');
        $this->addSql('ALTER TABLE transport DROP category_id, DROP chauffeur_id');
    }
}

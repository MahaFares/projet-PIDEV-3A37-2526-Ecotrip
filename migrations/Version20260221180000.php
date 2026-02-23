<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User: add face_descriptor (JSON) for Face-API.js facial recognition';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD face_descriptor JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP face_descriptor');
    }
}

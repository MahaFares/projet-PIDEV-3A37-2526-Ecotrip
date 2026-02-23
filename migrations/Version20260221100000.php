<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reservation: add date_from, date_to, number_of_persons, details.
 * Existing rows get date_from = created_at, number_of_persons = 1.
 */
final class Version20260221100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reservation: add date_from, date_to, number_of_persons, details columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD date_from DATE DEFAULT NULL, ADD date_to DATE DEFAULT NULL, ADD number_of_persons INT NOT NULL DEFAULT 1, ADD details JSON DEFAULT NULL');
        $this->addSql('UPDATE reservation SET date_from = DATE(created_at) WHERE date_from IS NULL');
        $this->addSql('ALTER TABLE reservation MODIFY date_from DATE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP date_from, DROP date_to, DROP number_of_persons, DROP details');
    }
}

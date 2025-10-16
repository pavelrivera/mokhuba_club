<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración para agregar el campo membership_end_date a la tabla users
 */
final class Version20251012000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega el campo membership_end_date a la tabla users para rastrear la fecha de expiración de membresías';
    }

    public function up(Schema $schema): void
    {
        // Agregar campo membership_end_date a la tabla users
        $this->addSql('ALTER TABLE users ADD membership_end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN users.membership_end_date IS \'Fecha de expiración de la membresía\'');
    }

    public function down(Schema $schema): void
    {
        // Eliminar campo membership_end_date de la tabla users
        $this->addSql('ALTER TABLE users DROP membership_end_date');
    }
}
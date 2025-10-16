<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251014011700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Eliminar columna duplicada invitee_email de la tabla invitations';
    }

    public function up(Schema $schema): void
    {
        // Eliminar la columna invitee_email si existe (es redundante con email)
        $this->addSql("
            DO $$ 
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'invitee_email'
                ) THEN
                    ALTER TABLE invitations DROP COLUMN invitee_email;
                END IF;
            END $$;
        ");
    }

    public function down(Schema $schema): void
    {
        // No es necesario revertir
    }
}
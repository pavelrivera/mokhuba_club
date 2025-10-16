<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251014000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crear/actualizar tabla invitations con estructura completa para sistema de invitaciones';
    }

    public function up(Schema $schema): void
    {
        // Crear tabla invitations si no existe con estructura completa
        $this->addSql("
            CREATE TABLE IF NOT EXISTS invitations (
                id SERIAL PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                token VARCHAR(64) NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                accepted_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                invited_by_user_id INT NULL,
                invitee_name VARCHAR(255) NULL,
                invitee_phone VARCHAR(50) NULL,
                message TEXT NULL
            )
        ");

        // Agregar columnas faltantes si la tabla ya existe
        
        // Columna status
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'status'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN status VARCHAR(20) DEFAULT 'pending';
                END IF;
            END $$;
        ");

        // Columna accepted_at
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'accepted_at'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN accepted_at TIMESTAMP(0) WITHOUT TIME ZONE NULL;
                END IF;
            END $$;
        ");

        // Columna invited_by_user_id
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'invited_by_user_id'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN invited_by_user_id INT NULL;
                END IF;
            END $$;
        ");

        // Columna invitee_name
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'invitee_name'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN invitee_name VARCHAR(255) NULL;
                END IF;
            END $$;
        ");

        // Columna invitee_phone
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'invitee_phone'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN invitee_phone VARCHAR(50) NULL;
                END IF;
            END $$;
        ");

        // Columna message
        $this->addSql("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'message'
                ) THEN
                    ALTER TABLE invitations ADD COLUMN message TEXT NULL;
                END IF;
            END $$;
        ");

        // Migrar datos de columnas antiguas si existen
        
        // Migrar de created_by_id a invited_by_user_id
        $this->addSql("
            DO $$ 
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'created_by_id'
                ) THEN
                    UPDATE invitations 
                    SET invited_by_user_id = created_by_id 
                    WHERE invited_by_user_id IS NULL AND created_by_id IS NOT NULL;
                END IF;
            END $$;
        ");

        // Migrar de name a invitee_name
        $this->addSql("
            DO $$ 
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'name'
                ) THEN
                    UPDATE invitations 
                    SET invitee_name = name 
                    WHERE invitee_name IS NULL AND name IS NOT NULL;
                END IF;
            END $$;
        ");

        // Migrar de phone a invitee_phone
        $this->addSql("
            DO $$ 
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = 'invitations' 
                    AND column_name = 'phone'
                ) THEN
                    UPDATE invitations 
                    SET invitee_phone = phone 
                    WHERE invitee_phone IS NULL AND phone IS NOT NULL;
                END IF;
            END $$;
        ");

        // Crear índices necesarios
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_invitations_token ON invitations (token)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_email ON invitations (email)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_status ON invitations (status)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_expires_at ON invitations (expires_at)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_accepted_at ON invitations (accepted_at)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_invited_by ON invitations (invited_by_user_id)");
    }

    public function down(Schema $schema): void
    {
        // Eliminar índices
        $this->addSql("DROP INDEX IF EXISTS uniq_invitations_token");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_email");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_status");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_expires_at");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_accepted_at");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_invited_by");
        
        // Eliminar tabla
        $this->addSql("DROP TABLE IF EXISTS invitations");
    }
}
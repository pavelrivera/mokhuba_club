<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración para crear la tabla de invitaciones
 */
final class Version20251013_CreateInvitationsTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla invitations para gestionar invitaciones de usuarios';
    }

    public function up(Schema $schema): void
    {
        // Crear tabla invitations
        $this->addSql('CREATE TABLE invitations (
            id SERIAL PRIMARY KEY,
            sender_id INTEGER NULL,
            invitee_email VARCHAR(255) NOT NULL,
            invitee_name VARCHAR(100) NOT NULL,
            invitee_phone VARCHAR(20) NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            accepted_at TIMESTAMP NULL,
            expires_at TIMESTAMP NOT NULL,
            created_user_id INTEGER NULL,
            message TEXT NULL,
            CONSTRAINT fk_invitations_sender FOREIGN KEY (sender_id) 
                REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_invitations_created_user FOREIGN KEY (created_user_id) 
                REFERENCES users(id) ON DELETE SET NULL
        )');

        // Índices para mejorar el rendimiento
        $this->addSql('CREATE INDEX idx_invitations_token ON invitations(token)');
        $this->addSql('CREATE INDEX idx_invitations_email ON invitations(invitee_email)');
        $this->addSql('CREATE INDEX idx_invitations_status ON invitations(status)');
        $this->addSql('CREATE INDEX idx_invitations_sender ON invitations(sender_id)');
        $this->addSql('CREATE INDEX idx_invitations_expires ON invitations(expires_at)');

        // Comentarios de tabla y columnas
        $this->addSql('COMMENT ON TABLE invitations IS \'Tabla de invitaciones enviadas por usuarios del club\'');
        $this->addSql('COMMENT ON COLUMN invitations.token IS \'Token único para validar la invitación (64 caracteres)\'');
        $this->addSql('COMMENT ON COLUMN invitations.status IS \'Estado: pending, accepted, expired, cancelled\'');
    }

    public function down(Schema $schema): void
    {
        // Eliminar tabla invitations
        $this->addSql('DROP TABLE IF EXISTS invitations CASCADE');
    }
}

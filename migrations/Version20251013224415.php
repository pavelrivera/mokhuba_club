<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251013224415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'PostgreSQL: crear tabla invitations si no existe + índices/unique (token), expiración y uso';
    }

    public function up(Schema $schema): void
    {
        // Crear tabla si no existe (PostgreSQL)
        $this->addSql("
            CREATE TABLE IF NOT EXISTS invitations (
                id SERIAL PRIMARY KEY,
                created_by_id INT NULL,
                name VARCHAR(180) NOT NULL,
                email VARCHAR(180) NOT NULL,
                phone VARCHAR(50) NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                used_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ");

        // Índices / restricciones idempotentes
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_invitations_token ON invitations (token)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_expires_at ON invitations (expires_at)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_used_at ON invitations (used_at)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_invitations_created_by ON invitations (created_by_id)");
    }

    public function down(Schema $schema): void
    {
        // Eliminamos solo los índices/tabla si existen (idempotente)
        $this->addSql("DROP INDEX IF EXISTS uniq_invitations_token");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_expires_at");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_used_at");
        $this->addSql("DROP INDEX IF EXISTS idx_invitations_created_by");
        $this->addSql("DROP TABLE IF EXISTS invitations");
    }
}

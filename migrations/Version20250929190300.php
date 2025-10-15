<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración para agregar campos faltantes detectados en auditoría
 */
final class Version20250929190300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campos stripe_customer_id, paid_at, processed_at';
    }

    public function up(Schema $schema): void
    {
        // PostgreSQL
        $this->addSql('ALTER TABLE users ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payments ADD paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE webhook_events ADD processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP stripe_customer_id');
        $this->addSql('ALTER TABLE payments DROP paid_at');
        $this->addSql('ALTER TABLE webhook_events DROP processed_at');
    }
}
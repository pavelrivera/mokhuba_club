<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración para agregar columnas faltantes en tabla subscriptions
 * Compatible con PostgreSQL
 */
final class Version20251013140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agregar columnas faltantes a la tabla subscriptions para que coincida con la entidad Subscription';
    }

    public function up(Schema $schema): void
    {
        // 1. Agregar columnas de fechas de inicio y fin
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // 2. Agregar columnas de período actual
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS current_period_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS current_period_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP');

        // 3. Agregar columnas de cancelación
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS cancel_at_period_end BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS canceled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // 4. Agregar columnas de período de prueba
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS trial_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS trial_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // 5. Agregar columnas de precio
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS price_amount INTEGER DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS price_currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');

        // 6. Agregar columnas adicionales
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS metadata TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS last_stripe_sync TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // 7. Actualizar registros existentes
        $this->addSql("
            UPDATE subscriptions 
            SET 
                start_date = created_at,
                end_date = created_at + INTERVAL '1 year',
                current_period_start = created_at,
                current_period_end = created_at + INTERVAL '1 year',
                price_amount = CASE 
                    WHEN membership_level = 'ruby' THEN 299
                    WHEN membership_level = 'gold' THEN 599
                    WHEN membership_level = 'platinum' THEN 999
                    ELSE 0
                END,
                price_currency = 'usd'
            WHERE start_date IS NULL
        ");

        // 8. Hacer NOT NULL las columnas que lo requieren (después de llenarlas)
        $this->addSql('ALTER TABLE subscriptions ALTER COLUMN current_period_start SET NOT NULL');
        $this->addSql('ALTER TABLE subscriptions ALTER COLUMN current_period_end SET NOT NULL');
        $this->addSql('ALTER TABLE subscriptions ALTER COLUMN price_amount SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revertir cambios
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS start_date');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS end_date');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS current_period_start');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS current_period_end');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS cancel_at_period_end');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS canceled_at');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS cancelled_at');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS trial_start');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS trial_end');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS price_amount');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS price_currency');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS metadata');
        $this->addSql('ALTER TABLE subscriptions DROP COLUMN IF EXISTS last_stripe_sync');
    }
}

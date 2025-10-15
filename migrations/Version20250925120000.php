<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration para sistema completo de pagos Mokhuba Club
 * Compatible con PHP 7.4.33 y PostgreSQL
 */
final class Version20250926120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crear sistema completo de pagos Mokhuba: suscripciones, pagos, webhooks';
    }

    public function up(Schema $schema): void
    {
        // =====================================================
        // TABLA: subscriptions
        // Suscripciones de membresías Mokhuba
        // =====================================================
        $this->addSql('CREATE TABLE subscriptions (
            id SERIAL NOT NULL,
            user_id INTEGER NOT NULL,
            stripe_subscription_id VARCHAR(255) DEFAULT NULL,
            stripe_customer_id VARCHAR(255) DEFAULT NULL,
            membership_level VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL,
            current_period_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            current_period_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            cancel_at_period_end BOOLEAN NOT NULL DEFAULT FALSE,
            canceled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            trial_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            trial_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            price_amount INTEGER NOT NULL,
            price_currency VARCHAR(3) NOT NULL DEFAULT \'USD\',
            metadata TEXT DEFAULT NULL,
            last_stripe_sync TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Índices para subscriptions
        $this->addSql('CREATE INDEX IDX_MOKHUBA_SUBSCRIPTIONS_USER ON subscriptions (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MOKHUBA_SUBSCRIPTIONS_STRIPE ON subscriptions (stripe_subscription_id)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_SUBSCRIPTIONS_STATUS ON subscriptions (status)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_SUBSCRIPTIONS_LEVEL ON subscriptions (membership_level)');

        // =====================================================
        // TABLA: payments 
        // Registro de todos los pagos realizados
        // =====================================================
        $this->addSql('CREATE TABLE payments (
            id SERIAL NOT NULL,
            user_id INTEGER NOT NULL,
            subscription_id INTEGER DEFAULT NULL,
            stripe_payment_intent_id VARCHAR(255) DEFAULT NULL,
            stripe_invoice_id VARCHAR(255) DEFAULT NULL,
            amount INTEGER NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT \'USD\',
            status VARCHAR(50) NOT NULL,
            payment_method_type VARCHAR(50) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            failure_reason TEXT DEFAULT NULL,
            receipt_email VARCHAR(255) DEFAULT NULL,
            receipt_url VARCHAR(500) DEFAULT NULL,
            refunded_amount INTEGER NOT NULL DEFAULT 0,
            refunded BOOLEAN NOT NULL DEFAULT FALSE,
            disputed BOOLEAN NOT NULL DEFAULT FALSE,
            reconciled BOOLEAN NOT NULL DEFAULT FALSE,
            metadata TEXT DEFAULT NULL,
            stripe_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            last_stripe_sync TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Índices para payments
        $this->addSql('CREATE INDEX IDX_MOKHUBA_PAYMENTS_USER ON payments (user_id)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_PAYMENTS_SUBSCRIPTION ON payments (subscription_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MOKHUBA_PAYMENTS_STRIPE_PI ON payments (stripe_payment_intent_id)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_PAYMENTS_STATUS ON payments (status)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_PAYMENTS_DATE ON payments (created_at)');

        // =====================================================
        // TABLA: webhook_events
        // Log de eventos webhook de Stripe para auditoría
        // =====================================================
        $this->addSql('CREATE TABLE webhook_events (
            id SERIAL NOT NULL,
            stripe_event_id VARCHAR(255) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            api_version VARCHAR(50) DEFAULT NULL,
            object_id VARCHAR(255) DEFAULT NULL,
            livemode BOOLEAN NOT NULL DEFAULT FALSE,
            processed BOOLEAN NOT NULL DEFAULT FALSE,
            processing_attempts INTEGER NOT NULL DEFAULT 0,
            last_processing_attempt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            processing_error TEXT DEFAULT NULL,
            raw_data TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        // Índices para webhook_events
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MOKHUBA_WEBHOOK_STRIPE_ID ON webhook_events (stripe_event_id)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_WEBHOOK_TYPE ON webhook_events (event_type)');
        $this->addSql('CREATE INDEX IDX_MOKHUBA_WEBHOOK_PROCESSED ON webhook_events (processed)');

        // =====================================================
        // FOREIGN KEYS
        // =====================================================
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_MOKHUBA_SUBSCRIPTIONS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_MOKHUBA_PAYMENTS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_MOKHUBA_PAYMENTS_SUBSCRIPTION FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Eliminar en orden correcto para evitar problemas de FK
        $this->addSql('DROP TABLE IF EXISTS webhook_events');
        $this->addSql('DROP TABLE IF EXISTS payments');
        $this->addSql('DROP TABLE IF EXISTS subscriptions');
    }
}
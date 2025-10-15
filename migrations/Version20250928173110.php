<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250928173110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE invoices_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE payment_methods_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE invoices (id INT NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, subtotal INT NOT NULL, tax_amount INT DEFAULT 0 NOT NULL, discount_amount INT DEFAULT 0 NOT NULL, total_amount INT NOT NULL, amount_due INT NOT NULL, amount_paid INT DEFAULT 0 NOT NULL, currency VARCHAR(3) DEFAULT \'USD\' NOT NULL, description TEXT DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) DEFAULT NULL, customer_address TEXT DEFAULT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, invoice_url VARCHAR(500) DEFAULT NULL, invoice_pdf VARCHAR(500) DEFAULT NULL, attempted_collection BOOLEAN DEFAULT false NOT NULL, collection_method VARCHAR(255) DEFAULT NULL, line_items TEXT DEFAULT NULL, metadata TEXT DEFAULT NULL, stripe_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_stripe_sync TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2F2F952DA68207 ON invoices (invoice_number)');
        $this->addSql('CREATE INDEX IDX_6A2F2F95A76ED395 ON invoices (user_id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F959A1887DC ON invoices (subscription_id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F954C3A3BB ON invoices (payment_id)');
        $this->addSql('CREATE TABLE payment_methods (id INT NOT NULL, user_id INT NOT NULL, stripe_payment_method_id VARCHAR(255) DEFAULT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, is_default BOOLEAN DEFAULT false NOT NULL, card_brand VARCHAR(50) DEFAULT NULL, card_last4 VARCHAR(4) DEFAULT NULL, card_exp_month INT DEFAULT NULL, card_exp_year INT DEFAULT NULL, card_funding VARCHAR(50) DEFAULT NULL, card_country VARCHAR(2) DEFAULT NULL, card_fingerprint VARCHAR(255) DEFAULT NULL, billing_name VARCHAR(255) DEFAULT NULL, billing_email VARCHAR(255) DEFAULT NULL, billing_phone VARCHAR(20) DEFAULT NULL, billing_address TEXT DEFAULT NULL, billing_city VARCHAR(100) DEFAULT NULL, billing_state VARCHAR(100) DEFAULT NULL, billing_postal_code VARCHAR(20) DEFAULT NULL, billing_country VARCHAR(2) DEFAULT NULL, bank_account_last4 VARCHAR(255) DEFAULT NULL, bank_name VARCHAR(255) DEFAULT NULL, bank_account_type VARCHAR(50) DEFAULT NULL, bank_routing_number VARCHAR(255) DEFAULT NULL, is_active BOOLEAN DEFAULT true NOT NULL, is_verified BOOLEAN DEFAULT false NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, metadata TEXT DEFAULT NULL, stripe_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, last_stripe_sync TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4FABF983A76ED395 ON payment_methods (user_id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F959A1887DC FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F954C3A3BB FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_methods ADD CONSTRAINT FK_4FABF983A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX idx_mokhuba_payments_date');
        $this->addSql('DROP INDEX idx_mokhuba_payments_status');
        $this->addSql('DROP INDEX uniq_mokhuba_payments_stripe_pi');
        $this->addSql('ALTER TABLE payments ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE payments ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE payments ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_mokhuba_payments_user RENAME TO IDX_65D29B32A76ED395');
        $this->addSql('ALTER INDEX idx_mokhuba_payments_subscription RENAME TO IDX_65D29B329A1887DC');
        $this->addSql('DROP INDEX idx_mokhuba_subscriptions_level');
        $this->addSql('DROP INDEX idx_mokhuba_subscriptions_status');
        $this->addSql('DROP INDEX uniq_mokhuba_subscriptions_stripe');
        $this->addSql('ALTER TABLE subscriptions ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE subscriptions ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE subscriptions ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_mokhuba_subscriptions_user RENAME TO IDX_4778A01A76ED395');
        $this->addSql('ALTER TABLE users ALTER id DROP DEFAULT');
        $this->addSql('DROP INDEX idx_mokhuba_webhook_processed');
        $this->addSql('DROP INDEX idx_mokhuba_webhook_type');
        $this->addSql('DROP INDEX uniq_mokhuba_webhook_stripe_id');
        $this->addSql('ALTER TABLE webhook_events ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE webhook_events ALTER created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE invoices_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE payment_methods_id_seq CASCADE');
        $this->addSql('ALTER TABLE invoices DROP CONSTRAINT FK_6A2F2F95A76ED395');
        $this->addSql('ALTER TABLE invoices DROP CONSTRAINT FK_6A2F2F959A1887DC');
        $this->addSql('ALTER TABLE invoices DROP CONSTRAINT FK_6A2F2F954C3A3BB');
        $this->addSql('ALTER TABLE payment_methods DROP CONSTRAINT FK_4FABF983A76ED395');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE payment_methods');
        $this->addSql('CREATE SEQUENCE users_id_seq');
        $this->addSql('SELECT setval(\'users_id_seq\', (SELECT MAX(id) FROM users))');
        $this->addSql('ALTER TABLE users ALTER id SET DEFAULT nextval(\'users_id_seq\')');
        $this->addSql('CREATE SEQUENCE webhook_events_id_seq');
        $this->addSql('SELECT setval(\'webhook_events_id_seq\', (SELECT MAX(id) FROM webhook_events))');
        $this->addSql('ALTER TABLE webhook_events ALTER id SET DEFAULT nextval(\'webhook_events_id_seq\')');
        $this->addSql('ALTER TABLE webhook_events ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_mokhuba_webhook_processed ON webhook_events (processed)');
        $this->addSql('CREATE INDEX idx_mokhuba_webhook_type ON webhook_events (event_type)');
        $this->addSql('CREATE UNIQUE INDEX uniq_mokhuba_webhook_stripe_id ON webhook_events (stripe_event_id)');
        $this->addSql('CREATE SEQUENCE payments_id_seq');
        $this->addSql('SELECT setval(\'payments_id_seq\', (SELECT MAX(id) FROM payments))');
        $this->addSql('ALTER TABLE payments ALTER id SET DEFAULT nextval(\'payments_id_seq\')');
        $this->addSql('ALTER TABLE payments ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE payments ALTER updated_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_mokhuba_payments_date ON payments (created_at)');
        $this->addSql('CREATE INDEX idx_mokhuba_payments_status ON payments (status)');
        $this->addSql('CREATE UNIQUE INDEX uniq_mokhuba_payments_stripe_pi ON payments (stripe_payment_intent_id)');
        $this->addSql('ALTER INDEX idx_65d29b329a1887dc RENAME TO idx_mokhuba_payments_subscription');
        $this->addSql('ALTER INDEX idx_65d29b32a76ed395 RENAME TO idx_mokhuba_payments_user');
        $this->addSql('CREATE SEQUENCE subscriptions_id_seq');
        $this->addSql('SELECT setval(\'subscriptions_id_seq\', (SELECT MAX(id) FROM subscriptions))');
        $this->addSql('ALTER TABLE subscriptions ALTER id SET DEFAULT nextval(\'subscriptions_id_seq\')');
        $this->addSql('ALTER TABLE subscriptions ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE subscriptions ALTER updated_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_mokhuba_subscriptions_level ON subscriptions (membership_level)');
        $this->addSql('CREATE INDEX idx_mokhuba_subscriptions_status ON subscriptions (status)');
        $this->addSql('CREATE UNIQUE INDEX uniq_mokhuba_subscriptions_stripe ON subscriptions (stripe_subscription_id)');
        $this->addSql('ALTER INDEX idx_4778a01a76ed395 RENAME TO idx_mokhuba_subscriptions_user');
    }
}

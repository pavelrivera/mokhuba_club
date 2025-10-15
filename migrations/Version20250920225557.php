<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add admin user and test user
 */
final class Version20250920222000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default admin and test users';
    }

    public function up(Schema $schema): void
    {
        // Insert admin user
        // Password: 'password' encrypted with bcrypt cost 12
        $this->addSql("INSERT INTO users (
            email, 
            password, 
            first_name, 
            last_name, 
            unique_code, 
            roles, 
            email_verified_at, 
            tobacco_preferences,
            is_active,
            created_at,
            updated_at
        ) VALUES (
            'admin@mokhubaclub.com',
            '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'Admin',
            'Mokhuba',
            'MCC000001',
            '[\"ROLE_ADMIN\", \"ROLE_USER\"]',
            CURRENT_TIMESTAMP,
            '{}',
            true,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )");

        // Insert test user
        $this->addSql("INSERT INTO users (
            email, 
            password, 
            first_name, 
            last_name, 
            unique_code, 
            roles, 
            email_verified_at, 
            tobacco_preferences,
            is_active,
            created_at,
            updated_at
        ) VALUES (
            'test@mokhubaclub.com',
            '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'Usuario',
            'Prueba',
            'MCC000002',
            '[\"ROLE_USER\"]',
            CURRENT_TIMESTAMP,
            '{\"types\": [\"cuban\", \"dominican\"], \"strength\": \"medio-fuerte\"}',
            true,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users WHERE email IN ('admin@mokhubaclub.com', 'test@mokhubaclub.com')");
    }
}
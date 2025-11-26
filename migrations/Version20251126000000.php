<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial schema for production';
    }

    public function up(Schema $schema): void
    {
        // Create user_app table
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS "user_app_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS "user_app" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, age INT NOT NULL, is_active BOOLEAN DEFAULT false NOT NULL, activation_token VARCHAR(64) UNIQUE, profile_image_url VARCHAR(255), PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_IDENTIFIER_EMAIL ON "user_app" (email)');

        // Create chat table
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS "chat_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS "chat" (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');

        // Create message table
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS "message_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS "message" (id INT NOT NULL, chat_id INT NOT NULL, user_id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, FOREIGN KEY (chat_id) REFERENCES "chat" (id), FOREIGN KEY (user_id) REFERENCES "user_app" (id), PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_B6BD307F1A9A7125 ON "message" (chat_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_B6BD307FA76ED395 ON "message" (user_id)');

        // Create chat_member table
        $this->addSql('CREATE TABLE IF NOT EXISTS "chat_member" (chat_id INT NOT NULL, user_id INT NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, FOREIGN KEY (chat_id) REFERENCES "chat" (id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES "user_app" (id) ON DELETE CASCADE, PRIMARY KEY(chat_id, user_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_1B7CF0D6A76ED395 ON "chat_member" (user_id)');

        // Create access_token table
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS "access_token_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE IF NOT EXISTS "access_token" (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(500) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, FOREIGN KEY (user_id) REFERENCES "user_app" (id), PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9386F3B2A76ED395 ON "access_token" (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS "access_token" CASCADE');
        $this->addSql('DROP TABLE IF EXISTS "chat_member" CASCADE');
        $this->addSql('DROP TABLE IF EXISTS "message" CASCADE');
        $this->addSql('DROP TABLE IF EXISTS "chat" CASCADE');
        $this->addSql('DROP TABLE IF EXISTS "user_app" CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS "access_token_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS "message_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS "chat_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE IF EXISTS "user_app_id_seq" CASCADE');
    }
}

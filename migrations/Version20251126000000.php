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
        $this->addSql('CREATE SEQUENCE access_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE chat_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE chat_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE message_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_app_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE access_token (id INT NOT NULL, user_token_id INT NOT NULL, token TEXT, expires_at TIMESTAMP(0) WITHOUT TIME ZONE, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6A2DD68A15303B9 ON access_token (user_token_id)');
        $this->addSql('CREATE TABLE chat (id INT NOT NULL, name VARCHAR(255), is_group BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE chat_member (id INT NOT NULL, chat_id INT NOT NULL, user_associated_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1738CD591A9A7125 ON chat_member (chat_id)');
        $this->addSql('CREATE INDEX IDX_1738CD594DC95A3E ON chat_member (user_associated_id)');
        $this->addSql('CREATE TABLE message (id INT NOT NULL, chat_id INT NOT NULL, sender_id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B6BD307FCF36BB23 ON message (chat_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE TABLE "user_app" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, age INT NOT NULL, is_active BOOLEAN NOT NULL, activation_token VARCHAR(64), profile_image_url VARCHAR(255), PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_22781144E7927C74 ON "user_app" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_22781144B1B4826B ON "user_app" (activation_token)');
        $this->addSql('ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A15303B9 FOREIGN KEY (user_token_id) REFERENCES "user_app" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_member ADD CONSTRAINT FK_1738CD591A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_member ADD CONSTRAINT FK_1738CD594DC95A3E FOREIGN KEY (user_associated_id) REFERENCES "user_app" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCF36BB23 FOREIGN KEY (chat_id) REFERENCES chat (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES "user_app" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_token DROP CONSTRAINT FK_B6A2DD68A15303B9');
        $this->addSql('ALTER TABLE chat_member DROP CONSTRAINT FK_1738CD591A9A7125');
        $this->addSql('ALTER TABLE chat_member DROP CONSTRAINT FK_1738CD594DC95A3E');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FCF36BB23');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FF624B39D');
        $this->addSql('DROP TABLE access_token');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE chat_member');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE "user_app"');
        $this->addSql('DROP SEQUENCE access_token_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE chat_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE chat_member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE message_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_app_id_seq" CASCADE');
    }
}

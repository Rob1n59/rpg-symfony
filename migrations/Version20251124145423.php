<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124145423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(200) NOT NULL, attack_bonus INTEGER DEFAULT NULL, defense_bonus INTEGER DEFAULT NULL, hp_bonus INTEGER DEFAULT NULL)');
        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description CLOB NOT NULL, danger_level INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hp INTEGER NOT NULL, max_hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold INTEGER NOT NULL, experience INTEGER NOT NULL, level INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE enemy');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

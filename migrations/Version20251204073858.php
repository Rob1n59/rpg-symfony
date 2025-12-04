<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204073858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enemy ADD COLUMN description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__enemy AS SELECT id, name, hp, hp_max, attack, defense, gold_reward, xp_reward, danger_level, image, loot_item_id FROM enemy');
        $this->addSql('DROP TABLE enemy');
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, hp_max INTEGER DEFAULT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL, danger_level VARCHAR(50) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, loot_item_id INTEGER DEFAULT NULL, CONSTRAINT FK_FB9F5AA91B072476 FOREIGN KEY (loot_item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO enemy (id, name, hp, hp_max, attack, defense, gold_reward, xp_reward, danger_level, image, loot_item_id) SELECT id, name, hp, hp_max, attack, defense, gold_reward, xp_reward, danger_level, image, loot_item_id FROM __temp__enemy');
        $this->addSql('DROP TABLE __temp__enemy');
        $this->addSql('CREATE INDEX IDX_FB9F5AA91B072476 ON enemy (loot_item_id)');
    }
}

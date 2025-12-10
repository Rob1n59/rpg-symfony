<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210074109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player ADD COLUMN augur_points INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, name, hp, hp_max, attack, defense, equipped_attack_bonus, equipped_defense_bonus, gold, experience, level, player_class_id, player_class_name, current_location_id FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, hp INTEGER NOT NULL, hp_max INTEGER DEFAULT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, equipped_attack_bonus INTEGER DEFAULT NULL, equipped_defense_bonus INTEGER DEFAULT NULL, gold INTEGER NOT NULL, experience INTEGER NOT NULL, level INTEGER NOT NULL, player_class_id INTEGER DEFAULT NULL, player_class_name VARCHAR(255) DEFAULT NULL, current_location_id INTEGER DEFAULT NULL, CONSTRAINT FK_98197A65B8998A57 FOREIGN KEY (current_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, name, hp, hp_max, attack, defense, equipped_attack_bonus, equipped_defense_bonus, gold, experience, level, player_class_id, player_class_name, current_location_id) SELECT id, name, hp, hp_max, attack, defense, equipped_attack_bonus, equipped_defense_bonus, gold, experience, level, player_class_id, player_class_name, current_location_id FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE INDEX IDX_98197A65B8998A57 ON player (current_location_id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126074545 extends AbstractMigration // Assure-toi que le nom de ta classe correspond à ton fichier
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE player_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, player_id INTEGER NOT NULL, item_id INTEGER NOT NULL, CONSTRAINT FK_BD01752D99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BD01752D126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BD01752D99E6F5DF ON player_item (player_id)');
        $this->addSql('CREATE INDEX IDX_BD01752D126F525E ON player_item (item_id)');

        // Correction pour la table ENEMY
        $this->addSql('CREATE TEMPORARY TABLE __temp__enemy AS SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM enemy');
        $this->addSql('DROP TABLE enemy');
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL, image VARCHAR(255) DEFAULT NULL, loot_item_id INTEGER DEFAULT NULL, CONSTRAINT FK_FB9F5AA91B072476 FOREIGN KEY (loot_item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        // MODIFICATION ICI: Ajouter 'image' et 'loot_item_id' avec des valeurs par défaut (NULL)
        $this->addSql('INSERT INTO enemy (id, name, hp, attack, defense, gold_reward, xp_reward, image, loot_item_id) SELECT id, name, hp, attack, defense, gold_reward, xp_reward, NULL, NULL FROM __temp__enemy');
        $this->addSql('DROP TABLE __temp__enemy');
        $this->addSql('CREATE INDEX IDX_FB9F5AA91B072476 ON enemy (loot_item_id)');

        // Correction pour la table ITEM
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, description, attack_bonus, hp_bonus FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, attack_bonus INTEGER DEFAULT NULL, hp_bonus INTEGER DEFAULT NULL, type VARCHAR(50) NOT NULL, healing_amount INTEGER DEFAULT NULL, image VARCHAR(255) DEFAULT NULL)');
        // MODIFICATION ICI: Ajouter 'type' avec une valeur par défaut ('misc'), 'healing_amount' et 'image' avec NULL
        $this->addSql('INSERT INTO item (id, name, description, attack_bonus, hp_bonus, type, healing_amount, image) SELECT id, name, description, attack_bonus, hp_bonus, \'misc\', NULL, NULL FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE player_item');
        $this->addSql('DROP INDEX IDX_FB9F5AA91B072476');
        $this->addSql('CREATE TEMPORARY TABLE __temp__enemy AS SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM enemy');
        $this->addSql('DROP TABLE enemy');
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL)');
        $this->addSql('INSERT INTO enemy (id, name, hp, attack, defense, gold_reward, xp_reward) SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM __temp__enemy');
        $this->addSql('DROP TABLE __temp__enemy');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, description, attack_bonus, hp_bonus FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, attack_bonus INTEGER DEFAULT NULL, hp_bonus INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO item (id, name, description, attack_bonus, hp_bonus) SELECT id, name, description, attack_bonus, hp_bonus FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
    }
}
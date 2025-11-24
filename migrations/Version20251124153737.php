<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124153737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__enemy AS SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM enemy');
        $this->addSql('DROP TABLE enemy');
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL)');
        $this->addSql('INSERT INTO enemy (id, name, hp, attack, defense, gold_reward, xp_reward) SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM __temp__enemy');
        $this->addSql('DROP TABLE __temp__enemy');
        $this->addSql('CREATE TEMPORARY TABLE __temp__location AS SELECT id, name, description, danger_level FROM location');
        $this->addSql('DROP TABLE location');
        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, danger_level INTEGER NOT NULL)');
        $this->addSql('INSERT INTO location (id, name, description, danger_level) SELECT id, name, description, danger_level FROM __temp__location');
        $this->addSql('DROP TABLE __temp__location');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__enemy AS SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM enemy');
        $this->addSql('DROP TABLE enemy');
        $this->addSql('CREATE TABLE enemy (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold_reward INTEGER NOT NULL, xp_reward INTEGER NOT NULL)');
        $this->addSql('INSERT INTO enemy (id, name, hp, attack, defense, gold_reward, xp_reward) SELECT id, name, hp, attack, defense, gold_reward, xp_reward FROM __temp__enemy');
        $this->addSql('DROP TABLE __temp__enemy');
        $this->addSql('CREATE TEMPORARY TABLE __temp__location AS SELECT id, name, description, danger_level FROM location');
        $this->addSql('DROP TABLE location');
        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description CLOB NOT NULL, danger_level INTEGER NOT NULL)');
        $this->addSql('INSERT INTO location (id, name, description, danger_level) SELECT id, name, description, danger_level FROM __temp__location');
        $this->addSql('DROP TABLE __temp__location');
    }
}

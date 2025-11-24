<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124154906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, description, attack_bonus, hp_bonus FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, attack_bonus INTEGER NOT NULL, hp_bonus INTEGER NOT NULL)');
        $this->addSql('INSERT INTO item (id, name, description, attack_bonus, hp_bonus) SELECT id, name, description, attack_bonus, hp_bonus FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, description, attack_bonus, hp_bonus FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(200) NOT NULL, attack_bonus INTEGER DEFAULT NULL, hp_bonus INTEGER DEFAULT NULL, defense_bonus INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO item (id, name, description, attack_bonus, hp_bonus) SELECT id, name, description, attack_bonus, hp_bonus FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202092039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item ADD COLUMN weapon_type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, description, attack_bonus, hp_bonus, type, healing_amount, image FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, attack_bonus INTEGER DEFAULT NULL, hp_bonus INTEGER DEFAULT NULL, type VARCHAR(50) NOT NULL, healing_amount INTEGER DEFAULT NULL, image VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO item (id, name, description, attack_bonus, hp_bonus, type, healing_amount, image) SELECT id, name, description, attack_bonus, hp_bonus, type, healing_amount, image FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
    }
}

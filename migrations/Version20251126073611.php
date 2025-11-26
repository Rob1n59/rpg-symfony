<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126073611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE scene_option (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, text VARCHAR(255) NOT NULL, required_level INTEGER DEFAULT NULL, location_id INTEGER NOT NULL, next_location_id INTEGER DEFAULT NULL, enemy_to_fight_id INTEGER DEFAULT NULL, item_to_gain_id INTEGER DEFAULT NULL, CONSTRAINT FK_E48A66C264D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E48A66C249F256F FOREIGN KEY (next_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E48A66C264CFFEBE FOREIGN KEY (enemy_to_fight_id) REFERENCES enemy (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E48A66C2F527ED3B FOREIGN KEY (item_to_gain_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E48A66C264D218E ON scene_option (location_id)');
        $this->addSql('CREATE INDEX IDX_E48A66C249F256F ON scene_option (next_location_id)');
        $this->addSql('CREATE INDEX IDX_E48A66C264CFFEBE ON scene_option (enemy_to_fight_id)');
        $this->addSql('CREATE INDEX IDX_E48A66C2F527ED3B ON scene_option (item_to_gain_id)');
        $this->addSql('ALTER TABLE location ADD COLUMN image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE scene_option');
        $this->addSql('CREATE TEMPORARY TABLE __temp__location AS SELECT id, name, description, danger_level FROM location');
        $this->addSql('DROP TABLE location');
        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, danger_level INTEGER NOT NULL)');
        $this->addSql('INSERT INTO location (id, name, description, danger_level) SELECT id, name, description, danger_level FROM __temp__location');
        $this->addSql('DROP TABLE __temp__location');
    }
}

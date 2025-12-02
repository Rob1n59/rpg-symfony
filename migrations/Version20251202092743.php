<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202092743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_item ADD COLUMN is_equipped BOOLEAN NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_item AS SELECT id, quantity, player_id, item_id FROM player_item');
        $this->addSql('DROP TABLE player_item');
        $this->addSql('CREATE TABLE player_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, player_id INTEGER NOT NULL, item_id INTEGER NOT NULL, CONSTRAINT FK_BD01752D99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BD01752D126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_item (id, quantity, player_id, item_id) SELECT id, quantity, player_id, item_id FROM __temp__player_item');
        $this->addSql('DROP TABLE __temp__player_item');
        $this->addSql('CREATE INDEX IDX_BD01752D99E6F5DF ON player_item (player_id)');
        $this->addSql('CREATE INDEX IDX_BD01752D126F525E ON player_item (item_id)');
    }
}

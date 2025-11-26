<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126072000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, name, hp, attack, defense, gold, experience FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold INTEGER NOT NULL, experience INTEGER NOT NULL, level INTEGER NOT NULL, current_location_id INTEGER DEFAULT NULL, CONSTRAINT FK_98197A65B8998A57 FOREIGN KEY (current_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        
        // MODIFICATION ICI: Ajouter la colonne 'level' avec une valeur par défaut de 1 lors de l'insertion
        // Et inclure 'current_location_id' avec NULL par défaut
        $this->addSql('INSERT INTO player (id, name, hp, attack, defense, gold, experience, level, current_location_id) SELECT id, name, hp, attack, defense, gold, experience, 1, NULL FROM __temp__player');
        
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE INDEX IDX_98197A65B8998A57 ON player (current_location_id)');
        // Il semble que l'entité Location n'existait pas encore ou n'était pas dans le schéma au moment de la génération
        // Si tu n'as pas encore de table 'location', il faudra ajouter sa création ici ou dans une migration précédente.
        // Sinon, si la table 'location' existe déjà, cette partie est correcte.
        // Si elle n'existe pas, tu auras une erreur lors de l'exécution à cause de la FK.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_98197A65B8998A57');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, name, hp, attack, defense, gold, experience FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(25) NOT NULL, hp INTEGER NOT NULL, attack INTEGER NOT NULL, defense INTEGER NOT NULL, gold INTEGER NOT NULL, experience INTEGER NOT NULL)');
        $this->addSql('INSERT INTO player (id, name, hp, attack, defense, gold, experience) SELECT id, name, hp, attack, defense, gold, experience FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
    }
}
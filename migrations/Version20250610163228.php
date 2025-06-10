<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610163228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE city_view (id VARCHAR(36) NOT NULL, owner_id VARCHAR(36) NOT NULL, game_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, x INT NOT NULL, y INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE game_view (id VARCHAR(36) NOT NULL, name VARCHAR(120) NOT NULL, active_player VARCHAR(36) NOT NULL, current_turn INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, players JSON NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, current_turn_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN game_view.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN game_view.started_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN game_view.current_turn_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE map_tile_view (id VARCHAR(255) NOT NULL, game_id VARCHAR(255) NOT NULL, x INT NOT NULL, y INT NOT NULL, terrain VARCHAR(255) NOT NULL, is_occupied BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE map_view (game_id VARCHAR(36) NOT NULL, width INT NOT NULL, height INT NOT NULL, tiles JSON NOT NULL, generated_at VARCHAR(30) NOT NULL, PRIMARY KEY(game_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE unit_view (id VARCHAR(36) NOT NULL, owner_id VARCHAR(36) NOT NULL, game_id VARCHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, x INT NOT NULL, y INT NOT NULL, current_health INT NOT NULL, max_health INT NOT NULL, is_dead BOOLEAN NOT NULL, attack_power INT NOT NULL, defense_power INT NOT NULL, movement_range INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE city_view
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE game_view
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE map_tile_view
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE map_view
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE unit_view
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}

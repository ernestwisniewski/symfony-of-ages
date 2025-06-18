<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618092328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE event_streams_no_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE projections_no_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE _90372ed3f0df39defa6c6f585cab3d723adcc862_no_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE _89d1a3ed00d266d374d16fc9cadf533ba4846ea1_no_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE player_visibility (player_id VARCHAR(36) NOT NULL, game_id VARCHAR(36) NOT NULL, x INT NOT NULL, y INT NOT NULL, state VARCHAR(20) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(player_id, game_id, x, y))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN player_visibility.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE _90372ed3f0df39defa6c6f585cab3d723adcc862
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE _89d1a3ed00d266d374d16fc9cadf533ba4846ea1
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_streams
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projections
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE event_streams_no_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE projections_no_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE _90372ed3f0df39defa6c6f585cab3d723adcc862_no_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE _89d1a3ed00d266d374d16fc9cadf533ba4846ea1_no_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE _90372ed3f0df39defa6c6f585cab3d723adcc862 (no BIGSERIAL NOT NULL, event_id UUID NOT NULL, event_name VARCHAR(100) NOT NULL, payload JSON NOT NULL, metadata JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(no))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX _90372ed3f0df39defa6c6f585cab3d723adcc862_event_id_key ON _90372ed3f0df39defa6c6f585cab3d723adcc862 (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX _90372ed3f0df39defa6c6f585cab3d723adcc862_expr_expr1_no_idx ON _90372ed3f0df39defa6c6f585cab3d723adcc862 (no)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE _89d1a3ed00d266d374d16fc9cadf533ba4846ea1 (no BIGSERIAL NOT NULL, event_id UUID NOT NULL, event_name VARCHAR(100) NOT NULL, payload JSON NOT NULL, metadata JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(no))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX _89d1a3ed00d266d374d16fc9cadf533ba4846ea1_event_id_key ON _89d1a3ed00d266d374d16fc9cadf533ba4846ea1 (event_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX _89d1a3ed00d266d374d16fc9cadf533ba4846ea1_expr_expr1_no_idx ON _89d1a3ed00d266d374d16fc9cadf533ba4846ea1 (no)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE event_streams (no BIGSERIAL NOT NULL, real_stream_name VARCHAR(150) NOT NULL, stream_name CHAR(41) NOT NULL, metadata JSONB DEFAULT NULL, category VARCHAR(150) DEFAULT NULL, PRIMARY KEY(no))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX event_streams_category_idx ON event_streams (category)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX event_streams_stream_name_key ON event_streams (stream_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projections (no BIGSERIAL NOT NULL, name VARCHAR(150) NOT NULL, "position" JSONB DEFAULT NULL, state JSONB DEFAULT NULL, status VARCHAR(28) NOT NULL, locked_until CHAR(26) DEFAULT NULL, PRIMARY KEY(no))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX projections_name_key ON projections (name)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE player_visibility
        SQL);
    }
}

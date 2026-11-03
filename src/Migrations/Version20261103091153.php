<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103091153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cateogry, puzzle and gameSession tables.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_session (id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, player_grid JSON NOT NULL, errors_count INT NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, puzzle_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_4586AAFBD9816812 (puzzle_id), INDEX IDX_4586AAFBA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE puzzle (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, difficulty INT NOT NULL, size_x INT NOT NULL, size_y INT NOT NULL, size_z INT NOT NULL, object_grid JSON NOT NULL, is_published TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_22A6DFDF12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFBD9816812 FOREIGN KEY (puzzle_id) REFERENCES puzzle (id)');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE puzzle ADD CONSTRAINT FK_22A6DFDF12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFBD9816812');
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFBA76ED395');
        $this->addSql('ALTER TABLE puzzle DROP FOREIGN KEY FK_22A6DFDF12469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE game_session');
        $this->addSql('DROP TABLE puzzle');
    }
}

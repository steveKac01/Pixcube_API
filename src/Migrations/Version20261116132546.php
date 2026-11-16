<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116132546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SizeX, sizeY and sizeZ removed.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY `FK_4586AAFBA76ED395`');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT FK_4586AAFBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE puzzle DROP size_x, DROP size_y, DROP size_z');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_session DROP FOREIGN KEY FK_4586AAFBA76ED395');
        $this->addSql('ALTER TABLE game_session ADD CONSTRAINT `FK_4586AAFBA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE puzzle ADD size_x INT NOT NULL, ADD size_y INT NOT NULL, ADD size_z INT NOT NULL');
    }
}

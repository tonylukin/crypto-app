<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220622083106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE symbol (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(32) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, riskable TINYINT(1) NOT NULL DEFAULT 0, total_price DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX ix_symbol_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD sale_price DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE symbol');
        $this->addSql('ALTER TABLE `order` DROP sale_price');
    }
}

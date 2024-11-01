<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220713044658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE sale_date sell_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD buy_reason VARCHAR(64) DEFAULT NULL, ADD sell_reason VARCHAR(255) DEFAULT NULL, CHANGE sale_price sell_price DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE sell_date sale_date DATETIME DEFAULT NULL, DROP buy_reason, DROP sell_reason, CHANGE sell_price sale_price DOUBLE PRECISION DEFAULT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220706160619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX ix_price_datetime_symbol ON `order`');
        $this->addSql('ALTER TABLE `order` ADD symbol_id INT AFTER `id`, DROP symbol');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('CREATE INDEX ix_order_symbol_status_exchange ON `order` (symbol_id, status, exchange)');
        $this->addSql('DROP INDEX ix_price_datetime_symbol ON price');
        $this->addSql('ALTER TABLE price ADD symbol_id INT AFTER `id`, DROP symbol');
        $this->addSql('ALTER TABLE price ADD CONSTRAINT FK_CAC822D9C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('CREATE INDEX IDX_CAC822D9C0F75674 ON price (symbol_id)');
        $this->addSql('CREATE UNIQUE INDEX ix_price_datetime_symbol ON price (datetime, symbol_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C0F75674');
        $this->addSql('DROP INDEX ix_order_symbol_status_exchange ON `order`');
        $this->addSql('ALTER TABLE `order` ADD symbol VARCHAR(16) NOT NULL, DROP symbol_id, CHANGE sale_date sale_date DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX ix_price_datetime_symbol ON `order` (symbol, status, exchange)');
        $this->addSql('ALTER TABLE price DROP FOREIGN KEY FK_CAC822D9C0F75674');
        $this->addSql('DROP INDEX IDX_CAC822D9C0F75674 ON price');
        $this->addSql('DROP INDEX ix_price_datetime_symbol ON price');
        $this->addSql('ALTER TABLE price ADD symbol VARCHAR(16) NOT NULL, DROP symbol_id');
        $this->addSql('CREATE INDEX ix_price_datetime_symbol ON price (datetime, symbol)');
    }
}

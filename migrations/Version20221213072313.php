<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221213072313 extends AbstractMigration
{
    private const EXCHANGE_BINANCE = 1;
    private const USER_ID = 1;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_symbol (user_id INT NOT NULL, symbol_id INT NOT NULL, INDEX IDX_899A2DC4A76ED395 (user_id), INDEX IDX_899A2DC4C0F75674 (symbol_id), PRIMARY KEY(user_id, symbol_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C0F75674');
        $this->addSql('DROP INDEX ix_order_symbol_status_exchange ON `order`');
        $this->addSql(sprintf('ALTER TABLE `order` ADD user_id INT NOT NULL DEFAULT %d, DROP exchange', self::USER_ID));
        $this->addSql('ALTER TABLE `order` ADD exchange SMALLINT NOT NULL DEFAULT ' . self::EXCHANGE_BINANCE);
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX ix_order_user_symbol_status_exchange ON `order` (user_id, symbol_id, status, exchange)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('ALTER TABLE price DROP exchange');
        $this->addSql('ALTER TABLE price ADD exchange SMALLINT NOT NULL DEFAULT ' . self::EXCHANGE_BINANCE);

        $this->addSql('ALTER TABLE user_symbol ADD active TINYINT(1) NOT NULL DEFAULT 1, ADD riskable TINYINT(1) NOT NULL DEFAULT 0, ADD total_price DOUBLE PRECISION DEFAULT NULL');
        $this->addSql(sprintf('INSERT INTO user_symbol (user_id, symbol_id, active, riskable, total_price) SELECT %d, id, active, riskable, total_price FROM symbol', self::USER_ID));
        $this->addSql('ALTER TABLE symbol DROP active, DROP riskable, DROP total_price');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user_symbol');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C0F75674');
        $this->addSql('DROP INDEX ix_order_user_symbol_status_exchange ON `order`');
        $this->addSql('ALTER TABLE `order` DROP user_id, DROP exchange');
        $this->addSql('ALTER TABLE `order` ADD exchange VARCHAR(16) NULL');
        $this->addSql('CREATE INDEX ix_order_symbol_status_exchange ON `order` (symbol_id, status, exchange)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('ALTER TABLE price DROP exchange');
        $this->addSql('ALTER TABLE price ADD exchange VARCHAR(16) NULL');

        $this->addSql('UPDATE `order` SET exchange = \'binance\'');
        $this->addSql('UPDATE price SET exchange = \'binance\'');

        $this->addSql('ALTER TABLE symbol ADD active TINYINT(1) NOT NULL DEFAULT 1, ADD riskable TINYINT(1) NOT NULL DEFAULT 0, ADD total_price DOUBLE PRECISION DEFAULT NULL');
    }
}

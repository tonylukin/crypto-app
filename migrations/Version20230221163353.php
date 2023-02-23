<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230221163353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_setting ADD use_exchange SMALLINT DEFAULT 1 NOT NULL, ADD binance_api_key VARCHAR(64) DEFAULT NULL, ADD binance_api_secret VARCHAR(255) DEFAULT NULL, ADD huobi_api_key VARCHAR(64) DEFAULT NULL, ADD huobi_api_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE user_setting us INNER JOIN user u ON u.id = us.user_id SET us.binance_api_key = u.binance_api_key, us.binance_api_secret = u.binance_api_secret');
        $this->addSql('ALTER TABLE user DROP binance_api_key, DROP binance_api_secret');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_setting DROP use_exchange, DROP binance_api_key, DROP binance_api_secret, DROP huobi_api_key, DROP huobi_api_secret');
        $this->addSql('ALTER TABLE user ADD binance_api_key VARCHAR(64) DEFAULT NULL, ADD binance_api_secret VARCHAR(255) DEFAULT NULL');
    }
}

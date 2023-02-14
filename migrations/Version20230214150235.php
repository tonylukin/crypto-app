<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230214150235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_setting ADD max_percent_diff_on_moving DOUBLE PRECISION DEFAULT NULL, ADD legal_moving_step_percent DOUBLE PRECISION DEFAULT NULL, ADD hours_extremely_short_interval_for_prices INT DEFAULT NULL, ADD min_price_diff_percent_after_last_sell DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_setting DROP max_percent_diff_on_moving, DROP legal_moving_step_percent, DROP hours_extremely_short_interval_for_prices, DROP min_price_diff_percent_after_last_sell');
    }
}

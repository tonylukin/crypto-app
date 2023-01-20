<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230117133355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C0F75674');
        $this->addSql('ALTER TABLE `order` CHANGE user_id user_id INT NOT NULL, CHANGE exchange exchange SMALLINT NOT NULL');
        $this->addSql('DROP INDEX fk_f5299398c0f75674 ON `order`');
        $this->addSql('CREATE INDEX IDX_F5299398C0F75674 ON `order` (symbol_id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('ALTER TABLE price CHANGE exchange exchange SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE user_symbol DROP FOREIGN KEY FK_899A2DC4C0F75674');
        $this->addSql('ALTER TABLE user_symbol DROP FOREIGN KEY FK_899A2DC4A76ED395');
        $this->addSql('ALTER TABLE user_symbol DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_symbol ADD PRIMARY KEY (symbol_id, user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398C0F75674');
        $this->addSql('ALTER TABLE `order` CHANGE user_id user_id INT DEFAULT 1 NOT NULL, CHANGE exchange exchange SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('DROP INDEX idx_f5299398c0f75674 ON `order`');
        $this->addSql('CREATE INDEX FK_F5299398C0F75674 ON `order` (symbol_id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
        $this->addSql('ALTER TABLE price CHANGE exchange exchange SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE user_symbol DROP FOREIGN KEY FK_899A2DC4C0F75674');
        $this->addSql('ALTER TABLE user_symbol DROP FOREIGN KEY FK_899A2DC4A76ED395');
        $this->addSql('ALTER TABLE user_symbol DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_symbol ADD CONSTRAINT FK_899A2DC4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_symbol ADD PRIMARY KEY (user_id, symbol_id)');
    }
}

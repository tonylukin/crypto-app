<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230424111343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE last_price (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, symbol_id INT NOT NULL, price DOUBLE PRECISION NOT NULL, lowest TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A88314C2C0F75674 (symbol_id), INDEX ix_last_price_user_symbol (user_id, symbol_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE last_price ADD CONSTRAINT FK_A88314C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE last_price ADD CONSTRAINT FK_A88314C2C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE last_price DROP FOREIGN KEY FK_A88314C2A76ED395');
        $this->addSql('ALTER TABLE last_price DROP FOREIGN KEY FK_A88314C2C0F75674');
        $this->addSql('DROP TABLE last_price');
    }
}

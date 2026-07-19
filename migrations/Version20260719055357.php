<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719055357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_category_subscription (user_id INT NOT NULL, category_id INT NOT NULL, PRIMARY KEY(user_id, category_id))');
        $this->addSql('CREATE INDEX IDX_B9150A3FA76ED395 ON user_category_subscription (user_id)');
        $this->addSql('CREATE INDEX IDX_B9150A3F12469DE2 ON user_category_subscription (category_id)');
        $this->addSql('ALTER TABLE user_category_subscription ADD CONSTRAINT FK_B9150A3FA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_category_subscription ADD CONSTRAINT FK_B9150A3F12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX uniq_957a647992fc23a8');
        $this->addSql('DROP INDEX uniq_957a6479a0d96fbf');
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(500)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_category_subscription DROP CONSTRAINT FK_B9150A3FA76ED395');
        $this->addSql('ALTER TABLE user_category_subscription DROP CONSTRAINT FK_B9150A3F12469DE2');
        $this->addSql('DROP TABLE user_category_subscription');
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(500)');
        $this->addSql('CREATE UNIQUE INDEX uniq_957a647992fc23a8 ON fos_user (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX uniq_957a6479a0d96fbf ON fos_user (email_canonical)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717114122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fos_user ADD mobile_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE voter ALTER type TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE voter ALTER type DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE voter ALTER type TYPE SMALLINT');
        $this->addSql('ALTER TABLE voter ALTER type SET DEFAULT 0');
        $this->addSql('ALTER TABLE fos_user DROP mobile_number');
    }
}

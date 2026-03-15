<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315092641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(500)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(500)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE fos_user ALTER mobile_number TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE fos_user ALTER email TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE fos_user ALTER email_canonical TYPE VARCHAR(255)');
    }
}

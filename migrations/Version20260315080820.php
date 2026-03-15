<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315080820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vote_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT vote_pkey');
        $this->addSql('ALTER TABLE vote ADD id INT DEFAULT nextval(\'vote_id_seq\') NOT NULL');
        $this->addSql('ALTER TABLE vote ALTER user_id DROP NOT NULL');
        // Cast existing integer votes to string so we don't lose them
        $this->addSql('ALTER TABLE vote ALTER value TYPE VARCHAR(255) USING value::VARCHAR');
        $this->addSql('ALTER TABLE vote ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE voter DROP value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE vote_id_seq CASCADE');
        $this->addSql('DROP INDEX vote_pkey');
        $this->addSql('ALTER TABLE vote DROP id');
        $this->addSql('ALTER TABLE vote ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE vote ALTER value TYPE INT');
        $this->addSql('ALTER TABLE vote ADD PRIMARY KEY (voting_id, user_id)');
        $this->addSql('ALTER TABLE voter ADD value INT DEFAULT NULL');
    }
}

<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20201223105657 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER SEQUENCE ext_log_entries_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE delegation_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE initiative_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE favourite_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE voting_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE fos_user_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE category_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE comment_id_seq INCREMENT BY 1');
        $this->addSql('ALTER SEQUENCE fos_group_id_seq INCREMENT BY 1');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER SEQUENCE ext_log_entries_id_seq INCREMENT BY 32');
        $this->addSql('ALTER SEQUENCE initiative_id_seq INCREMENT BY 6');
        $this->addSql('ALTER SEQUENCE voting_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE delegation_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE favourite_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE fos_user_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE category_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE comment_id_seq INCREMENT BY 0');
        $this->addSql('ALTER SEQUENCE fos_group_id_seq INCREMENT BY 0');
    }
}

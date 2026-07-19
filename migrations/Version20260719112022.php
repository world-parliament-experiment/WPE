<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719112022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate user_category_subscription with default categories (Global + User Country).';
    }

    public function up(Schema $schema): void
    {
        // 1. Subscribe all users to all Global categories (type = 0)
        $this->addSql('INSERT INTO user_category_subscription (user_id, category_id)
            SELECT u.id, c.id
            FROM fos_user u, category c
            WHERE c.type = 0
            ON CONFLICT DO NOTHING');

        // 2. Subscribe all users to their national category (type = 2) if it matches their country
        $this->addSql('INSERT INTO user_category_subscription (user_id, category_id)
            SELECT u.id, c.id
            FROM fos_user u
            JOIN category c ON c.type = 2 AND c.country = u.country
            ON CONFLICT DO NOTHING');
    }

    public function down(Schema $schema): void
    {
    }
}

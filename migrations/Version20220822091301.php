<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220822091301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates data base';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `Groups` (id CHAR(36) NOT NULL, name VARCHAR(50) NOT NULL, description TEXT, created_on DATETIME NOT NULL, UNIQUE INDEX u_groups_id (id), UNIQUE INDEX u_groups_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Orders (id CHAR(36) NOT NULL, user_id CHAR(36) NOT NULL, product_id CHAR(36) NOT NULL, group_id CHAR(36) NOT NULL, deleted TINYINT(1) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, description TEXT, created_on DATETIME NOT NULL, bougth_on DATETIME DEFAULT NULL, buy_on DATETIME DEFAULT NULL, INDEX idx_order_product_id (product_id), INDEX idx_user_id (user_id), INDEX idx_user_group (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Products (id CHAR(36) NOT NULL, name VARCHAR(50) NOT NULL, description TEXT, created_on DATETIME NOT NULL, UNIQUE INDEX u_products_id (id), UNIQUE INDEX u_products_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Users (id CHAR(36) NOT NULL, email VARCHAR(50) NOT NULL, password VARCHAR(256) NOT NULL, name VARCHAR(50) NOT NULL, created_on DATETIME NOT NULL, UNIQUE INDEX u_users_id (id), UNIQUE INDEX u_users_name (name), UNIQUE INDEX u_users_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Users_groups (user_id CHAR(36) NOT NULL, group_id CHAR(36) NOT NULL, INDEX idx_users_groups_user_id (user_id), INDEX idx_users_groups_group_id (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Orders ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE Orders ADD CONSTRAINT fk_orders_product_id FOREIGN KEY (product_id) REFERENCES Products (id)');
        $this->addSql('ALTER TABLE Orders ADD CONSTRAINT fk_orders_group_id FOREIGN KEY (group_id) REFERENCES `Groups` (id)');
        $this->addSql('ALTER TABLE Users_groups ADD CONSTRAINT fk_users_groups_user_id FOREIGN KEY (user_id) REFERENCES Users (id)');
        $this->addSql('ALTER TABLE Users_groups ADD CONSTRAINT fk_users_groups_group_id FOREIGN KEY (group_id) REFERENCES `Groups` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Orders DROP FOREIGN KEY FK_E283F8D8A76ED395');
        $this->addSql('ALTER TABLE Orders DROP FOREIGN KEY FK_E283F8D84584665A');
        $this->addSql('ALTER TABLE Orders DROP FOREIGN KEY FK_E283F8D8FE54D947');
        $this->addSql('ALTER TABLE Users_groups DROP FOREIGN KEY FK_E7BB6C18A76ED395');
        $this->addSql('ALTER TABLE Users_groups DROP FOREIGN KEY FK_E7BB6C18FE54D947');
        $this->addSql('DROP TABLE `Groups`');
        $this->addSql('DROP TABLE Orders');
        $this->addSql('DROP TABLE Products');
        $this->addSql('DROP TABLE Users');
        $this->addSql('DROP TABLE Users_groups');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

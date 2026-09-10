<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910120640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE observation (id VARCHAR(64) NOT NULL, zip_code VARCHAR(5) NOT NULL, date_observed VARCHAR(10) NOT NULL, hour_observed VARCHAR(5) NOT NULL, local_time_zone VARCHAR(10) NOT NULL, site_id VARCHAR(40) NOT NULL, site_name VARCHAR(255) NOT NULL, reporting_area_name VARCHAR(255) NOT NULL, parameter_name VARCHAR(30) NOT NULL, aqi INTEGER NOT NULL, category_name VARCHAR(80) NOT NULL, source CLOB NOT NULL, fetched_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C576DBE0A1ACE158AC8E9810DBF9B7A4 ON observation (zip_code, date_observed, hour_observed)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE observation');
    }
}

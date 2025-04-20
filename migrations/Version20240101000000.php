<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables initiales';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roles (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(50) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE utilisateurs (
            id INT AUTO_INCREMENT NOT NULL,
            numero_cp VARCHAR(7) NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            role_id INT NOT NULL,
            UNIQUE INDEX UNIQ_497B315E8A90ABA9 (numero_cp),
            UNIQUE INDEX UNIQ_497B315EE7927C74 (email),
            INDEX IDX_497B315ED60322AC (role_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE produits (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            categorie VARCHAR(50) NOT NULL,
            taille VARCHAR(10) NOT NULL,
            quantite INT NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE commandes (
            id INT AUTO_INCREMENT NOT NULL,
            utilisateur_id INT NOT NULL,
            date_commande DATETIME NOT NULL,
            statut VARCHAR(20) NOT NULL,
            INDEX IDX_35D4282CFB88E14F (utilisateur_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('CREATE TABLE commande_details (
            id INT AUTO_INCREMENT NOT NULL,
            commande_id INT NOT NULL,
            produit_id INT NOT NULL,
            quantite INT NOT NULL,
            INDEX IDX_849D792A82EA2E54 (commande_id),
            INDEX IDX_849D792AF347EFB (produit_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('ALTER TABLE utilisateurs ADD CONSTRAINT FK_497B315ED60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE commande_details ADD CONSTRAINT FK_849D792A82EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id)');
        $this->addSql('ALTER TABLE commande_details ADD CONSTRAINT FK_849D792AF347EFB FOREIGN KEY (produit_id) REFERENCES produits (id)');

        // Insertion des rôles par défaut
        $this->addSql("INSERT INTO roles (nom) VALUES ('Opérateur'), ('DPX'), ('DUO')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande_details DROP FOREIGN KEY FK_849D792A82EA2E54');
        $this->addSql('ALTER TABLE commande_details DROP FOREIGN KEY FK_849D792AF347EFB');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CFB88E14F');
        $this->addSql('ALTER TABLE utilisateurs DROP FOREIGN KEY FK_497B315ED60322AC');
        $this->addSql('DROP TABLE commande_details');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE produits');
        $this->addSql('DROP TABLE utilisateurs');
        $this->addSql('DROP TABLE roles');
    }
}
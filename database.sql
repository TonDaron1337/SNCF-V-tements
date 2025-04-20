DROP DATABASE IF EXISTS sncf_vetements;
CREATE DATABASE sncf_vetements;
USE sncf_vetements;

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL
);

CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_cp VARCHAR(8) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    role VARCHAR(50) DEFAULT 'Opérateur'
);

CREATE TABLE produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    categorie VARCHAR(50) NOT NULL,
    taille VARCHAR(10) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255)
);

CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'acceptee', 'refusee') DEFAULT 'en_attente',
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE commande_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT,
    produit_id INT,
    quantite INT NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id),
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- Insertion des rôles par défaut
INSERT INTO roles (nom) VALUES 
('Opérateur'),
('DPX'),
('DUO');

-- Création d'un utilisateur de test
INSERT INTO utilisateurs (numero_cp, email, password, nom, prenom, role) VALUES 
('1234567A', 'test@sncf.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 'Opérateur');

-- Insertion des produits avec leurs stocks initiaux
-- T-shirts gris
INSERT INTO produits (nom, description, categorie, taille, quantite, image_url) VALUES
('T-shirt Gris', 'T-shirt gris confortable pour le travail quotidien', 'tshirt', 'M', 50, '/images/tshirt-gris.jpg'),
('T-shirt Gris', 'T-shirt gris confortable pour le travail quotidien', 'tshirt', 'L', 50, '/images/tshirt-gris.jpg'),
('T-shirt Gris', 'T-shirt gris confortable pour le travail quotidien', 'tshirt', 'XL', 50, '/images/tshirt-gris.jpg'),
('T-shirt Gris', 'T-shirt gris confortable pour le travail quotidien', 'tshirt', '2XL', 50, '/images/tshirt-gris.jpg');

-- Vestes Haute Visibilité
INSERT INTO produits (nom, description, categorie, taille, quantite, image_url) VALUES
('Veste HV', 'Veste haute visibilité avec bandes réfléchissantes', 'veste', 'M', 30, '/images/veste-hv.jpg'),
('Veste HV', 'Veste haute visibilité avec bandes réfléchissantes', 'veste', 'L', 30, '/images/veste-hv.jpg'),
('Veste HV', 'Veste haute visibilité avec bandes réfléchissantes', 'veste', 'XL', 30, '/images/veste-hv.jpg'),
('Veste HV', 'Veste haute visibilité avec bandes réfléchissantes', 'veste', '2XL', 30, '/images/veste-hv.jpg');

-- Pantalons Haute Visibilité
INSERT INTO produits (nom, description, categorie, taille, quantite, image_url) VALUES
('Pantalon HV', 'Pantalon haute visibilité avec bandes réfléchissantes', 'pantalon', 'M', 40, '/images/pantalon-hv.jpg'),
('Pantalon HV', 'Pantalon haute visibilité avec bandes réfléchissantes', 'pantalon', 'L', 40, '/images/pantalon-hv.jpg'),
('Pantalon HV', 'Pantalon haute visibilité avec bandes réfléchissantes', 'pantalon', 'XL', 40, '/images/pantalon-hv.jpg'),
('Pantalon HV', 'Pantalon haute visibilité avec bandes réfléchissantes', 'pantalon', '2XL', 40, '/images/pantalon-hv.jpg');
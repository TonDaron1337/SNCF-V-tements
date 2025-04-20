import sqlite3 from 'sqlite3';
import { open } from 'sqlite';
import bcrypt from 'bcryptjs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));

async function loadFixtures() {
  const db = await open({
    filename: join(__dirname, '../data/sncf_vetements.db'),
    driver: sqlite3.Database
  });

  // Vider les tables existantes
  await db.exec(`
    DELETE FROM commande_details;
    DELETE FROM commandes;
    DELETE FROM produits;
    DELETE FROM utilisateurs;
    DELETE FROM roles;
  `);

  // Insérer les rôles
  const roles = [
    { nom: 'Opérateur' },
    { nom: 'DPX' },
    { nom: 'DUO' }
  ];

  for (const role of roles) {
    await db.run('INSERT INTO roles (nom) VALUES (?)', role.nom);
  }

  // Insérer des utilisateurs de test
  const password = bcrypt.hashSync('password123', 10);
  const users = [
    { numero_cp: '1234567A', email: 'operateur@sncf.fr', nom: 'Dupont', prenom: 'Jean', role_id: 1 },
    { numero_cp: '2345678B', email: 'dpx@sncf.fr', nom: 'Martin', prenom: 'Sophie', role_id: 2 },
    { numero_cp: '3456789C', email: 'duo@sncf.fr', nom: 'Dubois', prenom: 'Pierre', role_id: 3 }
  ];

  for (const user of users) {
    await db.run(`
      INSERT INTO utilisateurs (numero_cp, email, mot_de_passe, nom, prenom, role_id)
      VALUES (?, ?, ?, ?, ?, ?)
    `, [user.numero_cp, user.email, password, user.nom, user.prenom, user.role_id]);
  }

  // Insérer les produits
  const produits = [
    {
      nom: 'T-shirt Gris',
      description: 'T-shirt gris confortable pour le travail quotidien',
      categorie: 'tshirt',
      taille: 'M',
      quantite: 50,
      image_url: '/images/tshirt-gris.jpg'
    },
    {
      nom: 'Veste Haute Visibilité',
      description: 'Veste de sécurité haute visibilité avec bandes réfléchissantes',
      categorie: 'veste',
      taille: 'L',
      quantite: 30,
      image_url: '/images/veste-hv.jpg'
    },
    {
      nom: 'Pantalon Haute Visibilité',
      description: 'Pantalon de sécurité haute visibilité avec bandes réfléchissantes',
      categorie: 'pantalon',
      taille: 'XL',
      quantite: 40,
      image_url: '/images/pantalon-hv.jpg'
    }
  ];

  for (const produit of produits) {
    await db.run(`
      INSERT INTO produits (nom, description, categorie, taille, quantite, image_url)
      VALUES (?, ?, ?, ?, ?, ?)
    `, [produit.nom, produit.description, produit.categorie, produit.taille, produit.quantite, produit.image_url]);
  }

  console.log('Données initiales chargées avec succès !');
  console.log('Utilisateurs de test créés :');
  console.log('- Opérateur : 1234567A / password123');
  console.log('- DPX : 2345678B / password123');
  console.log('- DUO : 3456789C / password123');

  await db.close();
}

loadFixtures().catch(console.error);
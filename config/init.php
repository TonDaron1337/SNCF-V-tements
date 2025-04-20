<?php
// Configuration de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
session_start();

// Chargement des dépendances
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Connexion à la base de données
$database = new Database();
$db = $database->getConnection();
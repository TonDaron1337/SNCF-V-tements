<?php
// Démarrage de la session
session_start();

// Chargement des configurations
require_once __DIR__ . '/../config/config.php';

// Fonction de redirection
function redirect($path) {
    $url = FULL_BASE_URL . '/' . ltrim($path, '/');
    header("Location: $url");
    exit();
}

// Fonction de vérification d'authentification
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login');
    }
}

// Fonction de vérification des rôles
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        redirect('');
    }
}

// Fonction de nettoyage des entrées
function clean($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Fonction de génération d'URL
function url($path = '') {
    return FULL_BASE_URL . '/' . ltrim($path, '/');
}

// Connexion à la base de données
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("Erreur de connexion : " . $e->getMessage());
    }
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}
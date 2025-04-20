<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['produit_id']) || !isset($_GET['taille'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT quantite FROM produits WHERE id = ? AND taille = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['produit_id'], $_GET['taille']]);
$stock = $stmt->fetchColumn();

echo json_encode(['stock' => $stock]);
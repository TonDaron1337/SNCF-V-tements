<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Créer le dossier uploads s'il n'existe pas
if (!file_exists('public/uploads')) {
    mkdir('public/uploads', 0777, true);
}

// Copier les images vers le dossier uploads
$images = [
    'pantalon-hi-vis.jpg',
    'parka-hi-vis.jpg',
    'polaire-noire.jpg',
    'tshirt-gris.jpg'
];

foreach ($images as $image) {
    if (file_exists("images/$image")) {
        copy("images/$image", "public/uploads/$image");
    }
}

// Initialiser les produits avec leurs images
$produits = [
    [
        'nom' => 'Pantalon Haute Visibilité',
        'description' => 'Pantalon de sécurité haute visibilité avec bandes réfléchissantes',
        'categorie' => 'pantalon',
        'image_url' => 'uploads/pantalon-hi-vis.jpg'
    ],
    [
        'nom' => 'Parka Haute Visibilité',
        'description' => 'Parka de protection haute visibilité avec bandes réfléchissantes',
        'categorie' => 'veste',
        'image_url' => 'uploads/parka-hi-vis.jpg'
    ],
    [
        'nom' => 'Polaire Noire',
        'description' => 'Veste polaire chaude pour l\'hiver',
        'categorie' => 'veste',
        'image_url' => 'uploads/polaire-noire.jpg'
    ],
    [
        'nom' => 'T-shirt Gris',
        'description' => 'T-shirt gris confortable pour le travail quotidien',
        'categorie' => 'tshirt',
        'image_url' => 'uploads/tshirt-gris.jpg'
    ]
];

// Vider la table produits avant l'insertion
$db->query("DELETE FROM produits");

// Insérer les produits pour chaque taille disponible
$tailles = ['M', 'L', 'XL', '2XL'];

foreach ($produits as $produit) {
    foreach ($tailles as $taille) {
        $query = "INSERT INTO produits (nom, description, categorie, taille, quantite, image_url) 
                  VALUES (:nom, :description, :categorie, :taille, :quantite, :image_url)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':nom' => $produit['nom'],
            ':description' => $produit['description'],
            ':categorie' => $produit['categorie'],
            ':taille' => $taille,
            ':quantite' => 50, // Stock initial
            ':image_url' => $produit['image_url']
        ]);
    }
}

echo "Initialisation des produits terminée avec succès.\n";
echo "16 produits créés (4 produits × 4 tailles)\n";
echo "Stock initial : 50 pièces par produit\n";
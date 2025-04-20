<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
if (!in_array($categorie, ['tshirt', 'veste', 'pantalon'])) {
    header('Location: catalogue.php');
    exit();
}

// Récupérer les informations du produit
$query = "SELECT * FROM produits WHERE categorie = :categorie ORDER BY taille";
$stmt = $db->prepare($query);
$stmt->bindParam(':categorie', $categorie);
$stmt->execute();
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($produits)) {
    header('Location: catalogue.php');
    exit();
}

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produit_id = $_POST['produit_id'];
    $quantite = (int)$_POST['quantite'];

    try {
        $db->beginTransaction();

        // Vérifier le stock
        $query = "SELECT quantite FROM produits WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $produit_id]);
        $stock_disponible = $stmt->fetchColumn();

        if ($stock_disponible >= $quantite) {
            // Créer la commande
            $query = "INSERT INTO commandes (utilisateur_id, date_commande, statut) 
                      VALUES (:user_id, NOW(), 'en_attente')";
            $stmt = $db->prepare($query);
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $commande_id = $db->lastInsertId();

            // Ajouter le détail de la commande
            $query = "INSERT INTO commande_details (commande_id, produit_id, quantite) 
                      VALUES (:commande_id, :produit_id, :quantite)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':commande_id' => $commande_id,
                ':produit_id' => $produit_id,
                ':quantite' => $quantite
            ]);

            // Mettre à jour le stock
            $query = "UPDATE produits 
                      SET quantite = quantite - :quantite 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':quantite' => $quantite,
                ':id' => $produit_id
            ]);

            $db->commit();
            header('Location: mes-commandes.php?success=1');
            exit();
        } else {
            throw new Exception("Stock insuffisant");
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Une erreur est survenue : " . $e->getMessage();
    }
}

$titre_page = [
    'tshirt' => 'T-shirts Gris',
    'veste' => 'Vestes Haute Visibilité',
    'pantalon' => 'Pantalons Haute Visibilité'
][$categorie];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titre_page; ?> - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1><?php echo $titre_page; ?></h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="product-details">
            <div class="product-image">
                <img src="public/images/<?php echo $categorie; ?>-hv.jpg" 
                     alt="<?php echo $titre_page; ?>">
            </div>
            <div class="product-info">
                <p class="product-description"><?php echo htmlspecialchars($produits[0]['description']); ?></p>
                
                <form method="POST" class="order-form">
                    <div class="form-group">
                        <label for="taille">Taille:</label>
                        <select name="produit_id" id="taille" required>
                            <?php foreach ($produits as $produit): ?>
                                <option value="<?php echo $produit['id']; ?>" 
                                        data-stock="<?php echo $produit['quantite']; ?>">
                                    <?php echo $produit['taille']; ?> 
                                    (Stock: <?php echo $produit['quantite']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantite">Quantité:</label>
                        <input type="number" id="quantite" name="quantite" 
                               min="1" value="1" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Commander</button>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SNCF - Tous droits réservés</p>
        </div>
    </footer>

    <script>
    document.getElementById('taille').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const stock = parseInt(option.dataset.stock);
        const quantiteInput = document.getElementById('quantite');
        
        quantiteInput.max = stock;
        if (parseInt(quantiteInput.value) > stock) {
            quantiteInput.value = stock;
        }
    });
    </script>
</body>
</html>
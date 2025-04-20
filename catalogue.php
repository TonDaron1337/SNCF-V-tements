<?php
session_start();
require_once 'config/database.php';
require_once 'includes/image_utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$category = isset($_GET['category']) ? $_GET['category'] : '';

// Optimiser les images si nécessaire
$imagesDir = 'public/images/';
$optimizedDir = 'public/images/optimized/';

if (!file_exists($optimizedDir)) {
    mkdir($optimizedDir, 0777, true);
}

// Vérifier et optimiser les images de catégorie
$categories = ['tshirt', 'veste', 'pantalon'];
foreach ($categories as $cat) {
    $sourceImage = $imagesDir . $cat . '-hv.jpg';
    $optimizedImage = $optimizedDir . $cat . '-hv.jpg';
    
    if (file_exists($sourceImage) && (!file_exists($optimizedImage) || filemtime($sourceImage) > filemtime($optimizedImage))) {
        resizeImage($sourceImage, $optimizedImage, 800, 600);
    }
}

// Récupérer les produits groupés par catégorie
$query = "SELECT p.*, 
          GROUP_CONCAT(DISTINCT p2.taille) as tailles,
          GROUP_CONCAT(p2.quantite ORDER BY p2.taille) as quantites,
          GROUP_CONCAT(p2.id ORDER BY p2.taille) as ids
          FROM produits p
          JOIN produits p2 ON p.nom = p2.nom
          WHERE p.taille = 'M'";
if ($category) {
    $query .= " AND p.categorie = :category";
}
$query .= " GROUP BY p.nom, p.categorie";

$stmt = $db->prepare($query);
if ($category) {
    $stmt->bindParam(':category', $category);
}
$stmt->execute();
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Vérifier le stock disponible
        $query = "SELECT quantite FROM produits WHERE id = :produit_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':produit_id' => $_POST['produit_id']]);
        $stock_disponible = $stmt->fetchColumn();

        if ($stock_disponible < $_POST['quantite']) {
            throw new Exception("Stock insuffisant pour cette commande.");
        }
        
        // Créer la commande
        $query = "INSERT INTO commandes (utilisateur_id, date_commande, statut) 
                  VALUES (:user_id, NOW(), 'en_attente')";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $commande_id = $db->lastInsertId();
        
        // Ajouter les détails de la commande
        $query = "INSERT INTO commande_details (commande_id, produit_id, quantite) 
                  VALUES (:commande_id, :produit_id, :quantite)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':commande_id' => $commande_id,
            ':produit_id' => $_POST['produit_id'],
            ':quantite' => $_POST['quantite']
        ]);
        
        $db->commit();
        
        header('Location: mes-commandes.php?success=1');
        exit();
    } catch(Exception $e) {
        $db->rollBack();
        echo "<script>
                alert('Erreur : " . addslashes($e->getMessage()) . "');
                window.location.href = 'catalogue.php';
              </script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .catalogue-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background-color: #f8f9fa;
        }

        .product-image.loaded {
            animation: fadeIn 0.3s ease-in;
        }

        .category-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .category-filter {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary-color);
            border-radius: 30px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .category-filter.active,
        .category-filter:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .product-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .product-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        select, input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        select:focus, input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        .btn-commander {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-commander:hover {
            background-color: #000080;
            transform: translateY(-2px);
        }

        .stock-info {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-medium {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .stock-high {
            background-color: #d4edda;
            color: #155724;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <img src="public/images/sncf-logo.png" alt="SNCF Logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="catalogue.php" class="active">Catalogue</a></li>
                <li><a href="mes-commandes.php">Mes Commandes</a></li>
                <li><a href="parametres.php">Parametres</a></li>
                <?php if ($_SESSION['role'] === 'DPX' || $_SESSION['role'] === 'DUO'): ?>
                    <li><a href="gestion-utilisateurs.php">Gestion Utilisateurs</a></li>
                    <li><a href="gestion-commandes.php">Gestion Commandes</a></li>
                    <li><a href="historique-commandes.php">Historique Commandes</a></li>
                    <li><a href="gestion-stocks.php">Gestion Stocks</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="catalogue-container">
        <h1>Catalogue des vêtements</h1>

        <div class="category-filters">
            <a href="catalogue.php" class="category-filter <?php echo !$category ? 'active' : ''; ?>">Tous</a>
            <a href="catalogue.php?category=tshirt" class="category-filter <?php echo $category === 'tshirt' ? 'active' : ''; ?>">T-shirts Gris</a>
            <a href="catalogue.php?category=veste" class="category-filter <?php echo $category === 'veste' ? 'active' : ''; ?>">Vestes HV</a>
            <a href="catalogue.php?category=pantalon" class="category-filter <?php echo $category === 'pantalon' ? 'active' : ''; ?>">Pantalons HV</a>
        </div>

        <div class="products-grid">
            <?php foreach ($produits as $produit): 
                $tailles = explode(',', $produit['tailles']);
                $quantites = explode(',', $produit['quantites']);
                $ids = explode(',', $produit['ids']);
                $stocks = array_combine($tailles, $quantites);
                $produit_ids = array_combine($tailles, $ids);
            ?>
                <div class="product-card">
                    <img src="public/images/<?php echo $produit['categorie']; ?>-hv.jpg" 
                         alt="<?php echo htmlspecialchars($produit['nom']); ?>"
                         class="product-image"
                         width="800"
                         height="600"
                         loading="lazy"
                         onload="this.classList.add('loaded')">
                    
                    <h3 class="product-title"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($produit['description']); ?></p>
                    
                    <form method="POST" action="catalogue.php" class="order-form">
                        <div class="form-group">
                            <label for="taille_<?php echo $produit['id']; ?>">Taille:</label>
                            <select name="produit_id" 
                                    id="taille_<?php echo $produit['id']; ?>" 
                                    class="taille-select" 
                                    onchange="updateStock(this)">
                                <?php foreach ($tailles as $taille): ?>
                                    <option value="<?php echo $produit_ids[$taille]; ?>">
                                        <?php echo $taille; ?> 
                                        (Stock: <?php echo $stocks[$taille]; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantite_<?php echo $produit['id']; ?>">Quantité:</label>
                            <input type="number" 
                                   id="quantite_<?php echo $produit['id']; ?>" 
                                   name="quantite" 
                                   min="1" 
                                   value="1" 
                                   required>
                        </div>

                        <button type="submit" class="btn-commander">Commander</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
    function updateStock(select) {
        const option = select.options[select.selectedIndex];
        const productId = select.closest('.product-card').querySelector('.taille-select').id.split('_')[1];
        const quantiteInput = document.getElementById('quantite_' + productId);
        const stock = parseInt(option.textContent.match(/Stock: (\d+)/)[1]);
        
        quantiteInput.max = stock;
        if (parseInt(quantiteInput.value) > stock) {
            quantiteInput.value = stock;
        }

        // Mettre à jour l'indicateur de stock
        const stockLevel = select.closest('.product-card').querySelector('.stock-info');
        if (stockLevel) {
            stockLevel.className = 'stock-info ' + 
                (stock <= 10 ? 'stock-low' : stock <= 30 ? 'stock-medium' : 'stock-high');
            stockLevel.textContent = `Stock: ${stock}`;
        }
    }

    // Initialiser les stocks
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.taille-select').forEach(select => {
            updateStock(select);
        });

        // Ajouter des gestionnaires d'événements pour les images
        document.querySelectorAll('.product-image').forEach(img => {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        });
    });
    </script>
</body>
</html>
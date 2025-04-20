<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT c.*, cd.quantite, p.nom as produit_nom, p.taille, p.categorie 
          FROM commandes c 
          JOIN commande_details cd ON c.id = cd.commande_id 
          JOIN produits p ON cd.produit_id = p.id 
          WHERE c.utilisateur_id = :user_id 
          ORDER BY c.date_commande DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .en_attente {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #ffeeba;
        }

        .acceptee {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .refusee {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .order-details {
            padding: 1.5rem;
        }

        .product-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .product-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-details {
            flex: 1;
        }

        .product-details h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .product-details p {
            margin: 0.25rem 0;
            color: #666;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #000080;
            transform: translateY(-2px);
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
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="mes-commandes.php" class="active">Mes Commandes</a></li>
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

    <main class="orders-container">
        <h1>Mes Commandes</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success">Votre commande a été enregistrée avec succès.</div>
        <?php endif; ?>

        <?php if (empty($commandes)): ?>
            <div class="empty-state">
                <h2>Aucune commande</h2>
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="catalogue.php" class="btn btn-primary">Voir le catalogue</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($commandes as $commande): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-date">
                                Commande du <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </div>
                            <div class="order-status <?php echo $commande['statut']; ?>">
                                <?php
                                $status_labels = [
                                    'en_attente' => 'En attente',
                                    'acceptee' => 'Acceptée',
                                    'refusee' => 'Refusée'
                                ];
                                echo $status_labels[$commande['statut']] ?? $commande['statut'];
                                ?>
                            </div>
                        </div>
                        <div class="order-details">
                            <div class="product-info">
                                <img src="public/images/<?php echo $commande['categorie']; ?>-hv.jpg" 
                                     alt="<?php echo htmlspecialchars($commande['produit_nom']); ?>"
                                     class="product-thumbnail">
                                <div class="product-details">
                                    <h3><?php echo htmlspecialchars($commande['produit_nom']); ?></h3>
                                    <p>Taille: <?php echo htmlspecialchars($commande['taille']); ?></p>
                                    <p>Quantité: <?php echo htmlspecialchars($commande['quantite']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SNCF - Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>
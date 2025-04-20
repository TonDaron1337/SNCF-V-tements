<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNCF - Gestion des Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .main-title {
            text-align: center;
            color: var(--primary-color);
            margin: 2rem 0;
            font-size: 2rem;
            font-weight: 700;
            animation: fadeIn 0.5s ease-out;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            animation: slideUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .category-card:nth-child(1) { animation-delay: 0.1s; }
        .category-card:nth-child(2) { animation-delay: 0.2s; }
        .category-card:nth-child(3) { animation-delay: 0.3s; }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .category-card h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .category-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .category-card:hover img {
            transform: scale(1.05);
        }

        .category-card p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #000080;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer {
            margin-top: 4rem;
            padding: 2rem 0;
            background-color: #f8f9fa;
            text-align: center;
            color: #666;
        }

        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .main-title {
                font-size: 1.5rem;
                padding: 0 1rem;
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
                <li><a href="index.php" class="active">Accueil</a></li>
                <li><a href="catalogue.php">Catalogue</a></li>
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

    <main class="container">
        <h1 class="main-title">Bienvenue sur la plateforme de commande des vêtements SNCF</h1>

        <div class="categories-grid">
            <div class="category-card">
                <h2>T-shirts Gris</h2>
                <img src="public/images/tshirt-hv.jpg" alt="T-shirt Gris" loading="lazy">
                <p>T-shirts gris confortables pour le travail quotidien</p>
                <a href="catalogue.php?category=tshirt" class="btn">Voir les T-shirts</a>
            </div>

            <div class="category-card">
                <h2>Vestes HV</h2>
                <img src="public/images/veste-hv.jpg" alt="Veste Haute Visibilité" loading="lazy">
                <p>Vestes haute visibilité avec bandes réfléchissantes</p>
                <a href="catalogue.php?category=veste" class="btn">Voir les Vestes</a>
            </div>

            <div class="category-card">
                <h2>Pantalons HV</h2>
                <img src="public/images/pantalon-hv.jpg" alt="Pantalon Haute Visibilité" loading="lazy">
                <p>Pantalons haute visibilité avec bandes réfléchissantes</p>
                <a href="catalogue.php?category=pantalon" class="btn">Voir les Pantalons</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SNCF - Tous droits réservés</p>
        </div>
    </footer>
</body>
</html>
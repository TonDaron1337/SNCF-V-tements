<?php
session_start();
require_once 'config/database.php';
require_once 'includes/date_utils.php';
require_once 'includes/filters.php';

// Vérifier que l'utilisateur est DPX ou DUO
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DPX', 'DUO'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Récupération des filtres
$filters = getFilterParams();
$params = [];
$query = buildFilterQuery($filters, $params);

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur de requête : " . $e->getMessage());
    $commandes = [];
    $error_message = "Une erreur est survenue lors de la recherche.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Commandes - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .historique-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
            align-items: flex-end;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #495057;
        }

        .filter-input {
            padding: 0.75rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            min-width: 200px;
        }

        .filter-input:focus {
            border-color: #00005A;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        .btn-reset {
            padding: 0.75rem 1.5rem;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-reset svg {
            width: 16px;
            height: 16px;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #00005A;
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-acceptee {
            background-color: #28a745;
            color: white;
        }

        .status-refusee {
            background-color: #dc3545;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
            }

            .filter-input {
                width: 100%;
            }

            .btn-reset {
                width: 100%;
                justify-content: center;
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
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="mes-commandes.php">Mes Commandes</a></li>
                <li><a href="parametres.php">Parametres</a></li>
                <?php if ($_SESSION['role'] === 'DPX' || $_SESSION['role'] === 'DUO'): ?>
                    <li><a href="gestion-utilisateurs.php">Gestion Utilisateurs</a></li>
                    <li><a href="gestion-commandes.php">Gestion Commandes</a></li>
                    <li><a href="historique-commandes.php" class="active">Historique Commandes</a></li>
                    <li><a href="gestion-stocks.php">Gestion Stocks</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="historique-container">
            <h1>Historique des Commandes</h1>

            <div class="filters">
                <div class="filter-group">
                    <label class="filter-label" for="status-filter">Statut</label>
                    <select id="status-filter" class="filter-input" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="acceptee" <?php echo $filters['status'] === 'acceptee' ? 'selected' : ''; ?>>Acceptées</option>
                        <option value="refusee" <?php echo $filters['status'] === 'refusee' ? 'selected' : ''; ?>>Refusées</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label" for="date-filter">Date</label>
                    <input type="date" id="date-filter" class="filter-input" name="date" 
                           value="<?php echo $filters['date']; ?>">
                </div>

                <div class="filter-group">
                    <label class="filter-label" for="name-filter">Nom de l'agent</label>
                    <input type="text" id="name-filter" class="filter-input" name="name"
                           value="<?php echo $filters['name']; ?>" 
                           placeholder="Rechercher un agent...">
                </div>

                <div class="filter-group">
                    <label class="filter-label" for="commande-filter">N° Commande</label>
                    <input type="number" id="commande-filter" class="filter-input" name="commande"
                           value="<?php echo $filters['commande']; ?>" 
                           placeholder="N° de commande">
                </div>

                <button type="button" class="btn-reset" onclick="resetFilters()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Réinitialiser les filtres
                </button>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (empty($commandes)): ?>
                <div class="empty-state">
                    <h2>Aucune commande trouvée</h2>
                    <p>Modifiez vos critères de recherche pour voir plus de résultats.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Agent</th>
                                <th>Produit</th>
                                <th>Taille</th>
                                <th>Quantité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $commande): 
                                $dateInfo = formatDateFr($commande['date_commande']);
                            ?>
                                <tr>
                                    <td>#<?php echo $commande['id']; ?></td>
                                    <td>
                                        <div class="date-info">
                                            <span class="date"><?php echo $dateInfo['date']; ?></span>
                                            <span class="time"><?php echo $dateInfo['time']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['produit_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['taille']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['quantite']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $commande['statut']; ?>">
                                            <?php echo $commande['statut'] === 'acceptee' ? 'Acceptée' : 'Refusée'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener('change', applyFilters);
    });

    function applyFilters() {
        const status = document.getElementById('status-filter').value;
        const date = document.getElementById('date-filter').value;
        const name = document.getElementById('name-filter').value;
        const commande = document.getElementById('commande-filter').value;
        
        const url = new URL(window.location.href);
        
        // Nettoyer les paramètres existants
        url.searchParams.delete('status');
        url.searchParams.delete('date');
        url.searchParams.delete('name');
        url.searchParams.delete('commande');
        
        // Ajouter uniquement les paramètres non vides
        if (status) url.searchParams.set('status', status);
        if (date) url.searchParams.set('date', date);
        if (name) url.searchParams.set('name', name);
        if (commande) url.searchParams.set('commande', commande);
        
        window.location.href = url.toString();
    }

    function resetFilters() {
        // Réinitialiser les valeurs des champs
        document.getElementById('status-filter').value = '';
        document.getElementById('date-filter').value = '';
        document.getElementById('name-filter').value = '';
        document.getElementById('commande-filter').value = '';
        
        // Rediriger vers la page sans paramètres
        window.location.href = window.location.pathname;
    }
    </script>
</body>
</html>

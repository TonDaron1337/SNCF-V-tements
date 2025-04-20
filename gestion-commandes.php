<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DPX', 'DUO'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commande_id = $_POST['commande_id'];
    $nouveau_statut = $_POST['statut'];
    
    try {
        $db->beginTransaction();

        // Récupérer les détails de la commande
        $query = "SELECT cd.*, p.quantite as stock_actuel 
                  FROM commande_details cd 
                  JOIN produits p ON cd.produit_id = p.id 
                  WHERE cd.commande_id = :commande_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':commande_id' => $commande_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si la commande est acceptée, vérifier et mettre à jour le stock
        if ($nouveau_statut === 'acceptee') {
            foreach ($details as $detail) {
                if ($detail['stock_actuel'] < $detail['quantite']) {
                    throw new Exception("Stock insuffisant pour le produit ID " . $detail['produit_id']);
                }
                
                // Mettre à jour le stock
                $query = "UPDATE produits 
                          SET quantite = quantite - :quantite 
                          WHERE id = :produit_id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':quantite' => $detail['quantite'],
                    ':produit_id' => $detail['produit_id']
                ]);
            }
        }
        // Si la commande passe de acceptée à refusée ou en attente, remettre le stock
        elseif ($nouveau_statut !== 'acceptee') {
            $query = "SELECT statut FROM commandes WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $commande_id]);
            $ancien_statut = $stmt->fetchColumn();

            if ($ancien_statut === 'acceptee') {
                foreach ($details as $detail) {
                    // Remettre la quantité en stock
                    $query = "UPDATE produits 
                              SET quantite = quantite + :quantite 
                              WHERE id = :produit_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':quantite' => $detail['quantite'],
                        ':produit_id' => $detail['produit_id']
                    ]);
                }
            }
        }

        // Mettre à jour le statut de la commande
        $query = "UPDATE commandes SET statut = :statut WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':statut' => $nouveau_statut,
            ':id' => $commande_id
        ]);

        $db->commit();
        $success_message = 'Le statut de la commande a été mis à jour avec succès' . 
            ($nouveau_statut === 'acceptee' ? ' et le stock a été actualisé' : '');
    } catch(Exception $e) {
        $db->rollBack();
        $error_message = 'Erreur : ' . $e->getMessage();
    }
}

// Récupération uniquement des commandes en attente
$query = "SELECT c.*, u.nom, u.prenom, p.nom as produit_nom, p.taille, cd.quantite, p.quantite as stock_actuel
          FROM commandes c 
          JOIN utilisateurs u ON c.utilisateur_id = u.id 
          JOIN commande_details cd ON c.id = cd.commande_id 
          JOIN produits p ON cd.produit_id = p.id 
          WHERE c.statut = 'en_attente'
          ORDER BY c.date_commande DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        :root {
            --primary-color: #00005A;
            --secondary-color: #E40613;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 12px;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition-base: all 0.3s ease;
            --font-family: 'Roboto', sans-serif;
        }

        .orders-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin: 2rem auto;
            padding: 2rem;
            max-width: 1200px;
            animation: fadeIn 0.5s ease-out;
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

        .table-responsive {
            overflow-x: auto;
            margin: 2rem 0;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            padding: 1.25rem 1rem;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .table tbody tr {
            transition: var(--transition-base);
            animation: slideIn 0.5s ease-out forwards;
        }

        .table tbody tr:hover {
            background-color: var(--gray-100);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-en_attente {
            background-color: var(--warning-color);
            color: #856404;
            border: 2px solid #ffeeba;
        }

        .status-acceptee {
            background-color: var(--success-color);
            color: white;
            border: 2px solid #c3e6cb;
        }

        .status-refusee {
            background-color: var(--danger-color);
            color: white;
            border: 2px solid #f5c6cb;
        }

        .status-select {
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.95rem;
            min-width: 150px;
            cursor: pointer;
            transition: var(--transition-base);
            background-color: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .status-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            opacity: 0;
            transition: var(--transition-base);
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            z-index: 1001;
            width: 90%;
            max-width: 500px;
            opacity: 0;
            transition: var(--transition-base);
        }

        .modal.show,
        .modal-overlay.show {
            opacity: 1;
        }

        .modal.show {
            transform: translate(-50%, -50%) scale(1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .modal-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }

        .modal-body {
            padding: 2rem;
            font-size: 1.1rem;
            line-height: 1.6;
            color: var(--gray-700);
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-confirm {
            background-color: var(--success-color);
            color: white;
        }

        .btn-confirm:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background-color: var(--gray-500);
            color: white;
        }

        .btn-cancel:hover {
            background-color: var(--gray-600);
            transform: translateY(-2px);
        }

        .message {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stock-warning {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--warning-color);
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .orders-container {
                margin: 1rem;
                padding: 1rem;
            }

            .table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .table td, 
            .table th {
                padding: 0.75rem;
            }

            .modal {
                width: 95%;
                margin: 1rem;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1rem;
            }

            .status-select {
                min-width: 120px;
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
                    <li><a href="gestion-commandes.php" class="active">Gestion Commandes</a></li>
                    <li><a href="historique-commandes.php">Historique Commandes</a></li>
                    <li><a href="gestion-stocks.php">Gestion Stocks</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="orders-container">
            <h1>Gestion des Commandes</h1>

            <?php if (isset($success_message)): ?>
                <div class="message success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($commandes)): ?>
                <div class="empty-state">
                    <h2>Aucune commande en attente</h2>
                    <p>Les nouvelles commandes apparaîtront ici.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Agent</th>
                                <th>Produit</th>
                                <th>Taille</th>
                                <th>Quantité</th>
                                <th>Stock Disponible</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $commande): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                                    <td><?php echo htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['produit_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['taille']); ?></td>
                                    <td><?php echo htmlspecialchars($commande['quantite']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($commande['stock_actuel']); ?>
                                        <?php if ($commande['stock_actuel'] < $commande['quantite']): ?>
                                            <div class="stock-warning">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                                </svg>
                                                Stock insuffisant
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $commande['statut']; ?>">
                                            <?php
                                            $status_labels = [
                                                'en_attente' => 'En attente',
                                                'acceptee' => 'Acceptée',
                                                'refusee' => 'Refusée'
                                            ];
                                            echo $status_labels[$commande['statut']] ?? $commande['statut'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="gestion-commandes.php" class="status-form" id="form-<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <select name="statut" class="status-select" onchange="showStatusConfirmation(<?php echo $commande['id']; ?>, this.value)">
                                                <option value="en_attente" <?php echo $commande['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                <option value="acceptee" <?php echo $commande['statut'] === 'acceptee' ? 'selected' : ''; ?>>Accepter</option>
                                                <option value="refusee" <?php echo $commande['statut'] === 'refusee' ? 'selected' : ''; ?>>Refuser</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de confirmation -->
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal" id="confirmationModal">
        <div class="modal-header">
            <h2 class="modal-title">Confirmation du changement de statut</h2>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn btn-cancel" onclick="hideModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Annuler
            </button>
            <button class="modal-btn btn-confirm" id="confirmButton">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Confirmer
            </button>
        </div>
    </div>

    <script>
    let currentForm = null;
    let currentStatus = null;

    function showStatusConfirmation(commandeId, newStatus) {
        if (newStatus === 'en_attente') return;

        currentForm = document.getElementById('form-' + commandeId);
        currentStatus = newStatus;

        const modal = document.getElementById('confirmationModal');
        const overlay = document.getElementById('modalOverlay');
        const message = document.getElementById('modalMessage');
        const confirmButton = document.getElementById('confirmButton');

        let messageText = '';
        if (newStatus === 'acceptee') {
            messageText = 'Êtes-vous sûr de vouloir accepter cette commande ? Le stock sera automatiquement mis à jour.';
            confirmButton.style.backgroundColor = '#28a745';
        } else if (newStatus === 'refusee') {
            messageText = 'Êtes-vous sûr de vouloir refuser cette commande ?';
            confirmButton.style.backgroundColor = '#dc3545';
        }

        message.textContent = messageText;
        modal.style.display = 'block';
        overlay.style.display = 'block';
        
        requestAnimationFrame(() => {
            modal.classList.add('show');
            overlay.classList.add('show');
        });
    }

    function hideModal() {
        const modal = document.getElementById('confirmationModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.remove('show');
        overlay.classList.remove('show');
        
        setTimeout(() => {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);

        if (currentForm) {
            currentForm.statut.value = 'en_attente';
        }
    }

    document.getElementById('confirmButton').addEventListener('click', function() {
        if (currentForm) {
            currentForm.submit();
        }
        hideModal();
    });

    document.getElementById('modalOverlay').addEventListener('click', hideModal);

    document.getElementById('confirmationModal').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Fermer la modal avec la touche Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideModal();
        }
    });
    </script>
</body>
</html>
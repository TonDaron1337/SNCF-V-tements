<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DPX', 'DUO'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'];
    
    try {
        $db->beginTransaction();
        
        // Supprimer les commandes liées à l'utilisateur
        $query = "DELETE FROM commande_details WHERE commande_id IN (SELECT id FROM commandes WHERE utilisateur_id = :user_id)";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        $query = "DELETE FROM commandes WHERE utilisateur_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        // Supprimer l'utilisateur
        $query = "DELETE FROM utilisateurs WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        $db->commit();
        $success_message = "L'utilisateur a été supprimé avec succès.";
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Traitement de la modification du rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    try {
        $query = "UPDATE utilisateurs SET role = :role WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':role' => $role,
            ':id' => $user_id
        ]);
        $success_message = "Le rôle a été mis à jour avec succès.";
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour du rôle : " . $e->getMessage();
    }
}

// Récupération des utilisateurs
$query = "SELECT * FROM utilisateurs ORDER BY nom, prenom";
$stmt = $db->prepare($query);
$stmt->execute();
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .users-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease-out;
        }

        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
        }

        .users-table th,
        .users-table td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .users-table tbody tr {
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease-out forwards;
        }

        .users-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .role-select {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: white;
            font-size: 0.95rem;
            min-width: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1001;
            width: 90%;
            max-width: 500px;
            opacity: 0;
            transition: all 0.3s ease;
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
            border-bottom: 2px solid #eee;
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
            color: #666;
        }

        .modal-body span {
            color: #333;
            font-weight: 500;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-confirm {
            background-color: #dc3545;
            color: white;
        }

        .btn-confirm:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--primary-color);
        }

        .user-email {
            font-size: 0.9rem;
            color: #666;
        }

        .user-cp {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .users-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .users-container {
                padding: 1rem;
                margin: 1rem;
            }

            .btn-delete {
                padding: 0.5rem 1rem;
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
                    <li><a href="gestion-utilisateurs.php" class="active">Gestion Utilisateurs</a></li>
                    <li><a href="gestion-commandes.php">Gestion Commandes</a></li>
                    <li><a href="historique-commandes.php">Historique Commandes</a></li>
                    <li><a href="gestion-stocks.php">Gestion Stocks</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="users-container">
            <h1>Gestion des Utilisateurs</h1>

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

            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Numéro CP</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <span class="user-name">
                                            <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-cp"><?php echo htmlspecialchars($utilisateur['numero_cp']); ?></span>
                                </td>
                                <td>
                                    <span class="user-email"><?php echo htmlspecialchars($utilisateur['email']); ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="gestion-utilisateurs.php" class="inline-form">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $utilisateur['id']; ?>">
                                        <select name="role" class="role-select" onchange="this.form.submit()">
                                            <option value="Opérateur" <?php echo $utilisateur['role'] === 'Opérateur' ? 'selected' : ''; ?>>Opérateur</option>
                                            <option value="DPX" <?php echo $utilisateur['role'] === 'DPX' ? 'selected' : ''; ?>>DPX</option>
                                            <option value="DUO" <?php echo $utilisateur['role'] === 'DUO' ? 'selected' : ''; ?>>DUO</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn-delete" 
                                            onclick="showDeleteConfirmation(<?php echo $utilisateur['id']; ?>, '<?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirmer la suppression</h2>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <span id="userName"></span> ?</p>
                <p>Cette action est irréversible et supprimera également toutes les commandes associées à cet utilisateur.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="gestion-utilisateurs.php" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" class="btn-modal btn-cancel" onclick="hideDeleteConfirmation()">Annuler</button>
                    <button type="submit" class="btn-modal btn-confirm">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Overlay pour la modal -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <script>
    function showDeleteConfirmation(userId, userName) {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('modalOverlay');
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('userName').textContent = userName;
        
        modal.style.display = 'block';
        overlay.style.display = 'block';
        
        requestAnimationFrame(() => {
            modal.classList.add('show');
            overlay.classList.add('show');
        });
    }

    function hideDeleteConfirmation() {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.remove('show');
        overlay.classList.remove('show');
        
        setTimeout(() => {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
    }

    // Fermer la modal en cliquant sur l'overlay
    document.getElementById('modalOverlay').addEventListener('click', hideDeleteConfirmation);

    // Empêcher la propagation du clic depuis la modal
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Fermer la modal avec la touche Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideDeleteConfirmation();
        }
    });
    </script>
</body>
</html>
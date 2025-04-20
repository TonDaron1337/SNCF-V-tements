<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Récupération des informations actuelles de l'utilisateur
$query = "SELECT * FROM utilisateurs WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Validation du numéro CP
        if (!preg_match('/^[0-9]{7}[A-Z]$/', $_POST['numero_cp'])) {
            throw new Exception("Le numéro CP doit contenir 7 chiffres suivis d'une lettre majuscule.");
        }

        // Vérification si le numéro CP existe déjà pour un autre utilisateur
        $query = "SELECT id FROM utilisateurs WHERE numero_cp = :numero_cp AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':numero_cp' => $_POST['numero_cp'],
            ':id' => $_SESSION['user_id']
        ]);
        if ($stmt->fetch()) {
            throw new Exception("Ce numéro CP est déjà utilisé.");
        }

        // Vérification si l'email existe déjà pour un autre utilisateur
        $query = "SELECT id FROM utilisateurs WHERE email = :email AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':email' => $_POST['email'],
            ':id' => $_SESSION['user_id']
        ]);
        if ($stmt->fetch()) {
            throw new Exception("Cette adresse email est déjà utilisée.");
        }

        // Préparation de la requête de mise à jour
        $query = "UPDATE utilisateurs SET 
                  numero_cp = :numero_cp,
                  email = :email,
                  nom = :nom,
                  prenom = :prenom";

        // Si un nouveau mot de passe est fourni
        if (!empty($_POST['new_password'])) {
            // Vérification de l'ancien mot de passe
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception("Le mot de passe actuel est incorrect.");
            }
            
            // Validation du nouveau mot de passe
            if (strlen($_POST['new_password']) < 8) {
                throw new Exception("Le nouveau mot de passe doit contenir au moins 8 caractères.");
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
            }

            $query .= ", password = :password";
        }

        $query .= " WHERE id = :id";
        $stmt = $db->prepare($query);

        // Paramètres de base
        $params = [
            ':numero_cp' => $_POST['numero_cp'],
            ':email' => $_POST['email'],
            ':nom' => $_POST['nom'],
            ':prenom' => $_POST['prenom'],
            ':id' => $_SESSION['user_id']
        ];

        // Ajout du mot de passe si nécessaire
        if (!empty($_POST['new_password'])) {
            $params[':password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        $stmt->execute($params);
        $db->commit();

        $success_message = "Vos informations ont été mises à jour avec succès.";
        
        // Mise à jour des informations en session
        $_SESSION['numero_cp'] = $_POST['numero_cp'];
        
        // Recharger les informations de l'utilisateur
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $db->rollBack();
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .settings-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
            animation: fadeIn 0.5s ease-out;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        .help-text {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.5rem;
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

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-footer {
            margin-top: 2rem;
            text-align: right;
        }

        .btn-save {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: #000080;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background-color: #eee;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-weak { background-color: #dc3545; width: 33.33%; }
        .strength-medium { background-color: #ffc107; width: 66.66%; }
        .strength-strong { background-color: #28a745; width: 100%; }
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
                <li><a href="parametres.php" class="active">Parametres</a></li>
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
        <div class="settings-container">
            <h1>Paramètres du compte</h1>

            <?php if ($success_message): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="parametres.php" id="settings-form">
                <div class="form-section">
                    <h2>Informations personnelles</h2>
                    
                    <div class="form-group">
                        <label for="numero_cp">Numéro CP</label>
                        <input type="text" id="numero_cp" name="numero_cp" 
                               value="<?php echo htmlspecialchars($user['numero_cp']); ?>"
                               pattern="[0-9]{7}[A-Z]" required>
                        <div class="help-text">Format: 7 chiffres suivis d'une lettre majuscule</div>
                    </div>

                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" 
                               value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" 
                               value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Modification du mot de passe</h2>
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" minlength="8">
                        <div class="password-strength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="help-text">8 caractères minimum</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn-save">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('settings-form');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const currentPasswordInput = document.getElementById('current_password');
        const strengthBar = document.querySelector('.password-strength-bar');

        // Fonction pour vérifier la force du mot de passe
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength === 1) strengthBar.classList.add('strength-weak');
            else if (strength === 2) strengthBar.classList.add('strength-medium');
            else if (strength === 3) strengthBar.classList.add('strength-strong');
        }

        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        form.addEventListener('submit', function(e) {
            // Si un nouveau mot de passe est saisi
            if (newPasswordInput.value) {
                // Vérifier que le mot de passe actuel est saisi
                if (!currentPasswordInput.value) {
                    e.preventDefault();
                    alert('Veuillez saisir votre mot de passe actuel.');
                    currentPasswordInput.focus();
                    return;
                }

                // Vérifier que les nouveaux mots de passe correspondent
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert('Les nouveaux mots de passe ne correspondent pas.');
                    confirmPasswordInput.focus();
                    return;
                }

                // Vérifier la longueur minimale
                if (newPasswordInput.value.length < 8) {
                    e.preventDefault();
                    alert('Le nouveau mot de passe doit contenir au moins 8 caractères.');
                    newPasswordInput.focus();
                    return;
                }
            }
        });
    });
    </script>
</body>
</html>
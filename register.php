<?php
session_start();
require_once 'config/database.php';

$errorMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Récupération des données POST
    $numero_cp = trim($_POST['numero_cp']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    
    // Validation des champs
    if (empty($numero_cp) || !preg_match('/^[0-9]{7}[A-Z]$/', $numero_cp)) {
        $errorMessages[] = "Le numéro CP doit contenir 7 chiffres suivis d'une lettre majuscule.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@sncf\.fr$/', $email)) {
        $errorMessages[] = "L'adresse e-mail doit être une adresse valide de type @sncf.fr.";
    }
    
    if (empty($password) || strlen($password) < 8) {
        $errorMessages[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    
    if ($password !== $password_confirm) {
        $errorMessages[] = "Les mots de passe ne correspondent pas.";
    }
    
    if (empty($nom) || empty($prenom)) {
        $errorMessages[] = "Le nom et le prénom sont obligatoires.";
    }
    
    // Vérification du numéro CP unique dans la base de données
    if (empty($errorMessages)) {
        $query = "SELECT id FROM utilisateurs WHERE numero_cp = :numero_cp";
        $stmt = $db->prepare($query);
        $stmt->execute([':numero_cp' => $numero_cp]);
        if ($stmt->fetch()) {
            $errorMessages[] = "Ce numéro CP est déjà utilisé.";
        }
    }

    // Insertion en base de données si aucune erreur
    if (empty($errorMessages)) {
        $query = "INSERT INTO utilisateurs (numero_cp, email, password, nom, prenom, role) 
                  VALUES (:numero_cp, :email, :password, :nom, :prenom, 'Opérateur')";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([
                ':numero_cp' => $numero_cp,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':nom' => $nom,
                ':prenom' => $prenom
            ]);
            header('Location: login.php?registered=1');
            exit();
        } catch (PDOException $e) {
            $errorMessages[] = "Une erreur est survenue lors de l'inscription.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideUp 0.5s ease-out;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            height: 60px;
            margin-bottom: 1rem;
            animation: fadeIn 0.5s ease-out;
        }

        .auth-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0;
            animation: fadeIn 0.5s ease-out 0.2s both;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .error ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease-out;
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

        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #000080;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            animation: fadeIn 0.5s ease-out 0.4s both;
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
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
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="public/images/sncf-logo.png" alt="SNCF Logo" class="auth-logo">
            <h1>Inscription</h1>
        </div>

        <?php if (!empty($errorMessages)): ?>
            <div class="message error">
                <ul>
                    <?php foreach ($errorMessages as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form" id="register-form">
            <div class="form-group">
                <label for="numero_cp">Numéro CP</label>
                <input type="text" id="numero_cp" name="numero_cp" 
                       pattern="[0-9]{7}[A-Z]" placeholder="1234567A" required
                       value="<?php echo isset($_POST['numero_cp']) ? htmlspecialchars($_POST['numero_cp']) : ''; ?>">
                <div class="help-text">Format: 7 chiffres suivis d'une lettre majuscule</div>
            </div>

            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required
                       value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required
                       value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="prenom.nom@sncf.fr"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="help-text">Utilisez votre adresse email SNCF</div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="8">
                <div class="password-strength">
                    <div class="password-strength-bar"></div>
                </div>
                <div class="help-text">Minimum 8 caractères</div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>

        <div class="auth-footer">
            <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('register-form');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const strengthBar = document.querySelector('.password-strength-bar');
        
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

        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                confirmInput.focus();
            }
        });

        // Format automatique du numéro CP
        const numeroCpInput = document.getElementById('numero_cp');
        numeroCpInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9A-Z]/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            if (value.length > 7) {
                const numbers = value.slice(0, 7);
                const letter = value.slice(7).toUpperCase();
                value = numbers + letter;
            }
            e.target.value = value;
        });
    });
    </script>
</body>
</html>
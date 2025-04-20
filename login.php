<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $numero_cp = $_POST['numero_cp'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM utilisateurs WHERE numero_cp = :numero_cp";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':numero_cp', $numero_cp);
    $stmt->execute();
    
    if ($user = $stmt->fetch()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit();
        }
    }
    $error = "Numéro CP ou mot de passe incorrect";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 4rem auto;
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

        .password-toggle {
            position: relative;
        }

        .password-toggle input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            padding: 5px;
            font-size: 0.9rem;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <img src="public/images/sncf-logo.png" alt="SNCF Logo" class="auth-logo">
            <h1>Connexion</h1>
        </div>

        <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
            <div class="message success">
                Votre inscription a été réussie. Vous pouvez maintenant vous connecter.
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form" id="login-form">
            <div class="form-group">
                <label for="numero_cp">Numéro CP</label>
                <input type="text" id="numero_cp" name="numero_cp" 
                       pattern="[0-9]{7}[A-Z]" placeholder="1234567A" required
                       value="<?php echo isset($_POST['numero_cp']) ? htmlspecialchars($_POST['numero_cp']) : ''; ?>">
                <div class="help-text">Format: 7 chiffres suivis d'une lettre majuscule</div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        Afficher
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>

        <div class="auth-footer">
            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.querySelector('.toggle-password');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.textContent = 'Masquer';
        } else {
            passwordInput.type = 'password';
            toggleButton.textContent = 'Afficher';
        }
    }
    </script>
</body>
</html>
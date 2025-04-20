<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - SNCF Vêtements</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 4rem auto;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            line-height: 1;
        }

        .error-message {
            font-size: 1.5rem;
            color: #666;
            margin: 1rem 0 2rem;
        }

        .error-image {
            max-width: 300px;
            margin: 2rem auto;
        }

        .btn-home {
            display: inline-block;
            padding: 1rem 2rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            background-color: #000080;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <p class="error-message">La page que vous recherchez n'existe pas.</p>
        <img src="/public/images/sncf-logo.png" alt="SNCF Logo" class="error-image">
        <div>
            <a href="/" class="btn-home">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DPX', 'DUO'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Traitement de l'ajout/modification de produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $nom = $_POST['nom'];
        $description = $_POST['description'];
        $categorie = $_POST['categorie'];
        $taille = $_POST['taille'];
        $quantite = (int)$_POST['quantite'];
        
        // Gestion de l'upload d'image
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/' . $new_filename;
                }
            }
        }

        // Vérifier si le produit existe déjà
        $query = "SELECT id FROM produits WHERE categorie = :categorie AND taille = :taille";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':categorie' => $categorie,
            ':taille' => $taille
        ]);
        $produit = $stmt->fetch();

        if ($produit) {
            // Mettre à jour le produit existant
            $query = "UPDATE produits SET 
                      nom = :nom,
                      description = :description,
                      quantite = :quantite" .
                      ($image_url ? ", image_url = :image_url" : "") .
                      " WHERE id = :id";
            
            $params = [
                ':nom' => $nom,
                ':description' => $description,
                ':quantite' => $quantite,
                ':id' => $produit['id']
            ];
            
            if ($image_url) {
                $params[':image_url'] = $image_url;
            }
            
            $success_message = "Le produit a été mis à jour avec succès.";
        } else {
            // Créer un nouveau produit
            $query = "INSERT INTO produits (nom, description, categorie, taille, quantite, image_url) 
                      VALUES (:nom, :description, :categorie, :taille, :quantite, :image_url)";
            
            $params = [
                ':nom' => $nom,
                ':description' => $description,
                ':categorie' => $categorie,
                ':taille' => $taille,
                ':quantite' => $quantite,
                ':image_url' => $image_url
            ];
            
            $success_message = "Le produit a été ajouté avec succès.";
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Récupération des produits
$query = "SELECT * FROM produits ORDER BY categorie, taille";
$stmt = $db->prepare($query);
$stmt->execute();
$produits = $stmt->fetchAll();

// Grouper les produits par catégorie
$produits_par_categorie = [];
foreach ($produits as $produit) {
    $produits_par_categorie[$produit['categorie']][] = $produit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stocks - SNCF Vêtements</title>
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

        .stock-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            animation: fadeIn 0.5s ease-out;
        }

        .management-form {
            background: var(--gray-100);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition-base);
            background-color: white;
        }

        .form-group input[type="file"] {
            padding: 0.75rem;
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 90, 0.1);
        }

        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .stock-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            transition: var(--transition-base);
            animation: slideUp 0.5s ease-out forwards;
            opacity: 0;
        }

        .stock-card:nth-child(1) { animation-delay: 0.1s; }
        .stock-card:nth-child(2) { animation-delay: 0.2s; }
        .stock-card:nth-child(3) { animation-delay: 0.3s; }

        .stock-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stock-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .stock-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .stock-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-base);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #000080;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary {
            background-color: var(--gray-600);
            color: white;
        }

        .btn-secondary:hover {
            background-color: var(--gray-700);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .stock-level {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-level-low {
            background-color: var(--danger-color);
            color: white;
        }

        .stock-level-medium {
            background-color: var(--warning-color);
            color: #856404;
        }

        .stock-level-high {
            background-color: var(--success-color);
            color: white;
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
                transform: translateY(20px);
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

        @media (max-width: 768px) {
            .stock-container {
                margin: 1rem;
                padding: 1rem;
            }

            .management-form {
                padding: 1rem;
            }

            .stock-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .stock-info {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }

        .file-upload-wrapper {
            position: relative;
            margin-top: 0.5rem;
        }

        .file-upload-wrapper::before {
            content: 'Choisir un fichier';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-100);
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition-base);
        }

        .file-upload-wrapper:hover::before {
            background: var(--gray-200);
            border-color: var(--primary-color);
        }

        input[type="file"] {
            opacity: 0;
            position: relative;
            z-index: 2;
            cursor: pointer;
            height: 100px;
            width: 100%;
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
                    <li><a href="historique-commandes.php">Historique Commandes</a></li>
                    <li><a href="gestion-stocks.php" class="active">Gestion Stocks</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn-logout">Déconnexion</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <div class="stock-container">
            <h1>Gestion des Stocks</h1>

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

            <div class="management-form">
                <h2>Ajouter/Modifier un produit</h2>
                <form method="POST" enctype="multipart/form-data" id="product-form">
                    <div class="form-group">
                        <label for="nom">Nom du produit</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="categorie">Catégorie</label>
                        <select id="categorie" name="categorie" required>
                            <option value="tshirt">T-shirt Gris</option>
                            <option value="veste">Veste HV</option>
                            <option value="pantalon">Pantalon HV</option>
                            <option value="chaussures">Chaussures de sécurité</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="taille">Taille</label>
                        <select id="taille" name="taille" required>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="2XL">2XL</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantite">Quantité</label>
                        <input type="number" id="quantite" name="quantite" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="image">Image</label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Enregistrer
                    </button>
                </form>
            </div>

            <div class="stock-grid">
                <?php foreach ($produits_par_categorie as $categorie => $produits_categorie): ?>
                    <div class="stock-card">
                        <h3><?php 
                            $categories = [
                                'tshirt' => 'T-shirts Gris',
                                'veste' => 'Vestes HV',
                                'pantalon' => 'Pantalons HV'
                            ];
                            echo $categories[$categorie] ?? ucfirst($categorie);
                        ?></h3>
                        <?php foreach ($produits_categorie as $produit): ?>
                            <div class="stock-info">
                                <span>Taille <?php echo htmlspecialchars($produit['taille']); ?></span>
                                <span class="stock-level <?php 
                                    if ($produit['quantite'] <= 10) echo 'stock-level-low';
                                    elseif ($produit['quantite'] <= 30) echo 'stock-level-medium';
                                    else echo 'stock-level-high';
                                ?>">
                                    <?php if ($produit['quantite'] <= 10): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                            <line x1="12" y1="9" x2="12" y2="13"></line>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        </svg>
                                    <?php endif; ?>
                                    Stock: <?php echo htmlspecialchars($produit['quantite']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <div class="stock-actions">
                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($produits_categorie[0])); ?>)" 
                                    class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Modifier
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
    function editProduct(produit) {
        document.getElementById('nom').value = produit.nom;
        document.getElementById('description').value = produit.description;
        document.getElementById('categorie').value = produit.categorie;
        document.getElementById('taille').value = produit.taille;
        document.getElementById('quantite').value = produit.quantite;
        
        document.querySelector('.management-form').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }

    // Gestion de l'upload de fichier
    document.getElementById('image').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            e.target.parentElement.setAttribute('data-file', fileName);
        }
    });

    // Validation du formulaire
    document.getElementById('product-form').addEventListener('submit', function(e) {
        const quantite = document.getElementById('quantite').value;
        if (quantite < 0) {
            e.preventDefault();
            alert('La quantité ne peut pas être négative.');
            return;
        }
    });
    </script>
</body>
</html> 
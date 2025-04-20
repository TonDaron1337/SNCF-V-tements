<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav>
    <div class="logo">SNCF Vêtements</div>
    <ul>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="catalogue.php">Catalogue</a></li>
            <li><a href="mes-commandes.php">Mes Commandes</a></li>
            <?php if ($_SESSION['role'] === 'DPX' || $_SESSION['role'] === 'DUO'): ?>
                <li><a href="gestion-utilisateurs.php">Gestion Utilisateurs</a></li>
                <li><a href="gestion-commandes.php">Gestion Commandes</a></li>
                <li><a href="gestion-stocks.php">Gestion Stocks</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php">Inscription</a></li>
        <?php endif; ?>
    </ul>
</nav>
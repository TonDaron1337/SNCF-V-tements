<?php
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($roles) {
    requireAuth();
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: index.php');
        exit();
    }
}
<?php
function redirect($url) {
    // Ajouter l'URL de base si l'URL ne commence pas par http
    if (!preg_match('/^http(s)?:\/\//', $url)) {
        $url = BASE_URL . $url;
    }
    header("Location: $url");
    exit();
}

function getCurrentUrl() {
    $protocol = 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $uri;
}

function getBasePath() {
    return '/hugo/sncf-vetements';
}

function getPublicUrl($path) {
    return BASE_URL . getBasePath() . '/public/' . ltrim($path, '/');
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function getPostValue($key, $default = '') {
    return $_POST[$key] ?? $default;
}

function getQueryValue($key, $default = '') {
    return $_GET[$key] ?? $default;
}

function getCurrentUser() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function requireRole($roles) {
    if (!isAuthenticated()) {
        redirect('/login');
    }
    
    if (!in_array(getCurrentRole(), (array)$roles)) {
        redirect('/');
    }
}
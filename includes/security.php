<?php
// includes/security.php - Protection CSRF et XSS

// Générer un token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Protection contre les attaques XSS (déjà gérée avec sanitize)
// Protection contre les injections SQL (gérée par PDO)

// Limiter les tentatives de connexion
function checkLoginAttempts($email) {
    $db = Database::getInstance();
    $attempts = $db->fetch(
        "SELECT COUNT(*) as count FROM login_attempts 
         WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
        [$email]
    );
    return $attempts ? $attempts['count'] : 0;
}

function logLoginAttempt($email, $success) {
    $db = Database::getInstance();
    $db->insert(
        "INSERT INTO login_attempts (email, success, ip_address) VALUES (?, ?, ?)",
        [$email, $success ? 1 : 0, $_SERVER['REMOTE_ADDR'] ?? null]
    );
}

// Ajouter le token CSRF dans tous les formulaires
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
?>
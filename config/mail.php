<?php
// config/mail.php - Configuration EMAIL CORRIGÉE

// ============================================
// ✅ VÉRIFIER AVANT DE DÉFINIR
// ============================================

if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', 'ralijaonanirinarisoa@gmail.com');
}

if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', 'urqs fkzp fngw dveo');
}

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM', 'ralijaonanirinarisoa@gmail.com');
}

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', 'smtp.gmail.com');
}

if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', 587);
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'Plateforme de Ticketing - Spider Madagascar');
}

if (!defined('MAIL_ENCRYPTION')) {
    define('MAIL_ENCRYPTION', 'tls');
}

if (!defined('MAIL_ENABLED')) {
    define('MAIL_ENABLED', true);
}

if (!defined('MAIL_DEBUG')) {
    define('MAIL_DEBUG', true);
}

if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/ticketing_plateform');
}

// Vérifier que MAIL_FROM est identique à MAIL_USERNAME
if (MAIL_FROM !== MAIL_USERNAME) {
    define('MAIL_FROM', MAIL_USERNAME);
}

// Vérifier que les identifiants sont présents
if (empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
    define('MAIL_ENABLED', false);
    error_log("⚠️ MAIL_DISABLED: Identifiants email vides");
}

error_log("📧 Mail configuré: " . MAIL_HOST . ":" . MAIL_PORT);
?>
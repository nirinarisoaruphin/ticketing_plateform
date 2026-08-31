<?php
// config/mail.php - Configuration CORRIGÉE avec mot de passe d'application

// ============================================
// ✅ REMPLACEZ CES VALEURS PAR LES VÔTRES
// ============================================

// ⚠️ UTILISEZ VOS IDENTIFIANTS GMAIL
define('MAIL_USERNAME', 'ralijaonanirinarisoa@gmail.com');  // Votre email

// ⚠️ UTILISEZ LE MOT DE PASSE D'APPLICATION (16 caractères)
// Créé sur : https://myaccount.google.com/apppasswords
define('MAIL_PASSWORD', 'urqs fkzp fngw dveo');  // ← REMPLACER PAR VOTRE MOT DE PASSE D'APPLICATION

// ⚠️ DOIT ÊTRE IDENTIQUE À MAIL_USERNAME
define('MAIL_FROM', 'ralijaonanirinarisoa@gmail.com');

// ============================================
// ✅ NE PAS MODIFIER CES VALEURS
// ============================================

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
    define('MAIL_ENABLED', true);  // ✅ DOIT ÊTRE TRUE
}
if (!defined('MAIL_DEBUG')) {
    define('MAIL_DEBUG', true);    // ✅ Mettre à true pour voir les erreurs
}
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/ticketing_plateform');
}

// ============================================
// ✅ VÉRIFICATIONS AUTO
// ============================================

// MAIL_FROM doit être identique à MAIL_USERNAME
if (MAIL_FROM !== MAIL_USERNAME) {
    // Redéfinir MAIL_FROM
    if (!defined('MAIL_FROM')) {
        define('MAIL_FROM', MAIL_USERNAME);
    }
}

// Vérifier que les identifiants sont présents
if (empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
    define('MAIL_ENABLED', false);
    error_log("⚠️ MAIL_DISABLED: Identifiants email vides");
}

error_log("📧 Mail configuré: " . MAIL_HOST . ":" . MAIL_PORT);
?>
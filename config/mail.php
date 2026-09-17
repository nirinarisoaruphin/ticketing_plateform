<?php
// config/mail.php - Configuration EMAIL CORRIGÉE

// ============================================
// VÉRIFIER AVANT DE DÉFINIR
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

// DÉBOGAGE SMTP
// À true, PHPMailer écrit tout le dialogue SMTP dans error_log à chaque
// email : très verbeux et coûteux en E/S. À laisser à false en production,
// à passer temporairement à true seulement pour diagnostiquer un problème.
if (!defined('MAIL_DEBUG')) {
    define('MAIL_DEBUG', false);
}

// FILE D'ATTENTE DES EMAILS
//
// false = TRAITEMENT NORMAL (comportement d'origine, actif ici) :
//         les emails partent immédiatement pendant la requête, à la
//         création du ticket et à chaque action. Aucun cron nécessaire.
//
// true  = envoi différé en arrière-plan via cron/email_sender.php.
//         La page répond immédiatement, mais le cron DOIT être installé,
//         sinon les emails restent en attente dans la table email_queue.
if (!defined('MAIL_QUEUE_ENABLED')) {
    define('MAIL_QUEUE_ENABLED', false);
}

// Délai maximum d'attente d'une connexion SMTP, en secondes.
if (!defined('MAIL_TIMEOUT')) {
    define('MAIL_TIMEOUT', 15);
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
    error_log("MAIL_DISABLED: Identifiants email vides");
}

if (MAIL_DEBUG) {
    error_log("Mail configuré: " . MAIL_HOST . ":" . MAIL_PORT);
}
?>
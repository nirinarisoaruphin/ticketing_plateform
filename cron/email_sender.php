<?php
// cron/email_sender.php - Envoi des emails en file d'attente
//
// À planifier toutes les minutes, par exemple :
//   * * * * * php /chemin/vers/ticketing_plateform/cron/email_sender.php
//
// Tous les emails mis en file par EmailQueue sont envoyés ici, en
// arrière-plan, avec UNE SEULE connexion SMTP pour tout le lot
// (SMTPKeepAlive). La création de ticket et les actions ne sont donc
// plus ralenties par l'attente du serveur SMTP.

date_default_timezone_set('Indian/Antananarivo');

$root = __DIR__ . '/../';
require_once $root . 'config/database.php';
require_once $root . 'config/mail.php';
require_once $root . 'includes/functions.php';
require_once $root . 'includes/Mailer.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: application/json');
}

// Combien d'emails traiter par passage
$batchSize = 50;

// ============================================
// JOURNALISATION
// ============================================

function mailLog($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/email_sender.log';
    $logDir  = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $line = date('Y-m-d H:i:s') . " [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo $line;
    }
}

// ============================================
// VERROU : une seule exécution à la fois
// ============================================

$lockFile = __DIR__ . '/../tmp/email_sender.lock';
$lockDir  = dirname($lockFile);
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0777, true);
}

if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
    mailLog("Un envoi est déjà en cours, passage ignoré.");
    if (!$isCli) {
        echo json_encode(['success' => false, 'reason' => 'locked']);
    }
    exit;
}
file_put_contents($lockFile, (string)getmypid());

// Le verrou est retiré quoi qu'il arrive
register_shutdown_function(function () use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

// ============================================
// TRAITEMENT DE LA FILE
// ============================================

$db = Database::getInstance();

$pending = $db->fetchAll(
    "SELECT id, recipient_email, recipient_name, subject, message
     FROM email_queue
     WHERE status = 'pending'
     ORDER BY created_at ASC
     LIMIT " . (int)$batchSize
);

$sent   = 0;
$failed = 0;

if (empty($pending)) {
    mailLog("Aucun email en attente.");
    if (!$isCli) {
        echo json_encode(['success' => true, 'sent' => 0, 'failed' => 0]);
    }
    exit;
}

mailLog(count($pending) . " email(s) à envoyer.");

// Une seule instance de Mailer => une seule connexion SMTP pour tout le lot
$mailer = new Mailer();

foreach ($pending as $row) {
    try {
        $ok = $mailer->send(
            $row['recipient_email'],
            $row['subject'],
            $row['message'],
            $row['recipient_name'] ?? ''
        );

        if ($ok) {
            $db->query(
                "UPDATE email_queue SET status = 'sent', processed_at = NOW(), error = NULL WHERE id = ?",
                [$row['id']]
            );
            $sent++;
        } else {
            $db->query(
                "UPDATE email_queue SET status = 'failed', processed_at = NOW(), error = ? WHERE id = ?",
                [$mailer->getLastError() ?: 'Envoi refusé par le serveur SMTP', $row['id']]
            );
            $failed++;
            mailLog("Échec pour {$row['recipient_email']} : " . $mailer->getLastError(), 'ERROR');
        }
    } catch (Exception $e) {
        $db->query(
            "UPDATE email_queue SET status = 'failed', processed_at = NOW(), error = ? WHERE id = ?",
            [$e->getMessage(), $row['id']]
        );
        $failed++;
        mailLog("Exception pour {$row['recipient_email']} : " . $e->getMessage(), 'ERROR');
    }
}

// Fermer proprement la connexion SMTP maintenue ouverte
$mailer->closeConnection();

mailLog("Terminé : $sent envoyé(s), $failed en échec.");

// ============================================
// PURGE DES ANCIENNES ENTRÉES
// ============================================
// Les emails envoyés il y a plus de 30 jours sont supprimés pour que
// la table ne grossisse pas indéfiniment.

try {
    $db->query("DELETE FROM email_queue WHERE status = 'sent' AND processed_at < (NOW() - INTERVAL 30 DAY)");
} catch (Exception $e) {
    mailLog("Purge impossible : " . $e->getMessage(), 'ERROR');
}

if (!$isCli) {
    echo json_encode(['success' => true, 'sent' => $sent, 'failed' => $failed]);
}

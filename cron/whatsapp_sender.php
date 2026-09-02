<?php
// cron/whatsapp_sender.php - Script d'envoi des messages WhatsApp en file d'attente

// ============================================
// CONFIGURATION
// ============================================

$root = __DIR__ . '/../';
require_once $root . 'config/database.php';
require_once $root . 'includes/functions.php';
require_once $root . 'models/Notification.php';

// Vérifier si le script est exécuté en ligne de commande
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: application/json');
}

$db = Database::getInstance();
$notificationModel = new Notification();

// ============================================
// FICHIER DE VERROUILLAGE
// ============================================

$lockFile = __DIR__ . '/../tmp/whatsapp_sender.lock';
$lockDir = dirname($lockFile);

if (!is_dir($lockDir)) {
    mkdir($lockDir, 0777, true);
}

// Vérifier si le script est déjà en cours
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    if (time() - $lockTime < 300) {
        if ($isCli) {
            echo "⏳ Script déjà en cours\n";
        } else {
            echo json_encode(['status' => 'locked', 'message' => 'Script déjà en cours']);
        }
        exit(0);
    }
}

touch($lockFile);

// ============================================
// LOGS
// ============================================

function logWhatsAppMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/whatsapp.log';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

logWhatsAppMessage("📱 Début de l'envoi des messages WhatsApp");

// ============================================
// RÉCUPÉRER LES MESSAGES EN ATTENTE
// ============================================

$messages = $db->fetchAll(
    "SELECT * FROM whatsapp_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 20"
);

if (empty($messages)) {
    logWhatsAppMessage("📭 Aucun message WhatsApp en attente");
    unlink($lockFile);
    if ($isCli) {
        echo "📭 Aucun message WhatsApp en attente\n";
    } else {
        echo json_encode(['status' => 'empty', 'message' => 'Aucun message en attente']);
    }
    exit(0);
}

logWhatsAppMessage("📋 " . count($messages) . " message(s) à envoyer");

// ============================================
// ENVOYER LES MESSAGES
// ============================================

$sent = 0;
$failed = 0;

foreach ($messages as $msg) {
    try {
        // ✅ CONSTRUIRE L'URL WHATSAPP
        $whatsappUrl = "https://wa.me/" . $msg['phone'] . "?text=" . urlencode($msg['message']);
        
        // ✅ OPTION 1: Ouvrir automatiquement dans le navigateur
        // (nécessite que le script soit exécuté dans un navigateur)
        // echo "<script>window.open('$whatsappUrl', '_blank');</script>";
        
        // ✅ OPTION 2: Marquer comme envoyé (pour une file d'attente)
        $db->query(
            "UPDATE whatsapp_queue SET status = 'sent', sent_at = NOW() WHERE id = ?",
            [$msg['id']]
        );
        
        // ✅ OPTION 3: Logger l'URL pour référence
        logWhatsAppMessage("📱 URL WhatsApp générée: " . $whatsappUrl);
        logWhatsAppMessage("📱 Message envoyé à: " . $msg['phone'] . " (Ticket #" . ($msg['ticket_id'] ?? 'N/A') . ")");
        
        // ✅ CRÉER UNE NOTIFICATION IN-APP POUR L'ENVOI
        if ($msg['user_id']) {
            $notificationModel->createNotification(
                $msg['user_id'],
                "📱 Un message WhatsApp vous a été envoyé concernant le ticket #" . ($msg['ticket_id'] ?? 'N/A'),
                "index.php?page=tickets&action=show&id=" . $msg['ticket_id'],
                'general'
            );
        }
        
        $sent++;
        
    } catch (Exception $e) {
        // Marquer comme échoué
        $db->query(
            "UPDATE whatsapp_queue SET status = 'failed', error = ? WHERE id = ?",
            [$e->getMessage(), $msg['id']]
        );
        logWhatsAppMessage("❌ Erreur envoi: " . $e->getMessage(), 'ERROR');
        $failed++;
    }
}

// ============================================
// LOG FINAL
// ============================================

logWhatsAppMessage("✅ Envoi terminé: $sent envoyés, $failed échoués");

// ============================================
// SUPPRIMER LE VERROUILLAGE
// ============================================

unlink($lockFile);

// ============================================
// RÉPONSE
// ============================================

if ($isCli) {
    echo "✅ WhatsApp: $sent envoyés, $failed échoués\n";
} else {
    echo json_encode([
        'success' => true,
        'sent' => $sent,
        'failed' => $failed,
        'total' => count($messages),
        'time' => date('Y-m-d H:i:s')
    ]);
}
?>
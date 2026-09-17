<?php
// sse.php - Server-Sent Events pour notifications en temps réel

// SYNCHRONISATION DE L'HEURE - même fuseau horaire que le téléphone/ordinateur (Madagascar)
date_default_timezone_set('Indian/Antananarivo');

set_time_limit(0);
ignore_user_abort(false);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // évite la mise en buffer par Nginx
header('Access-Control-Allow-Origin: *');

if (ob_get_level() === 0) {
    ob_start();
}

// Démarrer la session pour récupérer l'utilisateur
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "event: error\n";
    echo "data: Non authentifié\n\n";
    ob_flush();
    flush();
    exit;
}

$userId = $_SESSION['user_id'];
$lastNotifId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Notification.php';

$notificationModel = new Notification();

// Boucle bornée dans le temps pour libérer le worker périodiquement
// (le client JS rouvre automatiquement une nouvelle connexion EventSource)
$maxDuration = 55; // secondes
$startTime = time();

while (time() - $startTime < $maxDuration) {
    if (connection_aborted()) {
        break;
    }

    // Récupérer les nouvelles notifications non lues
    $notifications = $notificationModel->getUnreadNotifications($userId, $lastNotifId);

    if (!empty($notifications)) {
        foreach ($notifications as $notif) {
            echo "event: notification\n";
            echo "data: " . json_encode($notif) . "\n\n";
            $lastNotifId = $notif['id'];
        }
        ob_flush();
        flush();
    } else {
        // Ping de maintien de connexion
        echo ": ping\n\n";
        ob_flush();
        flush();
    }

    session_write_close(); // libère le verrou de session pendant l'attente
    sleep(2);
    session_start();
}

echo "event: retry\n";
echo "data: reconnect\n\n";
ob_flush();
flush();
?>
<?php
// sse.php - Server-Sent Events pour notifications en temps réel
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');

// Démarrer la session pour récupérer l'utilisateur
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "event: error\n";
    echo "data: Non authentifié\n\n";
    exit;
}

$userId = $_SESSION['user_id'];
$lastNotifId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Notification.php';

$notificationModel = new Notification();

// Boucle infinie pour vérifier les nouvelles notifications
while (true) {
    // Récupérer les nouvelles notifications non lues
    $notifications = $notificationModel->getUnreadNotifications($userId, $lastNotifId);
    
    if (!empty($notifications)) {
        // Envoyer les nouvelles notifications
        foreach ($notifications as $notif) {
            echo "event: notification\n";
            echo "data: " . json_encode($notif) . "\n\n";
            $lastNotifId = $notif['id'];
        }
        
        // Flush le buffer
        ob_flush();
        flush();
    }
    
    // Attendre 2 secondes avant de vérifier à nouveau
    sleep(2);
}
?>
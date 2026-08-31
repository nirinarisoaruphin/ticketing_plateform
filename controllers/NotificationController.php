<?php
// controllers/NotificationController.php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';

class NotificationController {
    private $notificationModel;
    
    public function __construct() {
        $this->notificationModel = new Notification();
    }
    
    /**
     * Récupérer les notifications (API)
     */
    public function index() {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $notifications = $this->notificationModel->getNotifications($userId);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        exit;
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markRead() {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id > 0) {
            $this->notificationModel->markAsRead($id);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllRead() {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $this->notificationModel->markAllAsRead($_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    /**
     * Compter les notifications non lues (pour le badge)
     */
    public function countUnread() {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['count' => 0]);
            exit;
        }
        
        $count = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }
}
?>
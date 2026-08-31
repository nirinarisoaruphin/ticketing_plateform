<?php
// controllers/HistoriqueController.php - Page d'historique des notifications et activités
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';

class HistoriqueController {
    private $ticketModel;
    private $notificationModel;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Afficher l'historique des notifications et activités
     */
    public function index() {
        global $pageTitle;
        $pageTitle = 'Historique des notifications';
        
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        // Récupérer les notifications de l'utilisateur
        $notifications = $this->notificationModel->getNotifications($userId, 100);
        
        // Récupérer les activités (commentaires, actions)
        if ($role === 'commercial') {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.created_by = ? 
                 ORDER BY c.created_at DESC 
                 LIMIT 50",
                [$userId]
            );
        } elseif ($role === 'responsable_support_technique') {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.category IN ('support_technique', 'bureau_etude') 
                 ORDER BY c.created_at DESC 
                 LIMIT 50"
            );
        } elseif ($role === 'responsable_sav') {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.category = 'sav' 
                 ORDER BY c.created_at DESC 
                 LIMIT 50"
            );
        } elseif ($role === 'responsable_travaux') {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.category = 'travaux' 
                 ORDER BY c.created_at DESC 
                 LIMIT 50"
            );
        } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.category IN ('support_technique', 'bureau_etude') 
                 ORDER BY c.created_at DESC 
                 LIMIT 50"
            );
        } else {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 ORDER BY c.created_at DESC 
                 LIMIT 50"
            );
        }
        
        // Marquer toutes les notifications comme lues
        $this->notificationModel->markAllAsRead($userId);
        
        require_once __DIR__ . '/../views/historique/index.php';
    }
}
?>
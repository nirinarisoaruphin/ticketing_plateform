<?php
// controllers/ApiController.php - VERSION COMPLÈTE CORRIGÉE
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';
//
class ApiController {
    private $ticketModel;
    private $notificationModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->ticketModel = new Ticket();
        $this->notificationModel = new Notification();
    }
    
    // ============================================
    // STATISTIQUES
    // ============================================
    
    public function stats() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $db = Database::getInstance();
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        $statusStats = $db->fetchAll(
            "SELECT status, COUNT(*) as count FROM tickets GROUP BY status"
        );
        $statusLabels = array();
        $statusCounts = array();
        foreach ($statusStats as $stat) {
            $statusLabels[] = getStatusLabel($stat['status']);
            $statusCounts[] = (int)$stat['count'];
        }
        
        $priorityStats = $db->fetchAll(
            "SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority"
        );
        $priorityLabels = array();
        $priorityCounts = array();
        foreach ($priorityStats as $stat) {
            $priorityLabels[] = getPriorityLabel($stat['priority']);
            $priorityCounts[] = (int)$stat['count'];
        }
        
        $categoryStats = $db->fetchAll(
            "SELECT category, COUNT(*) as count FROM tickets GROUP BY category"
        );
        $categoryLabels = array();
        $categoryCounts = array();
        foreach ($categoryStats as $stat) {
            $categoryLabels[] = getCategoryLabel($stat['category']);
            $categoryCounts[] = (int)$stat['count'];
        }
        
        $evolutionStats = $db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM tickets 
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
        $evolutionLabels = array();
        $evolutionCounts = array();
        
        $dates = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = $date;
            $evolutionLabels[] = date('d/m', strtotime($date));
        }
        
        foreach ($dates as $date) {
            $found = false;
            foreach ($evolutionStats as $e) {
                if ($e['date'] === $date) {
                    $evolutionCounts[] = (int)$e['count'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $evolutionCounts[] = 0;
            }
        }
        
        echo json_encode([
            'status_labels' => $statusLabels,
            'status_counts' => $statusCounts,
            'priority_labels' => $priorityLabels,
            'priority_counts' => $priorityCounts,
            'category_labels' => $categoryLabels,
            'category_counts' => $categoryCounts,
            'evolution_labels' => $evolutionLabels,
            'evolution_counts' => $evolutionCounts
        ]);
        exit;
    }
    /**
 * Compter les messages pour le polling
 */
public function countMessages() {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    if (!isApiAuthenticated()) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    try {
        $db = Database::getInstance();
        $count = $db->fetch("SELECT COUNT(*) as count FROM comments")['count'] ?? 0;
        echo json_encode(['count' => (int)$count]);
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    }
    exit;
}
    // ============================================
    // NOTIFICATIONS - VERSION CORRIGÉE
    // ============================================
    
    public function notifications() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Non authentifié',
                'code' => 'AUTH_REQUIRED',
                'success' => false
            ]);
            exit;
        }
        
        try {
            $userId = (int)$_SESSION['user_id'];
            
            $db = Database::getInstance();
            $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Utilisateur invalide',
                    'code' => 'USER_NOT_FOUND',
                    'success' => false
                ]);
                exit;
            }
            
            $notifications = $this->notificationModel->getNotifications($userId, 20);
            $unreadCount = $this->notificationModel->getUnreadCount($userId);
            
            if (!$notifications) {
                $notifications = [];
            }
            
            foreach ($notifications as &$notif) {
                $icons = [
                    'ticket' => 'fa-ticket-alt',
                    'comment' => 'fa-comment',
                    'status' => 'fa-exchange-alt',
                    'action' => 'fa-bolt',
                    'message' => 'fa-comment-dots',
                    'validation' => 'fa-check-circle',
                    'assignation' => 'fa-user-check',
                    'general' => 'fa-bell'
                ];
                $notif['icon'] = $icons[$notif['type'] ?? 'general'] ?? 'fa-bell';
                
                $colors = [
                    'ticket' => 'text-indigo-600',
                    'comment' => 'text-blue-600',
                    'status' => 'text-amber-600',
                    'action' => 'text-purple-600',
                    'message' => 'text-emerald-600',
                    'validation' => 'text-green-600',
                    'assignation' => 'text-cyan-600',
                    'general' => 'text-gray-600'
                ];
                $notif['color'] = $colors[$notif['type'] ?? 'general'] ?? 'text-gray-600';
                
                $notif['time_ago'] = $this->timeAgo($notif['created_at'] ?? date('Y-m-d H:i:s'));
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("API Notifications Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur interne du serveur',
                'code' => 'INTERNAL_ERROR'
            ]);
            exit;
        }
    }
    
    private function timeAgo($datetime) {
        if (empty($datetime)) return 'N/A';
        
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 0) return 'à l\'instant';
        if ($diff < 60) return 'à l\'instant';
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . 'h';
        if ($diff < 2592000) return floor($diff / 86400) . 'j';
        if ($diff < 31536000) return floor($diff / 2592000) . ' mois';
        return date('d/m/Y', $timestamp);
    }
    
    public function markRead() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        
        if ($id > 0) {
            $this->notificationModel->markAsRead($id);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    public function markAllRead() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        try {
            $userId = (int)$_SESSION['user_id'];
            $db = Database::getInstance();
            
            $result = $db->query(
                "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
                [$userId]
            );
            
            if ($result !== false) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function countUnread() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            echo json_encode(['count' => 0, 'error' => 'Non authentifié']);
            exit;
        }
        
        try {
            $count = $this->notificationModel->getUnreadCount((int)$_SESSION['user_id']);
            echo json_encode(['count' => $count]);
        } catch (Exception $e) {
            echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function deleteAllNotifications() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Non authentifié'
            ]);
            exit;
        }
        
        try {
            $userId = (int)$_SESSION['user_id'];
            $db = Database::getInstance();
            
            $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Utilisateur invalide'
                ]);
                exit;
            }
            
            $before = $db->fetch(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?",
                [$userId]
            );
            $beforeCount = (int)($before['count'] ?? 0);
            
            if ($beforeCount == 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Aucune notification à supprimer',
                    'deleted' => 0
                ]);
                exit;
            }
            
            $result = $db->query(
                "DELETE FROM notifications WHERE user_id = ?",
                [$userId]
            );
            
            if ($result !== false) {
                $after = $db->fetch(
                    "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?",
                    [$userId]
                );
                $afterCount = (int)($after['count'] ?? 0);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Toutes les notifications ont été supprimées avec succès',
                    'deleted' => $beforeCount - $afterCount
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erreur lors de la suppression des notifications'
                ]);
            }
        } catch (Exception $e) {
            error_log("Delete notifications error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ============================================
    // ✅ SUPPRIMER UN TICKET VIA API
    // ============================================
    
    public function deleteTicket() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Non authentifié',
                'code' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ticketId = isset($input['id']) ? (int)$input['id'] : 0;
        
        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID de ticket invalide'
            ]);
            exit;
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ✅ VÉRIFICATION DES PERMISSIONS
        if (!in_array($role, ['admin', 'coordinateur'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Vous n\'avez pas la permission de supprimer ce ticket'
            ]);
            exit;
        }
        
        $ticket = $this->ticketModel->getTicketDetails($ticketId);
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Ticket non trouvé'
            ]);
            exit;
        }
        
        if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Ce ticket est ' . strtolower(getStatusLabel($ticket['status'])) . ' et ne peut pas être supprimé'
            ]);
            exit;
        }
        
        try {
            $this->ticketModel->delete($ticketId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ticket supprimé avec succès'
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("Delete ticket error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la suppression du ticket'
            ]);
            exit;
        }
    }
    
    // ============================================
    // MESSAGES
    // ============================================
    
    public function sendMessage() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Non authentifié',
                'code' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $ticketId = isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;
        $content = sanitize($input['content'] ?? '');
        
        if ($ticketId <= 0 || empty($content)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Données invalides'
            ]);
            exit;
        }
        
        try {
            $db = Database::getInstance();
            $userId = $_SESSION['user_id'] ?? 0;
            $userName = $_SESSION['user_name'] ?? 'Utilisateur';
            $userRole = $_SESSION['user_role'] ?? 'commercial';
            
            $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
            
            if (!$ticket) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Ticket non trouvé'
                ]);
                exit;
            }
            
            if ($userRole === 'commercial' && $ticket['created_by'] != $userId) {
                $hasCommented = $db->fetch(
                    "SELECT id FROM comments WHERE ticket_id = ? AND user_id = ? LIMIT 1",
                    [$ticketId, $userId]
                );
                if (!$hasCommented) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Vous n\'avez pas accès à ce ticket'
                    ]);
                    exit;
                }
            }
            
            $messageId = $this->ticketModel->addComment($ticketId, $userId, $content);
            
            if (!$messageId) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Erreur lors de l\'envoi du message'
                ]);
                exit;
            }
            
            $participants = $db->fetchAll(
                "SELECT DISTINCT user_id FROM (
                    SELECT created_by as user_id FROM tickets WHERE id = ?
                    UNION
                    SELECT assigned_to as user_id FROM tickets WHERE id = ? AND assigned_to IS NOT NULL
                    UNION
                    SELECT user_id FROM comments WHERE ticket_id = ?
                ) as participants",
                [$ticketId, $ticketId, $ticketId]
            );
            
            $link = "index.php?page=messages";
            $message = "💬 Nouveau message sur le ticket #{$ticket['ticket_number']} de " . $userName;
            
            foreach ($participants as $p) {
                if ($p['user_id'] != $userId) {
                    $this->notificationModel->createNotification($p['user_id'], $message, $link, 'message');
                }
            }
            
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'user_name' => $userName,
                'user_role' => $userRole
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("sendMessage Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur interne: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    public function getMessages() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Non authentifié',
                'code' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
        
        $ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
        
        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID de ticket invalide'
            ]);
            exit;
        }
        
        try {
            $db = Database::getInstance();
            $userId = $_SESSION['user_id'] ?? 0;
            $userRole = $_SESSION['user_role'] ?? 'commercial';
            
            $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
            
            if (!$ticket) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Ticket non trouvé'
                ]);
                exit;
            }
            
            if ($userRole === 'commercial' && $ticket['created_by'] != $userId) {
                $hasCommented = $db->fetch(
                    "SELECT id FROM comments WHERE ticket_id = ? AND user_id = ? LIMIT 1",
                    [$ticketId, $userId]
                );
                if (!$hasCommented) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Accès refusé à ce ticket'
                    ]);
                    exit;
                }
            }
            
            $messages = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 WHERE c.ticket_id = ? 
                 ORDER BY c.created_at ASC",
                [$ticketId]
            );
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'ticket' => $ticket
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("getMessages Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur interne du serveur: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // ============================================
    // DASHBOARD
    // ============================================
    
    public function dashboardData() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        $stats = [];
        
        if ($role === 'commercial') {
            $stats['total'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE created_by = ?", [$userId])['count'] ?? 0;
            $stats['en_attente'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE created_by = ? AND status IN ('nouveau', 'assigne')", [$userId])['count'] ?? 0;
            $stats['en_cours'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE created_by = ? AND status IN ('en_cours', 'en_attente')", [$userId])['count'] ?? 0;
            $stats['resolu'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE created_by = ? AND status = 'resolu'", [$userId])['count'] ?? 0;
            $stats['critique'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE created_by = ? AND priority = 'critique'", [$userId])['count'] ?? 0;
        } else {
            $stats['total'] = $db->fetch("SELECT COUNT(*) as count FROM tickets")['count'] ?? 0;
            $stats['en_attente'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE status IN ('nouveau', 'assigne')")['count'] ?? 0;
            $stats['en_cours'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE status IN ('en_cours', 'en_attente')")['count'] ?? 0;
            $stats['resolu'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE status = 'resolu'")['count'] ?? 0;
            $stats['critique'] = $db->fetch("SELECT COUNT(*) as count FROM tickets WHERE priority = 'critique'")['count'] ?? 0;
        }
        
        if ($role === 'commercial') {
            $recentTickets = $db->fetchAll(
                "SELECT t.*, u.full_name as created_by_name 
                 FROM tickets t 
                 LEFT JOIN users u ON t.created_by = u.id 
                 WHERE t.created_by = ? 
                 ORDER BY t.created_at DESC 
                 LIMIT 5",
                [$userId]
            );
        } else {
            $recentTickets = $db->fetchAll(
                "SELECT t.*, u.full_name as created_by_name 
                 FROM tickets t 
                 LEFT JOIN users u ON t.created_by = u.id 
                 ORDER BY t.created_at DESC 
                 LIMIT 5"
            );
        }
        
        if ($role === 'commercial') {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 WHERE t.created_by = ? 
                 ORDER BY c.created_at DESC 
                 LIMIT 5",
                [$userId]
            );
        } else {
            $activities = $db->fetchAll(
                "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status 
                 FROM comments c 
                 INNER JOIN users u ON c.user_id = u.id 
                 INNER JOIN tickets t ON c.ticket_id = t.id 
                 ORDER BY c.created_at DESC 
                 LIMIT 5"
            );
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'activities' => $activities
        ]);
        exit;
    }
    
    public function getTickets() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $tickets = $this->ticketModel->getTicketsWithUserInfo();
        
        echo json_encode([
            'success' => true,
            'tickets' => $tickets
        ]);
        exit;
    }
}
?>
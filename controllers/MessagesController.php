<?php
// controllers/MessagesController.php - Version complète
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';

class MessagesController {
    private $ticketModel;
    private $notificationModel;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->notificationModel = new Notification();
    }
    
    public function index() {
        global $pageTitle;
        $pageTitle = 'Messages';
        
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }
        
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ✅ TRAITER L'ENVOI DE MESSAGE VIA POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $content = sanitize($_POST['content'] ?? '');
            
            if ($ticketId > 0 && !empty($content)) {
                try {
                    $result = $db->insert(
                        "INSERT INTO comments (ticket_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
                        [$ticketId, $userId, $content]
                    );
                    
                    if ($result) {
                        // Notifier les participants
                        $this->notifyParticipants($ticketId, $userId, $content);
                        setFlash('success', '✅ Message envoyé avec succès !');
                    } else {
                        setFlash('danger', '❌ Erreur lors de l\'envoi du message.');
                    }
                } catch (Exception $e) {
                    setFlash('danger', '❌ Erreur: ' . $e->getMessage());
                }
            } else {
                setFlash('danger', '⚠️ Veuillez remplir tous les champs.');
            }
            redirect('index.php?page=messages');
        }
        
        // ✅ Récupérer tous les messages
        $messages = $db->fetchAll("
            SELECT c.*, 
                   u.full_name, 
                   u.role,
                   t.ticket_number,
                   t.title as ticket_title,
                   t.status as ticket_status
            FROM comments c 
            INNER JOIN users u ON c.user_id = u.id 
            INNER JOIN tickets t ON c.ticket_id = t.id 
            ORDER BY c.created_at DESC 
            LIMIT 200
        ");
        
        // ✅ Récupérer tous les tickets actifs
        $tickets = $db->fetchAll("
            SELECT id, ticket_number, title, status 
            FROM tickets 
            WHERE status != 'cloture' 
            ORDER BY created_at DESC
        ");
        
        require_once __DIR__ . '/../views/messages/index.php';
    }
    
    /**
     * Notifier les participants d'un ticket
     */
    private function notifyParticipants($ticketId, $userId, $content) {
        $db = Database::getInstance();
        
        $ticket = $db->fetch("SELECT ticket_number FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket) return;
        
        $sender = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$userId]);
        $senderName = $sender['full_name'] ?? 'Un utilisateur';
        
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
        
        $shortContent = strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
        $message = $senderName . ' a envoyé un message sur le ticket #' . $ticket['ticket_number'] . ' : "' . $shortContent . '"';
        $link = "index.php?page=messages";
        
        foreach ($participants as $p) {
            if ($p['user_id'] != $userId) {
                $db->insert(
                    "INSERT INTO notifications (user_id, ticket_id, message, link, type, is_read, created_at) 
                     VALUES (?, ?, ?, ?, 'message', 0, NOW())",
                    [$p['user_id'], $ticketId, $message, $link]
                );
            }
        }
    }
}
?>
<?php
// api_handler.php - API complète pour la messagerie - VERSION CORRIGÉE
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forcer l'en-tête JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// ✅ CHARGER LES FONCTIONS AVANT DE LES UTILISER
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Database.php';

// ✅ SUPPRIMER LA REDÉCLARATION DE isApiAuthenticated()
// La fonction est déjà dans includes/functions.php

if (!isApiAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non authentifié',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit;
}

// Charger les modèles
$db = Database::getInstance();
$userId = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // ============================================
    // COMPTER LES MESSAGES (polling)
    // ============================================
    if ($action === 'count_messages') {
        $count = $db->fetch("SELECT COUNT(*) as count FROM comments")['count'] ?? 0;
        echo json_encode(['count' => (int)$count]);
        exit;
    }
    
    // ============================================
    // COMPTER LES MESSAGES PAR TICKET
    // ============================================
    if ($action === 'count_messages_by_ticket') {
        $ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
        if ($ticketId <= 0) {
            echo json_encode(['count' => 0]);
            exit;
        }
        $count = $db->fetch("SELECT COUNT(*) as count FROM comments WHERE ticket_id = ?", [$ticketId])['count'] ?? 0;
        echo json_encode(['count' => (int)$count]);
        exit;
    }
    
    // ============================================
    // COMPTER LES NOTIFICATIONS NON LUES
    // ============================================
    if ($action === 'count_unread_notifications') {
        if (!isApiAuthenticated()) {
            echo json_encode(['count' => 0]);
            exit;
        }
        $userId = (int)$_SESSION['user_id'];
        $notificationModel = new Notification();
        $count = $notificationModel->getUnreadCount($userId);
        echo json_encode(['count' => (int)$count]);
        exit;
    }
    
    // ============================================
    // RÉCUPÉRER LES MESSAGES D'UN TICKET
    // ============================================
    if ($action === 'get_messages') {
        $ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
        
        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID invalide']);
            exit;
        }
        
        $messages = $db->fetchAll(
            "SELECT c.*, u.full_name, u.role 
             FROM comments c 
             INNER JOIN users u ON c.user_id = u.id 
             WHERE c.ticket_id = ? 
             ORDER BY c.created_at ASC",
            [$ticketId]
        );
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
    
    // ============================================
    // ENVOYER UN MESSAGE
    // ============================================
    if ($action === 'send_message') {
        $input = json_decode(file_get_contents('php://input'), true);
        $ticketId = (int)($input['ticket_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        
        if ($ticketId <= 0 || empty($content)) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            exit;
        }
        
        // Vérifier que le ticket existe
        $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket non trouvé']);
            exit;
        }
        
        // Ajouter le commentaire
        $messageId = $db->insert(
            "INSERT INTO comments (ticket_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
            [$ticketId, $userId, $content]
        );
        
        if ($messageId) {
            echo json_encode(['success' => true, 'message_id' => $messageId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi']);
        }
        exit;
    }
    
    // ============================================
    // RÉCUPÉRER TOUS LES TICKETS
    // ============================================
    if ($action === 'get_tickets') {
        $tickets = $db->fetchAll(
            "SELECT id, ticket_number, title, status FROM tickets WHERE status != 'cloture' ORDER BY created_at DESC LIMIT 100"
        );
        echo json_encode(['success' => true, 'tickets' => $tickets]);
        exit;
    }
    
    // ============================================
    // ACTION NON RECONNUE
    // ============================================
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Action non reconnue',
        'available_actions' => ['count_messages', 'count_messages_by_ticket', 'get_messages', 'send_message', 'get_tickets']
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur interne du serveur'
    ]);
    exit;
}
?>
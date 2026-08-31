<?php
// views/messages/index.php - Version avec règles de visibilité
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

$db = Database::getInstance();
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Utilisateur';
$userRole = $_SESSION['user_role'] ?? 'commercial';

// ✅ Fonctions de base
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
        $map = ['nouveau' => 'Nouveau', 'assigne' => 'Assigné', 'en_cours' => 'En cours', 'en_attente' => 'En attente', 'resolu' => 'Résolu', 'cloture' => 'Clôturé'];
        return $map[$status] ?? $status;
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y H:i') {
        if (!$date) return '-';
        return date($format, strtotime($date));
    }
}

if (!function_exists('getRoleLabel')) {
    function getRoleLabel($role) {
        $labels = [
            'admin' => 'Administrateur',
            'coordinateur' => 'Coordinateur',
            'commercial' => 'Commercial',
            'responsable_support_technique' => 'Responsable Support Technique',
            'responsable_sav' => 'Responsable SAV',
            'responsable_travaux' => 'Responsable Travaux',
            'charge_etude_electricite' => 'Chargé d\'Étude Electricité',
            'charge_etude_courant_faible' => 'Chargé d\'Étude Courant Faible',
            'charge_etude_climatisation' => 'Chargé d\'Étude Climatisation'
        ];
        return $labels[$role] ?? $role;
    }
}

// ============================================
// ✅ TRAITER L'ENVOI DE MESSAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    if ($ticketId > 0 && !empty($content)) {
        try {
            $db->insert(
                "INSERT INTO comments (ticket_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
                [$ticketId, $userId, $content]
            );
            $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Message envoyé !'];
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => '❌ Erreur: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => '⚠️ Veuillez remplir tous les champs.'];
    }
    redirect('index.php?page=messages');
}

// ============================================
// ✅ TRAITER LA MODIFICATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_message'])) {
    $messageId = (int)($_POST['message_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    
    if ($messageId > 0 && !empty($content)) {
        $message = $db->fetch("SELECT user_id FROM comments WHERE id = ?", [$messageId]);
        if ($message && $message['user_id'] == $userId) {
            $db->query("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?", [$content, $messageId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Message modifié !'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => '❌ Vous ne pouvez pas modifier ce message.'];
        }
    }
    redirect('index.php?page=messages&ticket_id=' . $ticketId);
}

// ============================================
// ✅ TRAITER LA SUPPRESSION
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'delete_message') {
    $messageId = (int)($_GET['id'] ?? 0);
    $ticketId = (int)($_GET['ticket_id'] ?? 0);
    
    if ($messageId > 0) {
        $message = $db->fetch("SELECT user_id FROM comments WHERE id = ?", [$messageId]);
        if ($message && ($message['user_id'] == $userId || isAdmin())) {
            $db->query("DELETE FROM comments WHERE id = ?", [$messageId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Message supprimé !'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => '❌ Vous ne pouvez pas supprimer ce message.'];
        }
    }
    redirect('index.php?page=messages&ticket_id=' . $ticketId);
}

// ============================================
// ✅ RÉCUPÉRER LES CONVERSATIONS - AVEC RÈGLES DE VISIBILITÉ
// ============================================
// ✅ ADMIN et COORDINATEUR voient TOUTES les conversations
if ($userRole === 'admin' || $userRole === 'coordinateur') {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE EXISTS (SELECT 1 FROM comments WHERE ticket_id = t.id)
        ORDER BY last_message_date DESC
    ");
}
// ✅ RESPONSABLE SUPPORT TECHNIQUE (Mahery) - voit Support Technique et BE
elseif ($userRole === 'responsable_support_technique') {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE t.category IN ('support_technique', 'bureau_etude')
        ORDER BY last_message_date DESC
    ");
}
// ✅ RESPONSABLE SAV (Dina) - voit SAV uniquement
elseif ($userRole === 'responsable_sav') {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE t.category = 'sav'
        ORDER BY last_message_date DESC
    ");
}
// ✅ RESPONSABLE TRAVAUX (Andry) - voit Travaux uniquement
elseif ($userRole === 'responsable_travaux') {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE t.category = 'travaux'
        ORDER BY last_message_date DESC
    ");
}
// ✅ CHARGÉS D'ÉTUDE - voient uniquement leurs tickets assignés
elseif (in_array($userRole, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE t.assigned_to = ?
          AND t.category IN ('support_technique', 'bureau_etude')
        ORDER BY last_message_date DESC
    ", [$userId]);
}
// ✅ COMMERCIAL - voit ses tickets uniquement
else {
    $conversations = $db->fetchAll("
        SELECT DISTINCT 
            t.id as ticket_id,
            t.ticket_number,
            t.title,
            t.status,
            t.category,
            (SELECT COUNT(*) FROM comments WHERE ticket_id = t.id) as message_count,
            (SELECT MAX(created_at) FROM comments WHERE ticket_id = t.id) as last_message_date,
            (SELECT content FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_content,
            (SELECT full_name FROM users WHERE id = (SELECT user_id FROM comments WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1)) as last_user_name
        FROM tickets t
        WHERE t.created_by = ?
        ORDER BY last_message_date DESC
    ", [$userId]);
}

$activeTicketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : ($conversations[0]['ticket_id'] ?? 0);

$messages = [];
$activeTicket = null;

if ($activeTicketId > 0) {
    // ✅ Vérifier que l'utilisateur a accès à ce ticket
    $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$activeTicketId]);
    if ($ticket) {
        $canAccess = false;
        if ($userRole === 'admin' || $userRole === 'coordinateur') {
            $canAccess = true;
        } elseif ($userRole === 'responsable_support_technique' && in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
            $canAccess = true;
        } elseif ($userRole === 'responsable_sav' && $ticket['category'] === 'sav') {
            $canAccess = true;
        } elseif ($userRole === 'responsable_travaux' && $ticket['category'] === 'travaux') {
            $canAccess = true;
        } elseif (in_array($userRole, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation']) && $ticket['assigned_to'] == $userId) {
            $canAccess = true;
        } elseif ($userRole === 'commercial' && $ticket['created_by'] == $userId) {
            $canAccess = true;
        }
        
        if ($canAccess) {
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
                WHERE c.ticket_id = ?
                ORDER BY c.created_at ASC
            ", [$activeTicketId]);
            
            $activeTicket = $ticket;
        }
    }
}

// ✅ TOUS LES TICKETS POUR LE FORMULAIRE - AVEC RÈGLES DE VISIBILITÉ
if ($userRole === 'admin' || $userRole === 'coordinateur') {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        ORDER BY created_at DESC
    ");
} elseif ($userRole === 'responsable_support_technique') {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        AND category IN ('support_technique', 'bureau_etude')
        ORDER BY created_at DESC
    ");
} elseif ($userRole === 'responsable_sav') {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        AND category = 'sav'
        ORDER BY created_at DESC
    ");
} elseif ($userRole === 'responsable_travaux') {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        AND category = 'travaux'
        ORDER BY created_at DESC
    ");
} elseif (in_array($userRole, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        AND assigned_to = ?
        AND category IN ('support_technique', 'bureau_etude')
        ORDER BY created_at DESC
    ", [$userId]);
} else {
    $tickets = $db->fetchAll("
        SELECT id, ticket_number, title, status, category 
        FROM tickets 
        WHERE status != 'cloture' 
        AND created_by = ?
        ORDER BY created_at DESC
    ", [$userId]);
}

$roleColors = [
    'admin' => '#EF4444',
    'coordinateur' => '#8B5CF6',
    'responsable_support_technique' => '#4F46E5',
    'responsable_sav' => '#EC4899',
    'responsable_travaux' => '#F59E0B',
    'commercial' => '#3B82F6',
    'charge_etude_electricite' => '#F97316',
    'charge_etude_courant_faible' => '#06B6D4',
    'charge_etude_climatisation' => '#10B981'
];

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

<style>
/* ============================================ */
/* STYLES MESSENGER */
/* ============================================ */

.messenger-container {
    display: flex;
    gap: 0;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    height: calc(100vh - 190px);
    min-height: 400px;
    max-height: 650px;
}

/* ===== SIDEBAR ===== */
.messenger-sidebar {
    width: 280px;
    min-width: 220px;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    background: #fafafa;
}

.sidebar-header {
    padding: 10px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: white;
    flex-shrink: 0;
}

.sidebar-header h2 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-header h2 span {
    font-size: 11px;
    font-weight: 400;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 0 8px;
    border-radius: 20px;
}

.sidebar-search {
    padding: 6px 12px;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.sidebar-search input {
    width: 100%;
    padding: 5px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    font-size: 12px;
    outline: none;
    background: white;
    color: #1e293b;
}

.sidebar-search input:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
}

.conversation-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 0;
}

.conv-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
}

.conv-item:hover {
    background: #f1f5f9;
}

.conv-item.active {
    background: #eef2ff;
    border-left-color: #4F46E5;
}

.conv-item .conv-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    color: white;
    flex-shrink: 0;
}

.conv-item .conv-info {
    flex: 1;
    min-width: 0;
}

.conv-item .conv-info .conv-name {
    font-weight: 600;
    font-size: 12px;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 4px;
}

.conv-item .conv-info .conv-name .ticket-badge {
    font-size: 9px;
    font-weight: 500;
    color: #4F46E5;
    background: #eef2ff;
    padding: 0 6px;
    border-radius: 8px;
}

.conv-item .conv-info .conv-last {
    font-size: 11px;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-item .conv-time {
    font-size: 10px;
    color: #94a3b8;
    flex-shrink: 0;
}

.conv-item .conv-count {
    background: #4F46E5;
    color: white;
    font-size: 9px;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}

/* ===== CHAT AREA ===== */
.messenger-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

/* ===== CHAT HEADER ===== */
.chat-header {
    padding: 8px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    flex-shrink: 0;
}

.chat-header .chat-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    color: white;
    flex-shrink: 0;
}

.chat-header .chat-info {
    flex: 1;
    min-width: 0;
}

.chat-header .chat-info h3 {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.chat-header .chat-info .chat-status {
    font-size: 10px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 4px;
}

.chat-header .chat-info .chat-status .status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.chat-header .chat-info .chat-status .status-dot.online { background: #10B981; }

.chat-header .chat-actions {
    display: flex;
    gap: 4px;
}

.chat-header .chat-actions button {
    background: transparent;
    border: none;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
    color: #94a3b8;
    font-size: 12px;
}

.chat-header .chat-actions button:hover {
    background: #f1f5f9;
    color: #4F46E5;
}

/* ===== MESSAGES ===== */
.chat-messages {
    flex: 1;
    padding: 10px 14px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: #f8fafc;
}

/* ===== DATE SEPARATOR ===== */
.date-separator {
    text-align: center;
    padding: 8px 0 4px 0;
    font-size: 10px;
    color: #94a3b8;
    font-weight: 500;
}

.date-separator span {
    background: #e2e8f0;
    padding: 1px 12px;
    border-radius: 10px;
}

/* ============================================ */
/* ✅ MESSAGE ITEM - MESSAGES DES AUTRES PLUS VISIBLES */
/* ============================================ */
.msg-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 85%;
    animation: msgIn 0.2s ease-out;
    margin-bottom: 4px;
}

.msg-wrapper.own {
    align-self: flex-end;
    flex-direction: row-reverse;
}

@keyframes msgIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.msg-wrapper .msg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    color: white;
    flex-shrink: 0;
    margin-top: 2px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.msg-wrapper .msg-content {
    max-width: 100%;
}

/* 🟦 Messages des autres (gauche) - PLUS VISIBLE */
.msg-wrapper:not(.own) .msg-bubble {
    background: #e2e8f0;
    color: #0f172a;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 18px;
    border: 2px solid #cbd5e1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.5;
}

.msg-wrapper:not(.own) .msg-bubble:hover {
    background: #e8edf4;
    border-color: #94a3b8;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* 🟣 Messages de l'utilisateur (droite) */
.msg-wrapper.own .msg-bubble {
    background: #4F46E5;
    color: white;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 18px;
    border: none;
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.5;
}

.msg-wrapper .msg-bubble .edited-tag {
    font-size: 9px;
    font-style: italic;
    opacity: 0.5;
    margin-left: 4px;
}

.msg-wrapper.own .msg-bubble .edited-tag {
    color: rgba(255,255,255,0.6);
}

/* ===== FOOTER : META + ACTIONS ===== */
.msg-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

.msg-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 9px;
    color: #94a3b8;
}

.msg-meta .msg-role {
    font-weight: 600;
    padding: 0 8px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #475569;
    font-size: 8px;
}

.msg-wrapper.own .msg-meta .msg-role {
    background: rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.8);
}

.msg-meta .msg-ticket {
    color: #4F46E5;
    text-decoration: none;
    font-weight: 500;
    font-size: 9px;
}

.msg-wrapper.own .msg-meta .msg-ticket {
    color: rgba(255,255,255,0.8);
}

/* ============================================ */
/* ✅ ACTIONS - TOUJOURS VISIBLES */
/* ============================================ */
.msg-actions {
    display: flex;
    gap: 3px;
}

.msg-actions button {
    background: transparent;
    border: none;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 3px;
    font-weight: 500;
}

.msg-actions .edit-btn {
    color: #4F46E5;
    background: #eef2ff;
}
.msg-actions .edit-btn:hover {
    background: #dbeafe;
    transform: scale(1.05);
}

.msg-actions .delete-btn {
    color: #EF4444;
    background: #fef2f2;
}
.msg-actions .delete-btn:hover {
    background: #fee2e2;
    transform: scale(1.05);
}

.msg-wrapper.own .msg-actions .edit-btn {
    color: #a78bfa;
    background: rgba(255,255,255,0.15);
}
.msg-wrapper.own .msg-actions .edit-btn:hover {
    background: rgba(255,255,255,0.25);
}

.msg-wrapper.own .msg-actions .delete-btn {
    color: #f87171;
    background: rgba(255,255,255,0.15);
}
.msg-wrapper.own .msg-actions .delete-btn:hover {
    background: rgba(255,255,255,0.25);
}

/* ===== STATUS BADGE ===== */
.status-badge {
    display: inline-flex;
    font-size: 9px;
    font-weight: 600;
    padding: 0 8px;
    border-radius: 12px;
}
.status-badge.nouveau { background: #dbeafe; color: #1e40af; }
.status-badge.assigne { background: #ede9fe; color: #5b21b6; }
.status-badge.en_cours { background: #fef3c7; color: #92400e; }
.status-badge.en_attente { background: #fed7aa; color: #9a3412; }
.status-badge.resolu { background: #d1fae5; color: #065f46; }
.status-badge.cloture { background: #f3f4f6; color: #374151; }

/* ===== EMPTY ===== */
.empty-chat {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
    text-align: center;
    padding: 30px;
}

.empty-chat i {
    font-size: 36px;
    color: #e2e8f0;
    margin-bottom: 8px;
}

.empty-chat h3 {
    font-size: 15px;
    color: #0f172a;
    margin-bottom: 2px;
}

.empty-chat p {
    font-size: 12px;
}

/* ===== FLASH ===== */
.flash-msg {
    padding: 8px 14px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 13px;
}
.flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.flash-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

/* ============================================ */
/* ✅ CHAT INPUT - TOUJOURS ACTIF */
/* ============================================ */
.chat-input {
    padding: 8px 14px;
    border-top: 1px solid #e2e8f0;
    background: white;
    display: flex;
    gap: 8px;
    align-items: flex-end;
    flex-shrink: 0;
}

.chat-input form {
    display: flex;
    gap: 8px;
    width: 100%;
    align-items: flex-end;
}

.chat-input select {
    padding: 5px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    font-size: 12px;
    background: #f8fafc;
    color: #1e293b;
    min-width: 100px;
    height: 34px;
    outline: none;
}

.chat-input select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
}

.chat-input textarea {
    flex: 1;
    padding: 5px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    font-size: 13px;
    background: #f8fafc;
    color: #1e293b;
    resize: none;
    height: 34px;
    max-height: 80px;
    font-family: inherit;
    outline: none;
    padding-top: 6px;
}

.chat-input textarea:focus {
    border-color: #4F46E5;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
}

.chat-input .btn-send {
    padding: 5px 18px;
    background: #4F46E5;
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    height: 34px;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.chat-input .btn-send:hover {
    background: #4338CA;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}

/* ===== MODALS ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: white;
    border-radius: 14px;
    padding: 24px 28px;
    max-width: 440px;
    width: 95%;
    box-shadow: 0 20px 48px rgba(0,0,0,0.15);
    animation: modalIn 0.3s ease-out;
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(15px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-box h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-box .subtitle {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 12px;
}

.modal-box textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    min-height: 80px;
    resize: vertical;
    font-family: inherit;
}

.modal-box textarea:focus {
    border-color: #4F46E5;
    outline: none;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
}

.modal-box .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
}

.modal-box .modal-actions button,
.modal-box .modal-actions a {
    padding: 6px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.modal-box .modal-actions .btn-cancel {
    background: #f1f5f9;
    color: #64748b;
}
.modal-box .modal-actions .btn-cancel:hover {
    background: #e2e8f0;
}

.modal-box .modal-actions .btn-save {
    background: #4F46E5;
    color: white;
}
.modal-box .modal-actions .btn-save:hover {
    background: #4338CA;
}

.modal-box .modal-actions .btn-danger {
    background: #EF4444;
    color: white;
}
.modal-box .modal-actions .btn-danger:hover {
    background: #DC2626;
}

/* ===== SCROLLBAR ===== */
.conversation-list::-webkit-scrollbar,
.chat-messages::-webkit-scrollbar {
    width: 3px;
}
.conversation-list::-webkit-scrollbar-track,
.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}
.conversation-list::-webkit-scrollbar-thumb,
.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .messenger-container {
        flex-direction: column;
        height: calc(100vh - 140px);
        min-height: 350px;
        max-height: none;
    }
    .messenger-sidebar {
        width: 100%;
        min-width: unset;
        max-height: 160px;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
    .messenger-chat {
        flex: 1;
        min-height: 250px;
    }
    .msg-wrapper {
        max-width: 95%;
    }
    .chat-input form {
        flex-wrap: wrap;
    }
    .chat-input select {
        min-width: 100%;
    }
    .chat-input .btn-send {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .messenger-container {
        height: calc(100vh - 120px);
        min-height: 300px;
    }
    .messenger-sidebar {
        max-height: 130px;
    }
    .conv-item {
        padding: 4px 10px;
    }
    .conv-item .conv-avatar {
        width: 28px;
        height: 28px;
        font-size: 10px;
    }
    .chat-header {
        padding: 6px 12px;
    }
    .chat-messages {
        padding: 8px 10px;
    }
    .msg-wrapper .msg-bubble {
        font-size: 12px;
        padding: 4px 10px;
    }
    .chat-input {
        padding: 6px 10px;
    }
    .msg-actions button {
        font-size: 9px;
        padding: 1px 5px;
    }
    .msg-actions {
        opacity: 1;
    }
}
</style>

<div class="messenger-container">

    <!-- ============================================ -->
    <!-- SIDEBAR -->
    <!-- ============================================ -->
    <div class="messenger-sidebar">
        <div class="sidebar-header">
            <h2>
                <i class="fas fa-comment-dots" style="color:#4F46E5;font-size:14px;"></i>
                Discussions
                <span><?= count($conversations) ?></span>
            </h2>
        </div>
        
        <div class="sidebar-search">
            <input type="text" id="searchConv" placeholder="🔍 Rechercher..." oninput="filterConversations(this.value)">
        </div>
        
        <div class="conversation-list" id="conversationList">
            <?php if (empty($conversations)): ?>
                <div style="padding:16px;text-align:center;color:#94a3b8;font-size:12px;">
                    <i class="fas fa-inbox" style="font-size:20px;display:block;margin-bottom:4px;"></i>
                    Aucune conversation
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): 
                    $isActive = ($conv['ticket_id'] == $activeTicketId);
                    $avatarColor = $roleColors[$conv['category']] ?? '#6B7280';
                    $lastUser = $conv['last_user_name'] ?? 'Utilisateur';
                    $lastMsg = $conv['last_message_content'] ?? '';
                    $count = $conv['message_count'] ?? 0;
                ?>
                <div class="conv-item <?= $isActive ? 'active' : '' ?>" onclick="window.location.href='?page=messages&ticket_id=<?= $conv['ticket_id'] ?>'">
                    <div class="conv-avatar" style="background: <?= $avatarColor ?>;">
                        <?= strtoupper(substr($conv['ticket_number'] ?? 'T', -2)) ?>
                    </div>
                    <div class="conv-info">
                        <div class="conv-name">
                            <?= htmlspecialchars($conv['ticket_number']) ?>
                            <span class="ticket-badge"><?= getStatusLabel($conv['status']) ?></span>
                        </div>
                        <div class="conv-last">
                            <span class="author"><?= htmlspecialchars($lastUser) ?>:</span>
                            <?= htmlspecialchars(substr($lastMsg, 0, 35)) ?><?= strlen($lastMsg) > 35 ? '...' : '' ?>
                        </div>
                    </div>
                    <div class="conv-time">
                        <?= $conv['last_message_date'] ? date('H:i', strtotime($conv['last_message_date'])) : '' ?>
                    </div>
                    <?php if ($count > 0): ?>
                    <div class="conv-count"><?= $count > 9 ? '9+' : $count ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- CHAT -->
    <!-- ============================================ -->
    <div class="messenger-chat">
        
        <!-- HEADER -->
        <div class="chat-header">
            <?php if ($activeTicket): 
                $avatarColor = $roleColors[$activeTicket['category']] ?? '#6B7280';
            ?>
            <div class="chat-avatar" style="background: <?= $avatarColor ?>;">
                <?= strtoupper(substr($activeTicket['ticket_number'] ?? 'T', -2)) ?>
            </div>
            <div class="chat-info">
                <h3><?= htmlspecialchars($activeTicket['ticket_number']) ?></h3>
                <div class="chat-status">
                    <span class="status-dot online"></span>
                    <?= count($messages) ?> messages
                    <span style="margin-left:6px;">•</span>
                    <span class="status-badge <?= $activeTicket['status'] ?>">
                        <?= getStatusLabel($activeTicket['status']) ?>
                    </span>
                </div>
            </div>
            <div class="chat-actions">
                <button onclick="window.location.href='index.php?page=tickets&action=show&id=<?= $activeTicketId ?>'" title="Voir le ticket">
                    <i class="fas fa-external-link-alt"></i>
                </button>
                <button onclick="location.reload()" title="Rafraîchir">
                    <i class="fas fa-sync"></i>
                </button>
            </div>
            <?php else: ?>
            <div class="chat-info">
                <h3>Sélectionnez une conversation</h3>
                <div class="chat-status">Choisissez une discussion à gauche</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- MESSAGES -->
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
                <div class="empty-chat">
                    <i class="fas fa-comment-slash"></i>
                    <h3>Aucun message</h3>
                    <p>Soyez le premier à envoyer un message !</p>
                </div>
            <?php else: 
                $lastDate = '';
                $prevUserId = null;
                foreach ($messages as $msg):
                    $msgDate = date('d/m/Y', strtotime($msg['created_at']));
                    $isOwn = ($msg['user_id'] == $userId);
                    $color = $roleColors[$msg['role']] ?? '#6B7280';
                    $isEdited = (!empty($msg['updated_at']) && $msg['updated_at'] != $msg['created_at']);
                    $canModify = $isOwn || isAdmin();
                    
                    $sameAuthor = ($prevUserId == $msg['user_id']);
                    $prevUserId = $msg['user_id'];
            ?>
                <?php if ($msgDate != $lastDate): ?>
                    <div class="date-separator"><span><?= $msgDate ?></span></div>
                    <?php $lastDate = $msgDate; ?>
                    <?php $sameAuthor = false; ?>
                <?php endif; ?>
                
                <div class="msg-wrapper <?= $isOwn ? 'own' : '' ?> <?= $sameAuthor ? 'same-author' : '' ?>">
                    <?php if (!$isOwn && !$sameAuthor): ?>
                    <div class="msg-avatar" style="background: <?= $color ?>;">
                        <?= strtoupper(substr($msg['full_name'] ?? 'U', 0, 2)) ?>
                    </div>
                    <?php elseif (!$isOwn && $sameAuthor): ?>
                    <div class="msg-avatar" style="background: transparent; width:32px; height:32px; flex-shrink:0;"></div>
                    <?php endif; ?>
                    
                    <div class="msg-content">
                        <div class="msg-bubble">
                            <?= nl2br(htmlspecialchars($msg['content'] ?? '')) ?>
                            <?php if ($isEdited): ?>
                            <span class="edited-tag">(modifié)</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="msg-footer">
                            <div class="msg-meta">
                                <?php if (!$isOwn && !$sameAuthor): ?>
                                <span class="msg-role"><?= htmlspecialchars($msg['full_name'] ?? 'Inconnu') ?></span>
                                <?php endif; ?>
                                <span><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                <?php if ($isOwn): ?>
                                <span style="color:#4F46E5;font-weight:500;">Vous</span>
                                <?php endif; ?>
                                <a href="index.php?page=tickets&action=show&id=<?= $msg['ticket_id'] ?>" class="msg-ticket">
                                    <?= htmlspecialchars($msg['ticket_number'] ?? 'N/A') ?>
                                </a>
                            </div>
                            
                            <?php if ($canModify): ?>
                            <div class="msg-actions">
                                <?php if ($isOwn): ?>
                                <button class="edit-btn" onclick="openEditModal(<?= $msg['id'] ?>, '<?= addslashes($msg['content']) ?>', <?= $activeTicketId ?>)" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <button class="delete-btn" onclick="confirmDelete(<?= $msg['id'] ?>, <?= $activeTicketId ?>)" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- INPUT -->
        <div class="chat-input">
            <form method="POST" action="index.php?page=messages" id="messageForm">
                <input type="hidden" name="send_message" value="1">
                <input type="hidden" name="reply_to" id="replyToInput" value="">
                
                <select name="ticket_id" required id="ticketSelect">
                    <option value="">📋 Sélectionner un ticket...</option>
                    <?php foreach ($tickets as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($activeTicketId == $t['id']) ? 'selected' : '' ?>>
                            #<?= $t['ticket_number'] ?> - <?= $t['title'] ?>
                            (<?= getStatusLabel($t['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <textarea name="content" rows="1" placeholder="Écrivez votre message..." required id="messageInput"></textarea>
                
                <button type="submit" class="btn-send" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL ÉDITION -->
<!-- ============================================ -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3><i class="fas fa-edit" style="color:#4F46E5;"></i> Modifier le message</h3>
        <p class="subtitle">Modifiez votre message et cliquez sur Enregistrer.</p>
        <form method="POST" action="index.php?page=messages">
            <input type="hidden" name="edit_message" value="1">
            <input type="hidden" id="editMessageId" name="message_id">
            <input type="hidden" id="editTicketId" name="ticket_id" value="<?= $activeTicketId ?>">
            <textarea id="editContent" name="content" rows="4" required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL SUPPRESSION -->
<!-- ============================================ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box" style="max-width: 400px;">
        <h3 style="color: #EF4444;"><i class="fas fa-exclamation-triangle" style="color:#EF4444;"></i> Supprimer le message</h3>
        <p class="subtitle" style="color: #64748b;">
            Êtes-vous sûr de vouloir supprimer ce message ?<br>
            <strong>Cette action est irréversible.</strong>
        </p>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">Annuler</button>
            <a id="deleteConfirmLink" href="#" class="btn-danger">
                <i class="fas fa-trash"></i> Supprimer
            </a>
        </div>
    </div>
</div>

<script>
// ============================================
// RECHERCHE
// ============================================
function filterConversations(query) {
    const items = document.querySelectorAll('.conv-item');
    const q = query.toLowerCase().trim();
    items.forEach(item => {
        item.style.display = (q === '' || item.textContent.toLowerCase().includes(q)) ? 'flex' : 'none';
    });
}

// ============================================
// MODAL ÉDITION
// ============================================
function openEditModal(messageId, content, ticketId) {
    document.getElementById('editMessageId').value = messageId;
    document.getElementById('editTicketId').value = ticketId;
    document.getElementById('editContent').value = content;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// ============================================
// MODAL SUPPRESSION
// ============================================
function confirmDelete(messageId, ticketId) {
    document.getElementById('deleteConfirmLink').href = 'index.php?page=messages&action=delete_message&id=' + messageId + '&ticket_id=' + ticketId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// ============================================
// FERMER AVEC ÉCHAP
// ============================================
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
    }
});

// ============================================
// FERMER EN CLIQUANT À L'EXTÉRIEUR
// ============================================
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// ============================================
// SCROLL EN BAS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('chatMessages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});

// ============================================
// AUTO-REFRESH
// ============================================
let lastCount = <?= count($messages) ?>;

function checkNewMessages() {
    const ticketId = <?= $activeTicketId ?: 0 ?>;
    if (ticketId === 0) return;
    
    fetch('api_handler.php?action=count_messages_by_ticket&ticket_id=' + ticketId, {
        credentials: 'include'
    })
    .then(r => r.json())
    .then(data => {
        if (data.count && data.count > lastCount) {
            location.reload();
        }
    })
    .catch(() => {});
}

setInterval(checkNewMessages, 10000);

// ============================================
// ENVOI AVEC ENTRÉE
// ============================================
document.querySelector('.chat-input textarea')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.closest('form').submit();
    }
});

// ============================================
// SÉLECTIONNER UN TICKET
// ============================================
document.querySelector('#ticketSelect')?.addEventListener('change', function() {
    const ticketId = this.value;
    if (ticketId) {
        window.location.href = '?page=messages&ticket_id=' + ticketId;
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
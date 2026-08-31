<?php
// models/Notification.php - VERSION COMPLÈTE AVEC NOTIFICATIONS COMMERCIAL

require_once __DIR__ . '/Database.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * ✅ NOTIFIER UN UTILISATEUR - SIMPLIFIÉE
     */
    public function notifyUser($userId, $message, $link = null, $type = 'general') {
        // Vérifier que l'utilisateur existe
        if (empty($userId) || empty($message)) {
            error_log("❌ Notification: userId ou message vide");
            return false;
        }
        
        // Vérifier que l'utilisateur existe en BDD
        $user = $this->db->fetch("SELECT id FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            error_log("❌ Notification: Utilisateur ID $userId non trouvé");
            return false;
        }
        
        return $this->createNotification($userId, $message, $link, $type);
    }
    
    /**
     * Créer une notification
     */
    public function createNotification($userId, $message, $link = null, $type = 'general') {
        if (empty($userId) || empty($message)) {
            return false;
        }
        
        try {
            $sql = "INSERT INTO notifications (user_id, message, link, type, is_read, created_at) 
                    VALUES (?, ?, ?, ?, 0, NOW())";
            
            $result = $this->db->insert($sql, [$userId, $message, $link, $type]);
            
            if ($result) {
                error_log("✅ Notification créée pour l'utilisateur $userId : " . substr($message, 0, 30));
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("❌ Exception Notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifier tous les participants d'un ticket (inclut le commercial)
     */
    public function notifyTicketParticipants($ticketId, $message, $link = null, $type = 'general') {
        $participants = $this->db->fetchAll(
            "SELECT DISTINCT user_id FROM (
                SELECT created_by as user_id FROM tickets WHERE id = ?
                UNION
                SELECT assigned_to as user_id FROM tickets WHERE id = ? AND assigned_to IS NOT NULL
                UNION
                SELECT user_id FROM comments WHERE ticket_id = ?
            ) as users",
            [$ticketId, $ticketId, $ticketId]
        );
        
        $count = 0;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentUserId = $_SESSION['user_id'] ?? 0;
        
        foreach ($participants as $p) {
            if ($p['user_id'] != $currentUserId) {
                if ($this->createNotification($p['user_id'], $message, $link, $type)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createNotificationForUsers($userIds, $message, $link = null, $type = 'general') {
        if (empty($userIds) || empty($message)) {
            return false;
        }
        
        $count = 0;
        foreach ($userIds as $userId) {
            if ($this->createNotification($userId, $message, $link, $type)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Notifier tous les techniciens
     */
    public function notifyTechnicians($message, $link = null, $type = 'general') {
        $technicians = $this->db->fetchAll(
            "SELECT id FROM users WHERE role IN ('responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
        );
        
        $userIds = array_column($technicians, 'id');
        return $this->createNotificationForUsers($userIds, $message, $link, $type);
    }
    
    /**
     * Notifier tous les responsables d'une catégorie
     */
    public function notifyResponsables($category, $message, $link = null, $type = 'general') {
        $roleMap = [
            'support_technique' => 'responsable_support_technique',
            'sav' => 'responsable_sav',
            'travaux' => 'responsable_travaux',
            'bureau_etude' => 'responsable_support_technique'
        ];
        
        $role = $roleMap[$category] ?? null;
        if (!$role) {
            return false;
        }
        
        $responsables = $this->db->fetchAll(
            "SELECT id FROM users WHERE role = ?",
            [$role]
        );
        
        $userIds = array_column($responsables, 'id');
        return $this->createNotificationForUsers($userIds, $message, $link, $type);
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getNotifications($userId, $limit = 20) {
        $limit = (int)$limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT " . $limit,
            [$userId]
        );
    }
    
    /**
     * Récupérer les notifications non lues
     */
    public function getUnreadNotifications($userId, $lastId = 0) {
        $lastId = (int)$lastId;
        
        if ($lastId > 0) {
            return $this->db->fetchAll(
                "SELECT * FROM notifications 
                 WHERE user_id = ? AND is_read = 0 AND id > ? 
                 ORDER BY created_at DESC",
                [$userId, $lastId]
            );
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE user_id = ? AND is_read = 0 
             ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount($userId) {
        if (empty($userId)) {
            return 0;
        }
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM notifications 
             WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId) {
        return $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE id = ?",
            [$notificationId]
        );
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId) {
        return $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Supprimer une notification
     */
    public function deleteNotification($notificationId) {
        return $this->db->query(
            "DELETE FROM notifications WHERE id = ?",
            [$notificationId]
        );
    }
    
    /**
     * Supprimer toutes les notifications d'un utilisateur
     */
    public function deleteAllNotifications($userId) {
        return $this->db->query(
            "DELETE FROM notifications WHERE user_id = ?",
            [$userId]
        );
    }
}
?>
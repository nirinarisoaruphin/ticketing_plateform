<?php
// models/Log.php
require_once __DIR__ . '/Database.php';

class Log extends Model {
    protected $table = 'logs';
    
    public function addLog($userId, $action, $details = null) {
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public function getLogs($limit = 50) {
        return $this->db->fetchAll(
            "SELECT l.*, u.full_name as user_name 
             FROM logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             ORDER BY l.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    public function getLogsByUser($userId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
?>
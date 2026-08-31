<?php
// models/Intervention.php - VERSION COMPLÈTE AVEC AUTOMATISATION

require_once __DIR__ . '/Database.php';

class Intervention extends Model {
    protected $table = 'interventions';
    
    // ============================================
    // CRUD DE BASE
    // ============================================
    
    /**
     * Récupérer les interventions avec les détails
     */
    public function getInterventionsWithDetails($filters = []) {
        $sql = "SELECT i.*, 
                       t.title as ticket_title,
                       t.ticket_number,
                       t.category,
                       u.full_name as technician_name,
                       c.full_name as client_name
                FROM interventions i
                INNER JOIN tickets t ON i.ticket_id = t.id
                INNER JOIN users u ON i.technician_id = u.id
                INNER JOIN users c ON t.created_by = c.id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['technician_id']) && $filters['technician_id']) {
            $sql .= " AND i.technician_id = ?";
            $params[] = $filters['technician_id'];
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['date_from']) && $filters['date_from']) {
            $sql .= " AND i.planned_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to']) && $filters['date_to']) {
            $sql .= " AND i.planned_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY i.planned_date ASC, i.planned_time ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Récupérer les interventions par technicien
     */
    public function getInterventionsByTechnician($technicianId, $date = null) {
        $sql = "SELECT i.*, t.title as ticket_title, t.ticket_number
                FROM interventions i
                INNER JOIN tickets t ON i.ticket_id = t.id
                WHERE i.technician_id = ?";
        $params = [$technicianId];
        
        if ($date) {
            $sql .= " AND i.planned_date = ?";
            $params[] = $date;
        }
        
        $sql .= " ORDER BY i.planned_date ASC, i.planned_time ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Vérifier si un ticket a déjà une intervention
     */
    public function findByTicketId($ticketId) {
        return $this->db->fetch(
            "SELECT id FROM interventions WHERE ticket_id = ?",
            [$ticketId]
        );
    }
    
    /**
     * Vérifier les conflits de planning
     */
    public function checkConflict($technicianId, $date, $time) {
        return $this->db->fetch(
            "SELECT id FROM interventions 
             WHERE technician_id = ? 
             AND planned_date = ? 
             AND planned_time = ?",
            [$technicianId, $date, $time]
        );
    }
    
    /**
     * Trouver une intervention par ID
     */
    public function findById($id) {
        return $this->db->fetch(
            "SELECT i.*, t.ticket_number, t.title as ticket_title
             FROM interventions i
             INNER JOIN tickets t ON i.ticket_id = t.id
             WHERE i.id = ?",
            [$id]
        );
    }
    
    /**
     * Créer une intervention
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        return $this->db->insert($sql, array_values($data));
    }
    
    /**
     * Mettre à jour une intervention
     */
    public function update($id, $data) {
        $fields = array_keys($data);
        $set = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        return $this->db->query($sql, $params);
    }
    
    /**
     * Supprimer une intervention
     */
    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
    
    // ============================================
    // ✅ AUTOMATISATION DU PLANNING
    // ============================================
    
    /**
     * Récupérer les interventions à démarrer automatiquement
     */
    public function getInterventionsToStart() {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        return $this->db->fetchAll(
            "SELECT i.*, 
                    t.ticket_number, 
                    t.title as ticket_title,
                    t.created_by,
                    u.full_name as technician_name,
                    u.email as technician_email
             FROM interventions i
             INNER JOIN tickets t ON i.ticket_id = t.id
             INNER JOIN users u ON i.technician_id = u.id
             WHERE i.status = 'planifiee' 
               AND CONCAT(i.planned_date, ' ', i.planned_time) <= ?
               AND i.planned_date = ?",
            [$now, $today]
        );
    }
    
    /**
     * Récupérer les interventions à terminer automatiquement
     */
    public function getInterventionsToComplete() {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        return $this->db->fetchAll(
            "SELECT i.*, 
                    t.ticket_number, 
                    t.title as ticket_title,
                    t.created_by,
                    u.full_name as technician_name,
                    u.email as technician_email
             FROM interventions i
             INNER JOIN tickets t ON i.ticket_id = t.id
             INNER JOIN users u ON i.technician_id = u.id
             WHERE i.status = 'en_cours' 
               AND DATE_ADD(CONCAT(i.planned_date, ' ', i.planned_time), INTERVAL i.duration MINUTE) <= ?
               AND i.planned_date = ?",
            [$now, $today]
        );
    }
    
    /**
     * Récupérer les interventions en retard
     */
    public function getInterventionsDelayed() {
        $now = date('Y-m-d H:i:s');
        return $this->db->fetchAll(
            "SELECT i.*, 
                    t.ticket_number, 
                    t.title as ticket_title,
                    u.full_name as technician_name
             FROM interventions i
             INNER JOIN tickets t ON i.ticket_id = t.id
             INNER JOIN users u ON i.technician_id = u.id
             WHERE i.status = 'en_cours' 
               AND DATE_ADD(CONCAT(i.planned_date, ' ', i.planned_time), INTERVAL i.duration MINUTE) <= ?",
            [$now]
        );
    }
    
    /**
     * Compter les interventions par statut
     */
    public function countByStatus($technicianId = null) {
        $sql = "SELECT status, COUNT(*) as count FROM interventions";
        $params = [];
        
        if ($technicianId) {
            $sql .= " WHERE technician_id = ?";
            $params[] = $technicianId;
        }
        
        $sql .= " GROUP BY status";
        
        $result = $this->db->fetchAll($sql, $params);
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }
    
    /**
     * Compter les interventions d'un technicien par statut
     */
    public function countByTechnicianAndStatus($technicianId) {
        return $this->countByStatus($technicianId);
    }
    
    /**
     * Calculer la durée totale des interventions par technicien
     */
    public function getTotalDurationByTechnician($technicianId, $date = null) {
        $sql = "SELECT SUM(duration) as total FROM interventions WHERE technician_id = ?";
        $params = [$technicianId];
        
        if ($date) {
            $sql .= " AND planned_date = ?";
            $params[] = $date;
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Démarrer une intervention automatiquement
     */
    public function autoStart($interventionId) {
        $now = date('Y-m-d H:i:s');
        return $this->db->query(
            "UPDATE interventions SET 
                status = 'en_cours', 
                actual_start = NOW(),
                updated_at = NOW() 
             WHERE id = ?",
            [$interventionId]
        );
    }
    
    /**
     * Terminer une intervention automatiquement
     */
    public function autoComplete($interventionId, $actualDuration = null) {
        $sql = "UPDATE interventions SET 
                status = 'realisee',
                updated_at = NOW()";
        $params = [];
        
        if ($actualDuration !== null) {
            $sql .= ", actual_duration = ?";
            $params[] = $actualDuration;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $interventionId;
        
        return $this->db->query($sql, $params);
    }
}
?>
<?php
// models/Ticket.php - VERSION CORRIGÉE AVEC DISTINCT POUR ÉVITER LES DOUBLONS

require_once __DIR__ . '/Database.php';

class Ticket extends Model {
    protected $table = 'tickets';
    
    // ============================================
    // RÉCUPÉRER LES TICKETS - AVEC DISTINCT
    // ============================================
    
    public function getTicketsWithUserInfo($filters = array()) {
        // ✅ DISTINCT pour éviter les doublons
        $sql = "SELECT DISTINCT t.*, 
                       u1.full_name as created_by_name,
                       u2.full_name as assigned_to_name,
                       u2.role as assigned_to_role,
                       (SELECT GROUP_CONCAT(u3.full_name SEPARATOR ', ') 
                        FROM ticket_assignments ta 
                        INNER JOIN users u3 ON ta.user_id = u3.id 
                        WHERE ta.ticket_id = t.id AND ta.is_active = 1) as assigned_users_names
                FROM tickets t
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE 1=1";
        
        $params = array();
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ✅ ADMIN et COORDINATEUR : voient tout
        if ($role === 'admin' || $role === 'coordinateur') {
            // Pas de filtre
        }
        // ✅ COMMERCIAL : voit uniquement ses tickets
        elseif ($role === 'commercial') {
            $sql .= " AND t.created_by = ?";
            $params[] = $userId;
        }
        // ✅ RESPONSABLE SUPPORT TECHNIQUE : voit SA catégorie + SES tickets
        elseif ($role === 'responsable_support_technique') {
            $sql .= " AND (t.category IN ('support_technique', 'bureau_etude') OR t.created_by = ?)";
            $params[] = $userId;
        }
        // ✅ RESPONSABLE SAV : voit SAV + SES tickets
        elseif ($role === 'responsable_sav') {
            $sql .= " AND (t.category = 'sav' OR t.created_by = ?)";
            $params[] = $userId;
        }
        // ✅ RESPONSABLE TRAVAUX : voit Travaux + SES tickets
        elseif ($role === 'responsable_travaux') {
            $sql .= " AND (t.category = 'travaux' OR t.created_by = ?)";
            $params[] = $userId;
        }
        // ✅ CHARGÉS D'ÉTUDE
        elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            $sql .= " AND (t.assigned_to = ? 
                        OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1)
                        OR t.created_by = ?)";
            $sql .= " AND t.category IN ('support_technique', 'bureau_etude')";
            $params[] = $userId;
            $params[] = $userId;
            $params[] = $userId;
        }
        // ❌ RÔLE NON RECONNU
        else {
            $sql .= " AND 1=0";
        }
        
        // Filtres supplémentaires
        if (isset($filters['status']) && $filters['status']) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['priority']) && $filters['priority']) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        $sql .= " ORDER BY FIELD(t.priority, 'critique', 'haute', 'moyenne', 'basse'), t.created_at DESC";
        
        $result = $this->db->fetchAll($sql, $params);
        
        error_log("📊 getTicketsWithUserInfo - Rôle: " . $role . ", Tickets trouvés: " . count($result));
        
        return $result;
    }
    
    // ============================================
    // RÉCUPÉRER LES DÉTAILS D'UN TICKET
    // ============================================
    
    public function getTicketDetails($id) {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, 
                       u1.full_name as created_by_name,
                       u1.email as created_by_email,
                       u2.full_name as assigned_to_name,
                       u2.email as assigned_to_email,
                       u2.phone as assigned_to_phone,
                       (SELECT GROUP_CONCAT(u3.full_name SEPARATOR ', ') 
                        FROM ticket_assignments ta 
                        INNER JOIN users u3 ON ta.user_id = u3.id 
                        WHERE ta.ticket_id = t.id AND ta.is_active = 1) as assigned_users_names
                FROM tickets t
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.id = ?";
        
        $ticket = $db->fetch($sql, [$id]);
        
        if (!$ticket) {
            error_log("❌ Ticket non trouvé dans getTicketDetails() - ID: " . $id);
            return null;
        }
        
        error_log("✅ Ticket trouvé dans getTicketDetails() - ID: " . $id . ", assigned_to: " . ($ticket['assigned_to'] ?? 'NULL'));
        
        return $ticket;
    }
    
    // ============================================
    // ASSIGNATIONS
    // ============================================
    
    public function getAssignedUsers($ticketId) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT u.id, u.full_name, u.email, u.role, ta.assigned_at, ta.assigned_by,
                    u2.full_name as assigned_by_name
             FROM ticket_assignments ta
             INNER JOIN users u ON ta.user_id = u.id
             LEFT JOIN users u2 ON ta.assigned_by = u2.id
             WHERE ta.ticket_id = ? AND ta.is_active = 1
             ORDER BY ta.assigned_at ASC",
            [$ticketId]
        );
    }
    
    public function isUserAssigned($ticketId, $userId) {
        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT id FROM ticket_assignments 
             WHERE ticket_id = ? AND user_id = ? AND is_active = 1",
            [$ticketId, $userId]
        );
        return $result !== false;
    }
    
    public function assignToMultiple($ticketId, $userIds, $assignedBy) {
        $success = 0;
        $errors = [];
        $db = Database::getInstance();
        
        $ticket = $db->fetch("SELECT id, ticket_number FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket) {
            return ['success' => 0, 'errors' => ['Ticket non trouvé']];
        }
        
        $db->query(
            "UPDATE ticket_assignments SET is_active = 0 WHERE ticket_id = ?",
            [$ticketId]
        );
        
        foreach ($userIds as $userId) {
            try {
                $user = $db->fetch("SELECT id, full_name FROM users WHERE id = ?", [$userId]);
                if (!$user) {
                    $errors[] = "Utilisateur ID $userId non trouvé";
                    continue;
                }
                
                $db->query(
                    "INSERT INTO ticket_assignments (ticket_id, user_id, assigned_by, assigned_at, is_active) 
                     VALUES (?, ?, ?, NOW(), 1)
                     ON DUPLICATE KEY UPDATE assigned_at = NOW(), is_active = 1, assigned_by = ?",
                    [$ticketId, $userId, $assignedBy, $assignedBy]
                );
                $success++;
            } catch (Exception $e) {
                $errors[] = $user['full_name'] ?? 'ID ' . $userId . ': ' . $e->getMessage();
            }
        }
        
        if ($success > 0) {
            $db->query(
                "UPDATE tickets SET status = 'assigne', updated_at = NOW() WHERE id = ?",
                [$ticketId]
            );
        }
        
        return ['success' => $success, 'errors' => $errors];
    }
    
    // ============================================
    // TICKETS SANS INTERVENTION - AVEC DISTINCT
    // ============================================
    
    public function getTicketsWithoutIntervention() {
        $db = Database::getInstance();
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ✅ DISTINCT pour éviter les doublons
        $sql = "SELECT DISTINCT t.*, u.full_name as created_by_name 
                FROM tickets t 
                LEFT JOIN users u ON t.created_by = u.id 
                LEFT JOIN interventions i ON t.id = i.ticket_id
                WHERE i.id IS NULL 
                  AND t.status NOT IN ('resolu', 'cloture')";
        
        $params = [];
        
        if ($role === 'commercial') {
            $sql .= " AND t.created_by = ?";
            $params[] = $userId;
        } elseif ($role === 'responsable_support_technique') {
            $sql .= " AND (t.category IN ('support_technique', 'bureau_etude') OR t.created_by = ?)";
            $params[] = $userId;
        } elseif ($role === 'responsable_sav') {
            $sql .= " AND (t.category = 'sav' OR t.created_by = ?)";
            $params[] = $userId;
        } elseif ($role === 'responsable_travaux') {
            $sql .= " AND (t.category = 'travaux' OR t.created_by = ?)";
            $params[] = $userId;
        } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            $sql .= " AND (t.assigned_to = ? 
                        OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1)
                        OR t.created_by = ?)";
            $sql .= " AND t.category IN ('support_technique', 'bureau_etude')";
            $params[] = $userId;
            $params[] = $userId;
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY FIELD(t.priority, 'critique', 'haute', 'moyenne', 'basse'), t.created_at ASC";
        
        return $db->fetchAll($sql, $params);
    }
    
    // ============================================
    // COMMENTAIRES ET ACTIONS
    // ============================================
    
    public function getCommentsByTicket($ticketId) {
        return $this->db->fetchAll(
            "SELECT c.*, u.full_name, u.role 
             FROM comments c 
             INNER JOIN users u ON c.user_id = u.id 
             WHERE c.ticket_id = ? 
             ORDER BY c.created_at ASC",
            [$ticketId]
        );
    }
    
    public function getActionsByTicket($ticketId) {
        return $this->db->fetchAll(
            "SELECT c.*, u.full_name, u.role, u.email 
             FROM comments c 
             INNER JOIN users u ON c.user_id = u.id 
             WHERE c.ticket_id = ? AND c.is_action = 1
             ORDER BY c.created_at DESC",
            [$ticketId]
        );
    }
    
    public function addComment($ticketId, $userId, $content) {
        return $this->db->insert(
            "INSERT INTO comments (ticket_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
            [$ticketId, $userId, $content]
        );
    }
    
    public function addAction($ticketId, $userId, $content, $actionType, $notifyRoles = null) {
        $db = Database::getInstance();
        
        if (is_array($notifyRoles)) {
            $notifyRoles = implode(',', $notifyRoles);
        }
        
        $result = $db->insert(
            "INSERT INTO comments (ticket_id, user_id, content, action_type, notify_roles, is_action, created_at) 
             VALUES (?, ?, ?, ?, ?, 1, NOW())",
            [$ticketId, $userId, $content, $actionType, $notifyRoles]
        );
        
        return $result;
    }
    
    // ============================================
    // CRUD TICKETS
    // ============================================
    
    public function create($data) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        error_log("📝 Ticket::create - Données reçues: " . json_encode([
            'ticket_number' => $data['ticket_number'] ?? 'N/A',
            'category' => $data['category'] ?? 'N/A',
            'assigned_to' => $data['assigned_to'] ?? 'NULL',
            'created_by' => $data['created_by'] ?? 'NULL'
        ]));
        
        if (!isset($data['assigned_to']) || $data['assigned_to'] === null || $data['assigned_to'] === '') {
            error_log("⚠️ assigned_to est NULL ou vide dans create()");
            $admin = $db->fetch("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            if ($admin) {
                $data['assigned_to'] = $admin['id'];
                error_log("✅ Assignation à l'admin par défaut (ID: " . $admin['id'] . ")");
            }
        }
        
        if (isset($data['ticket_number'])) {
            $existing = $db->fetch(
                "SELECT id FROM tickets WHERE ticket_number = ?",
                [$data['ticket_number']]
            );
            
            if ($existing) {
                $category = $data['category'] ?? 'support_technique';
                try {
                    $data['ticket_number'] = generateTicketNumber($category) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
                } catch (Exception $e) {
                    error_log("❌ Ticket::create - régénération ticket_number impossible : " . $e->getMessage());
                    return false;
                }
            }
        }
        
        $allowedFields = [
            'ticket_number', 'title', 'description', 'category', 'type_demande',
            'priority', 'status', 'validation_status', 'created_by', 'assigned_to',
            'commercial_dedie', 'client_name', 'adresse_client', 'interlocuteur',
            'contact_technique', 'lieu_visite', 'visite_date', 'visite_heure',
            'moyen_transport', 'elements_complement', 'attachment'
        ];
        
        $filteredData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $filteredData[$field] = $data[$field];
            }
        }
        
        if (empty($filteredData)) {
            return false;
        }
        
        $placeholders = array_fill(0, count($filteredData), '?');
        $sql = "INSERT INTO tickets (" . implode(', ', array_keys($filteredData)) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        // ✅ RETRY AUTOMATIQUE EN CAS DE COLLISION DE ticket_number
        // Filet de sécurité final : même avec le compteur atomique,
        // on retente en cas d'erreur de clé dupliquée (ex: contrainte
        // UNIQUE ajoutée après coup sur une base contenant déjà des
        // doublons, ou tout autre cas imprévu).
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $this->db->insert($sql, array_values($filteredData));
                error_log("✅ Ticket créé avec succès - ID: " . $result);
                return $result;
            } catch (Exception $e) {
                $isDuplicateKey = stripos($e->getMessage(), 'Duplicate entry') !== false
                    || stripos($e->getMessage(), '1062') !== false;
                
                if ($isDuplicateKey && $attempt < $maxAttempts && isset($filteredData['ticket_number'])) {
                    error_log("⚠️ Collision ticket_number (tentative $attempt), régénération...");
                    $category = $filteredData['category'] ?? 'support_technique';
                    try {
                        $filteredData['ticket_number'] = generateTicketNumber($category) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
                    } catch (Exception $e2) {
                        error_log("❌ Régénération ticket_number impossible : " . $e2->getMessage());
                        return false;
                    }
                    $sql = "INSERT INTO tickets (" . implode(', ', array_keys($filteredData)) . ") 
                            VALUES (" . implode(', ', array_fill(0, count($filteredData), '?')) . ")";
                    continue;
                }
                
                error_log("❌ Ticket::create Error: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    public function update($id, $data) {
        $fields = array_keys($data);
        $set = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        return $this->db->query($sql, $params);
    }
    
    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
    
    public function updateStatus($ticketId, $status, $userId = null) {
        $db = Database::getInstance();
        
        $ticket = $db->fetch("SELECT id, ticket_number FROM tickets WHERE id = ?", [$ticketId]);
        if (!$ticket) {
            return false;
        }
        
        $result = $db->query(
            "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $ticketId]
        );
        
        if ($result && $userId) {
            $actionLabels = [
                'nouveau' => 'Nouveau',
                'assigne' => 'Assigné',
                'en_cours' => 'En cours',
                'en_attente' => 'En attente',
                'resolu' => 'Résolu',
                'cloture' => 'Clôturé'
            ];
            $label = $actionLabels[$status] ?? $status;
            
            $db->insert(
                "INSERT INTO comments (ticket_id, user_id, content, action_type, is_action, created_at) 
                 VALUES (?, ?, ?, ?, 1, NOW())",
                [$ticketId, $userId, 'Statut changé vers : ' . $label, 'changement_statut']
            );
        }
        
        return $result;
    }
    
    // ============================================
    // STATISTIQUES
    // ============================================
    
    public function countByStatus() {
        $result = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }
    
    public function countByPriority() {
        $result = $this->db->fetchAll("SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority");
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['priority']] = (int)$row['count'];
        }
        return $counts;
    }
    
    public function countByCategory() {
        $result = $this->db->fetchAll("SELECT category, COUNT(*) as count FROM tickets GROUP BY category");
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['category']] = (int)$row['count'];
        }
        return $counts;
    }
    
    public function getStats() {
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM tickets")['count'] ?? 0;
        
        $statusCounts = $this->countByStatus();
        $priorityCounts = $this->countByPriority();
        $categoryCounts = $this->countByCategory();
        
        return array(
            'total' => $total,
            'by_status' => $statusCounts,
            'by_priority' => $priorityCounts,
            'by_category' => $categoryCounts
        );
    }
}
?>
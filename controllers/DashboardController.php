<?php
// controllers/DashboardController.php - VERSION COMPLÈTE CORRIGÉE

require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Intervention.php';
require_once __DIR__ . '/../includes/functions.php';

class DashboardController {
    private $ticketModel;
    private $interventionModel;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->interventionModel = new Intervention();
    }
    
    public function index() {
        global $pageTitle;
        $pageTitle = 'Tableau de bord';
        
        try {
            $db = Database::getInstance();
            $userId = $_SESSION['user_id'] ?? 0;
            $role = $_SESSION['user_role'] ?? 'commercial';
            
            $stats = array();
            $recentTickets = array();
            $activities = array();
            $upcomingInterventions = array();
            
            // ============================================
            // 👑 ADMIN - voit TOUS les tickets
            // ============================================
            if ($role === 'admin') {
                $total = $db->fetch("SELECT COUNT(*) as count FROM tickets")['count'] ?? 0;
                
                $statusStats = $db->fetchAll("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
                $statusData = array();
                foreach ($statusStats as $stat) {
                    $statusData[$stat['status']] = (int)$stat['count'];
                }
                
                $priorityStats = $db->fetchAll("SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority");
                $priorityData = array();
                foreach ($priorityStats as $stat) {
                    $priorityData[$stat['priority']] = (int)$stat['count'];
                }
                
                $categoryStats = $db->fetchAll("SELECT category, COUNT(*) as count FROM tickets GROUP BY category");
                $categoryData = array();
                foreach ($categoryStats as $stat) {
                    $categoryData[$stat['category']] = (int)$stat['count'];
                }
                
                $stats = array(
                    'total' => $total,
                    'nouveau' => $statusData['nouveau'] ?? 0,
                    'assigne' => $statusData['assigne'] ?? 0,
                    'en_cours' => $statusData['en_cours'] ?? 0,
                    'en_attente' => $statusData['en_attente'] ?? 0,
                    'resolu' => $statusData['resolu'] ?? 0,
                    'cloture' => $statusData['cloture'] ?? 0,
                    'critique' => $priorityData['critique'] ?? 0,
                    'support_technique' => $categoryData['support_technique'] ?? 0,
                    'bureau_etude' => $categoryData['bureau_etude'] ?? 0,
                    'sav' => $categoryData['sav'] ?? 0,
                    'travaux' => $categoryData['travaux'] ?? 0,
                    'resolution_rate' => $total > 0 ? round((($statusData['resolu'] ?? 0) + ($statusData['cloture'] ?? 0)) / $total * 100, 1) : 0
                );
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, u.full_name as created_by_name 
                     FROM tickets t 
                     LEFT JOIN users u ON t.created_by = u.id 
                     ORDER BY t.created_at DESC 
                     LIMIT 10"
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     ORDER BY c.created_at DESC 
                     LIMIT 10"
                );
            }
            
            // ============================================
            // ✅ COORDINATEUR (Mikajy) - voit TOUS les tickets
            // ============================================
            elseif ($role === 'coordinateur') {
                $total = $db->fetch("SELECT COUNT(*) as count FROM tickets")['count'] ?? 0;
                
                $statusStats = $db->fetchAll("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
                $statusData = array();
                foreach ($statusStats as $stat) {
                    $statusData[$stat['status']] = (int)$stat['count'];
                }
                
                $priorityStats = $db->fetchAll("SELECT priority, COUNT(*) as count FROM tickets GROUP BY priority");
                $priorityData = array();
                foreach ($priorityStats as $stat) {
                    $priorityData[$stat['priority']] = (int)$stat['count'];
                }
                
                $categoryStats = $db->fetchAll("SELECT category, COUNT(*) as count FROM tickets GROUP BY category");
                $categoryData = array();
                foreach ($categoryStats as $stat) {
                    $categoryData[$stat['category']] = (int)$stat['count'];
                }
                
                $stats = array(
                    'total' => $total,
                    'nouveau' => $statusData['nouveau'] ?? 0,
                    'assigne' => $statusData['assigne'] ?? 0,
                    'en_cours' => $statusData['en_cours'] ?? 0,
                    'en_attente' => $statusData['en_attente'] ?? 0,
                    'resolu' => $statusData['resolu'] ?? 0,
                    'cloture' => $statusData['cloture'] ?? 0,
                    'critique' => $priorityData['critique'] ?? 0,
                    'support_technique' => $categoryData['support_technique'] ?? 0,
                    'bureau_etude' => $categoryData['bureau_etude'] ?? 0,
                    'sav' => $categoryData['sav'] ?? 0,
                    'travaux' => $categoryData['travaux'] ?? 0,
                    'resolution_rate' => $total > 0 ? round((($statusData['resolu'] ?? 0) + ($statusData['cloture'] ?? 0)) / $total * 100, 1) : 0
                );
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, u.full_name as created_by_name 
                     FROM tickets t 
                     LEFT JOIN users u ON t.created_by = u.id 
                     ORDER BY t.created_at DESC 
                     LIMIT 10"
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     ORDER BY c.created_at DESC 
                     LIMIT 10"
                );
            }
            
            // ============================================
            // ✅ RESPONSABLE SUPPORT TECHNIQUE (Mahery)
            // ============================================
            elseif ($role === 'responsable_support_technique') {
                $total = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')",
                    []
                )['count'] ?? 0;
                
                $stats['total'] = $total;
                $stats['en_attente'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')
                     AND status IN ('nouveau', 'assigne')",
                    []
                )['count'] ?? 0;
                
                $stats['en_cours'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')
                     AND status IN ('en_cours', 'en_attente')",
                    []
                )['count'] ?? 0;
                
                $stats['resolu'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')
                     AND status = 'resolu'",
                    []
                )['count'] ?? 0;
                
                $stats['critique'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')
                     AND priority = 'critique' AND status NOT IN ('resolu', 'cloture')",
                    []
                )['count'] ?? 0;
                
                $stats['cloture'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category IN ('support_technique', 'bureau_etude')
                     AND status = 'cloture'",
                    []
                )['count'] ?? 0;
                
                $stats['resolution_rate'] = $total > 0 ? round((($stats['resolu'] + $stats['cloture']) / $total) * 100, 1) : 0;
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, 
                            u1.full_name as created_by_name,
                            u2.full_name as assigned_to_name,
                            u2.role as assigned_to_role
                     FROM tickets t 
                     LEFT JOIN users u1 ON t.created_by = u1.id 
                     LEFT JOIN users u2 ON t.assigned_to = u2.id 
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     ORDER BY t.created_at DESC 
                     LIMIT 10"
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, 
                            u.full_name, 
                            u.role, 
                            t.title as ticket_title, 
                            t.ticket_number, 
                            t.status,
                            t.category
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     ORDER BY c.created_at DESC 
                     LIMIT 10"
                );
            }
            
            // ============================================
            // ✅ RESPONSABLE SAV (Dina)
            // ============================================
            elseif ($role === 'responsable_sav') {
                $total = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets WHERE category = 'sav'",
                    []
                )['count'] ?? 0;
                
                $stats['total'] = $total;
                $stats['en_attente'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'sav'
                     AND status IN ('nouveau', 'assigne')",
                    []
                )['count'] ?? 0;
                
                $stats['en_cours'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'sav'
                     AND status IN ('en_cours', 'en_attente')",
                    []
                )['count'] ?? 0;
                
                $stats['resolu'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'sav'
                     AND status = 'resolu'",
                    []
                )['count'] ?? 0;
                
                $stats['critique'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'sav'
                     AND priority = 'critique' AND status NOT IN ('resolu', 'cloture')",
                    []
                )['count'] ?? 0;
                
                $stats['cloture'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'sav'
                     AND status = 'cloture'",
                    []
                )['count'] ?? 0;
                
                $stats['resolution_rate'] = $total > 0 ? round((($stats['resolu'] + $stats['cloture']) / $total) * 100, 1) : 0;
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, 
                            u1.full_name as created_by_name,
                            u2.full_name as assigned_to_name,
                            u2.role as assigned_to_role
                     FROM tickets t 
                     LEFT JOIN users u1 ON t.created_by = u1.id 
                     LEFT JOIN users u2 ON t.assigned_to = u2.id 
                     WHERE t.category = 'sav'
                     ORDER BY t.created_at DESC 
                     LIMIT 10"
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, 
                            u.full_name, 
                            u.role, 
                            t.title as ticket_title, 
                            t.ticket_number, 
                            t.status,
                            t.category
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     WHERE t.category = 'sav'
                     ORDER BY c.created_at DESC 
                     LIMIT 10"
                );
            }
            
            // ============================================
            // ✅ RESPONSABLE TRAVAUX (Andry)
            // ============================================
            elseif ($role === 'responsable_travaux') {
                $total = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets WHERE category = 'travaux'",
                    []
                )['count'] ?? 0;
                
                $stats['total'] = $total;
                $stats['en_attente'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'travaux'
                     AND status IN ('nouveau', 'assigne')",
                    []
                )['count'] ?? 0;
                
                $stats['en_cours'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'travaux'
                     AND status IN ('en_cours', 'en_attente')",
                    []
                )['count'] ?? 0;
                
                $stats['resolu'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'travaux'
                     AND status = 'resolu'",
                    []
                )['count'] ?? 0;
                
                $stats['critique'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'travaux'
                     AND priority = 'critique' AND status NOT IN ('resolu', 'cloture')",
                    []
                )['count'] ?? 0;
                
                $stats['cloture'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE category = 'travaux'
                     AND status = 'cloture'",
                    []
                )['count'] ?? 0;
                
                $stats['resolution_rate'] = $total > 0 ? round((($stats['resolu'] + $stats['cloture']) / $total) * 100, 1) : 0;
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, 
                            u1.full_name as created_by_name,
                            u2.full_name as assigned_to_name,
                            u2.role as assigned_to_role
                     FROM tickets t 
                     LEFT JOIN users u1 ON t.created_by = u1.id 
                     LEFT JOIN users u2 ON t.assigned_to = u2.id 
                     WHERE t.category = 'travaux'
                     ORDER BY t.created_at DESC 
                     LIMIT 10"
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, 
                            u.full_name, 
                            u.role, 
                            t.title as ticket_title, 
                            t.ticket_number, 
                            t.status,
                            t.category
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     WHERE t.category = 'travaux'
                     ORDER BY c.created_at DESC 
                     LIMIT 10"
                );
            }
            
            // ============================================
            // ✅ CHARGÉS D'ÉTUDE
            // ============================================
            elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
                
                $total = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['total'] = $total;
                $stats['en_attente'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     AND t.status IN ('nouveau', 'assigne')",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['en_cours'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     AND t.status IN ('en_cours', 'en_attente')",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['resolu'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     AND t.status = 'resolu'",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['critique'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     AND t.priority = 'critique' 
                     AND t.status NOT IN ('resolu', 'cloture')",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['cloture'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets t
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     AND t.status = 'cloture'",
                    [$userId, $userId]
                )['count'] ?? 0;
                
                $stats['resolution_rate'] = $total > 0 ? round((($stats['resolu'] + $stats['cloture']) / $total) * 100, 1) : 0;
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, 
                            u1.full_name as created_by_name,
                            u2.full_name as assigned_to_name,
                            u2.role as assigned_to_role
                     FROM tickets t 
                     LEFT JOIN users u1 ON t.created_by = u1.id 
                     LEFT JOIN users u2 ON t.assigned_to = u2.id 
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     ORDER BY t.created_at DESC 
                     LIMIT 10",
                    [$userId, $userId]
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, 
                            u.full_name, 
                            u.role, 
                            t.title as ticket_title, 
                            t.ticket_number, 
                            t.status,
                            t.category
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     WHERE t.category IN ('support_technique', 'bureau_etude')
                     AND (t.assigned_to = ? 
                         OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))
                     ORDER BY c.created_at DESC 
                     LIMIT 10",
                    [$userId, $userId]
                );
                
                error_log("📊 Dashboard Chargé d'Étude (ID: $userId) - Total tickets: " . $stats['total']);
                error_log("📊 Tickets récents: " . count($recentTickets));
            }
            
            // ============================================
            // ✅ COMMERCIAL
            // ============================================
            elseif ($role === 'commercial') {
                $total = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets WHERE created_by = ?",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['total'] = $total;
                $stats['en_attente'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE created_by = ? AND status IN ('nouveau', 'assigne')",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['en_cours'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE created_by = ? AND status IN ('en_cours', 'en_attente')",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['resolu'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE created_by = ? AND status = 'resolu'",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['critique'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE created_by = ? AND priority = 'critique' AND status NOT IN ('resolu', 'cloture')",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['cloture'] = $db->fetch(
                    "SELECT COUNT(*) as count FROM tickets 
                     WHERE created_by = ? AND status = 'cloture'",
                    [$userId]
                )['count'] ?? 0;
                
                $stats['resolution_rate'] = $total > 0 ? round((($stats['resolu'] + $stats['cloture']) / $total) * 100, 1) : 0;
                
                $recentTickets = $db->fetchAll(
                    "SELECT t.*, u.full_name as created_by_name 
                     FROM tickets t 
                     LEFT JOIN users u ON t.created_by = u.id 
                     WHERE t.created_by = ?
                     ORDER BY t.created_at DESC 
                     LIMIT 10",
                    [$userId]
                );
                
                $activities = $db->fetchAll(
                    "SELECT c.*, u.full_name, u.role, t.title as ticket_title, t.ticket_number, t.status
                     FROM comments c 
                     INNER JOIN users u ON c.user_id = u.id 
                     INNER JOIN tickets t ON c.ticket_id = t.id 
                     WHERE t.created_by = ?
                     ORDER BY c.created_at DESC 
                     LIMIT 10",
                    [$userId]
                );
            }
            
            // ============================================
            // ❌ RÔLE NON RECONNU
            // ============================================
            else {
                $stats = array(
                    'total' => 0,
                    'en_attente' => 0,
                    'en_cours' => 0,
                    'resolu' => 0,
                    'cloture' => 0,
                    'critique' => 0,
                    'resolution_rate' => 0
                );
                $recentTickets = array();
                $activities = array();
            }
            
        } catch (Exception $e) {
            $stats = array(
                'total' => 0, 
                'en_attente' => 0, 
                'en_cours' => 0,
                'resolu' => 0, 
                'cloture' => 0, 
                'critique' => 0,
                'resolution_rate' => 0
            );
            $recentTickets = array();
            $activities = array();
            
            error_log("DashboardController Error: " . $e->getMessage());
        }
        
        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
?>
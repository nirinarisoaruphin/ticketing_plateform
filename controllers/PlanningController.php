<?php
// controllers/PlanningController.php - VERSION COMPLÈTE AVEC AUTOMATISATION

require_once __DIR__ . '/../models/Intervention.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Mailer.php';

class PlanningController {
    private $interventionModel;
    private $ticketModel;
    private $userModel;
    private $notificationModel;
    private $mailer;
    private $db;
    
    public function __construct() {
        $this->interventionModel = new Intervention();
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
        $this->mailer = new Mailer();
        $this->db = Database::getInstance();
    }
    
    // ============================================
    // INDEX - LISTE DES INTERVENTIONS
    // ============================================
    public function index() {
        global $pageTitle;
        $pageTitle = 'Planning des interventions';
        
        // ✅ Vérifier que l'utilisateur peut voir le planning
        if (!canViewPlanningOnly()) {
            setFlash('danger', 'Vous n\'avez pas accès au planning.');
            redirect('index.php?page=dashboard');
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        $isCommercial = isCommercial();
        $canManage = canManagePlanning();
        
        // ============================================
        // RÉCUPÉRER LES INTERVENTIONS
        // ============================================
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
        
        // RÈGLES DE VISIBILITÉ
        if ($role === 'commercial') {
            // Commercial voit tout en lecture seule
        } elseif ($role === 'responsable_support_technique') {
            $sql .= " AND t.category IN ('support_technique', 'bureau_etude')";
        } elseif ($role === 'responsable_sav') {
            $sql .= " AND t.category = 'sav'";
        } elseif ($role === 'responsable_travaux') {
            $sql .= " AND t.category = 'travaux'";
        } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            $sql .= " AND (t.assigned_to = ? OR t.id IN (SELECT ticket_id FROM ticket_assignments WHERE user_id = ? AND is_active = 1))";
            $sql .= " AND t.category IN ('support_technique', 'bureau_etude')";
            $params[] = $userId;
            $params[] = $userId;
        }
        
        // Filtres
        if (isset($_GET['technician']) && $_GET['technician']) {
            $sql .= " AND i.technician_id = ?";
            $params[] = (int)$_GET['technician'];
        }
        if (isset($_GET['status']) && $_GET['status']) {
            $sql .= " AND i.status = ?";
            $params[] = $_GET['status'];
        }
        if (isset($_GET['date_from']) && $_GET['date_from']) {
            $sql .= " AND i.planned_date >= ?";
            $params[] = $_GET['date_from'];
        }
        if (isset($_GET['date_to']) && $_GET['date_to']) {
            $sql .= " AND i.planned_date <= ?";
            $params[] = $_GET['date_to'];
        }
        
        $sql .= " ORDER BY i.planned_date ASC, i.planned_time ASC";
        
        $interventions = $this->db->fetchAll($sql, $params);
        $technicians = $this->userModel->getTechnicians();
        
        require_once __DIR__ . '/../views/planning/index.php';
    }
    
    // ============================================
    // CRÉER UNE INTERVENTION
    // ============================================
    public function create() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de planifier des interventions.');
            redirect('index.php?page=planning');
        }
        
        global $pageTitle;
        $pageTitle = 'Planifier une intervention';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $existing = $this->interventionModel->findByTicketId($_POST['ticket_id']);
            
            if ($existing) {
                setFlash('danger', 'Ce ticket a déjà une intervention planifiée.');
                redirect('index.php?page=planning&action=create');
            }
            
            $data = [
                'ticket_id' => (int)$_POST['ticket_id'],
                'technician_id' => (int)$_POST['technician_id'],
                'planned_date' => $_POST['planned_date'],
                'planned_time' => $_POST['planned_time'],
                'duration' => (int)($_POST['duration'] ?? 60),
                'notes' => sanitize($_POST['notes'] ?? ''),
                'status' => 'planifiee'
            ];
            
            $conflict = $this->interventionModel->checkConflict(
                $data['technician_id'], 
                $data['planned_date'], 
                $data['planned_time']
            );
            
            if ($conflict) {
                setFlash('danger', 'Ce créneau est déjà occupé pour ce responsable.');
                redirect('index.php?page=planning&action=create');
            }
            
            $this->interventionModel->create($data);
            $this->ticketModel->update($_POST['ticket_id'], [
                'status' => 'assigne', 
                'assigned_to' => $_POST['technician_id']
            ]);
            
            $this->notifyTechnician($data['technician_id'], $data);
            $this->notifyPlanningCreated($data);
            
            setFlash('success', 'Intervention planifiée avec succès. Elle démarrera automatiquement à l\'heure prévue.');
            redirect('index.php?page=planning');
        }
        
        $tickets = $this->ticketModel->getTicketsWithoutIntervention();
        $technicians = $this->userModel->getTechnicians();
        
        require_once __DIR__ . '/../views/planning/create.php';
    }
    
    // ============================================
    // MODIFIER UNE INTERVENTION
    // ============================================
    public function edit() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de modifier les interventions.');
            redirect('index.php?page=planning');
        }
        
        global $pageTitle;
        $pageTitle = 'Modifier l\'intervention';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'technician_id' => (int)$_POST['technician_id'],
                'planned_date' => $_POST['planned_date'],
                'planned_time' => $_POST['planned_time'],
                'duration' => (int)($_POST['duration'] ?? 60),
                'status' => $_POST['status'],
                'notes' => sanitize($_POST['notes'] ?? '')
            ];
            
            $this->interventionModel->update($id, $data);
            
            if ($data['status'] === 'realisee') {
                $this->ticketModel->update($intervention['ticket_id'], [
                    'status' => 'resolu', 
                    'resolved_at' => date('Y-m-d H:i:s')
                ]);
            } elseif ($data['status'] === 'annulee') {
                $this->ticketModel->update($intervention['ticket_id'], [
                    'status' => 'nouveau', 
                    'assigned_to' => null
                ]);
            }
            
            setFlash('success', 'Intervention mise à jour avec succès.');
            redirect('index.php?page=planning');
        }
        
        $technicians = $this->userModel->getTechnicians();
        require_once __DIR__ . '/../views/planning/edit.php';
    }
    
    // ============================================
    // DÉMARRER UNE INTERVENTION (MANUEL)
    // ============================================
    public function start() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de démarrer des interventions.');
            redirect('index.php?page=planning');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($intervention['status'] !== 'planifiee') {
            setFlash('danger', 'Cette intervention ne peut pas être démarrée.');
            redirect('index.php?page=planning');
        }
        
        $this->interventionModel->autoStart($id);
        $this->ticketModel->update($intervention['ticket_id'], ['status' => 'en_cours']);
        
        $this->addHistory($id, 'Démarrée', 'Intervention démarrée par ' . $_SESSION['user_name']);
        $this->notifyStatusChange($intervention, 'démarrée');
        
        setFlash('success', 'Intervention démarrée avec succès.');
        redirect('index.php?page=planning');
    }
    
    // ============================================
    // TERMINER UNE INTERVENTION (MANUEL)
    // ============================================
    public function complete() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de terminer des interventions.');
            redirect('index.php?page=planning');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($intervention['status'] !== 'en_cours' && $intervention['status'] !== 'en_attente') {
            setFlash('danger', 'Cette intervention ne peut pas être terminée.');
            redirect('index.php?page=planning');
        }
        
        // Calculer la durée réelle
        $actualDuration = $intervention['duration'];
        if (!empty($intervention['actual_start'])) {
            $start = new DateTime($intervention['actual_start']);
            $end = new DateTime();
            $actualDuration = $end->diff($start)->i;
        }
        
        $this->interventionModel->autoComplete($id, $actualDuration);
        $this->ticketModel->update($intervention['ticket_id'], [
            'status' => 'resolu',
            'resolved_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->addHistory($id, 'Terminée', 'Intervention terminée par ' . $_SESSION['user_name']);
        $this->notifyStatusChange($intervention, 'terminée');
        
        setFlash('success', 'Intervention terminée avec succès.');
        redirect('index.php?page=planning');
    }
    
    // ============================================
    // ANNULER UNE INTERVENTION
    // ============================================
    public function cancel() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission d\'annuler des interventions.');
            redirect('index.php?page=planning');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($intervention['status'] === 'realisee' || $intervention['status'] === 'annulee') {
            setFlash('danger', 'Cette intervention ne peut pas être annulée.');
            redirect('index.php?page=planning');
        }
        
        $this->interventionModel->update($id, ['status' => 'annulee', 'updated_at' => date('Y-m-d H:i:s')]);
        $this->ticketModel->update($intervention['ticket_id'], [
            'status' => 'nouveau',
            'assigned_to' => null
        ]);
        
        $this->addHistory($id, 'Annulée', 'Intervention annulée par ' . $_SESSION['user_name']);
        $this->notifyStatusChange($intervention, 'annulée');
        
        setFlash('success', 'Intervention annulée avec succès.');
        redirect('index.php?page=planning');
    }
    
    // ============================================
    // SUPPRIMER UNE INTERVENTION
    // ============================================
    public function delete() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de supprimer des interventions.');
            redirect('index.php?page=planning');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($intervention['status'] !== 'annulee' && $intervention['status'] !== 'realisee') {
            setFlash('danger', 'Seules les interventions annulées ou réalisées peuvent être supprimées.');
            redirect('index.php?page=planning');
        }
        
        $this->interventionModel->delete($id);
        $this->ticketModel->update($intervention['ticket_id'], [
            'status' => 'nouveau',
            'assigned_to' => null
        ]);
        
        setFlash('success', 'Intervention supprimée avec succès.');
        redirect('index.php?page=planning');
    }
    
    // ============================================
    // AJOUTER UNE NOTE
    // ============================================
    public function addNote() {
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission d\'ajouter des notes.');
            redirect('index.php?page=planning');
        }
        
        global $pageTitle;
        $pageTitle = 'Ajouter une note';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $note = sanitize($_POST['note'] ?? '');
            if (empty($note)) {
                setFlash('danger', 'La note ne peut pas être vide.');
                redirect('index.php?page=planning&action=add_note&id=' . $id);
            }
            
            $oldNotes = $intervention['notes'] ?? '';
            $timestamp = date('d/m/Y H:i');
            $newNotes = $oldNotes . "\n[" . $timestamp . "] " . $note;
            
            $this->interventionModel->update($id, ['notes' => $newNotes]);
            $this->addHistory($id, 'Note ajoutée', 'Note ajoutée par ' . $_SESSION['user_name'] . ': ' . $note);
            
            setFlash('success', 'Note ajoutée avec succès.');
            redirect('index.php?page=planning');
        }
        
        require_once __DIR__ . '/../views/planning/add_note.php';
    }
    
    // ============================================
    // HISTORIQUE
    // ============================================
    public function history() {
        global $pageTitle;
        $pageTitle = 'Historique de l\'intervention';
        
        if (!canManagePlanning()) {
            setFlash('danger', 'Vous n\'avez pas la permission de voir l\'historique.');
            redirect('index.php?page=planning');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $this->interventionModel->findById($id);
        
        if (!$intervention) {
            setFlash('danger', 'Intervention non trouvée.');
            redirect('index.php?page=planning');
        }
        
        $history = $this->db->fetchAll(
            "SELECT h.*, u.full_name as user_name
             FROM intervention_history h
             LEFT JOIN users u ON h.user_id = u.id
             WHERE h.intervention_id = ?
             ORDER BY h.created_at DESC",
            [$id]
        );
        
        require_once __DIR__ . '/../views/planning/history.php';
    }
    
    // ============================================
    // ✅ CHECK STATUS - POLLING AJAX
    // ============================================
    public function checkStatus() {
        header('Content-Type: application/json');
        
        if (!isLoggedIn()) {
            echo json_encode(['error' => 'Non authentifié']);
            exit;
        }
        
        $db = Database::getInstance();
        
        // Vérifier si des interventions ont changé de statut récemment
        $changed = $db->fetch(
            "SELECT COUNT(*) as count FROM interventions 
             WHERE status IN ('en_cours', 'realisee', 'annulee') 
             AND updated_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)"
        );
        
        // Vérifier si des tickets ont été résolus automatiquement
        $resolved = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets 
             WHERE status = 'resolu' 
             AND resolved_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)"
        );
        
        $interventionCount = $changed['count'] ?? 0;
        $ticketCount = $resolved['count'] ?? 0;
        $total = $interventionCount + $ticketCount;
        
        echo json_encode([
            'updated' => $total > 0,
            'count' => $total,
            'interventions' => $interventionCount,
            'tickets' => $ticketCount,
            'message' => $this->getStatusMessage($interventionCount, $ticketCount)
        ]);
        exit;
    }
    
    private function getStatusMessage($interventions, $tickets) {
        $parts = [];
        if ($interventions > 0) {
            $parts[] = $interventions . ' intervention(s)';
        }
        if ($tickets > 0) {
            $parts[] = $tickets . ' ticket(s) résolu(s)';
        }
        return '🔄 ' . implode(' et ', $parts) . ' mis à jour automatiquement';
    }
    
    // ============================================
    // NOTIFICATIONS
    // ============================================
    
    private function notifyTechnician($technicianId, $data) {
        $technician = $this->db->fetch("SELECT full_name, email FROM users WHERE id = ?", [$technicianId]);
        $ticket = $this->db->fetch("SELECT ticket_number, title FROM tickets WHERE id = ?", [$data['ticket_id']]);
        
        if ($technician && $ticket) {
            $this->notificationModel->createNotification(
                $technicianId,
                "📅 Nouvelle intervention: {$ticket['ticket_number']} le {$data['planned_date']} à {$data['planned_time']}",
                "index.php?page=planning",
                'planning'
            );
        }
    }
    
    private function notifyPlanningCreated($data) {
        // Notifier le créateur du ticket
        $ticket = $this->db->fetch("SELECT created_by FROM tickets WHERE id = ?", [$data['ticket_id']]);
        if ($ticket && !empty($ticket['created_by'])) {
            $this->notificationModel->createNotification(
                $ticket['created_by'],
                "📅 Une intervention a été planifiée pour votre ticket",
                "index.php?page=planning",
                'planning'
            );
        }
    }
    
    private function notifyStatusChange($intervention, $status) {
        $db = Database::getInstance();
        $link = "index.php?page=planning";
        $ticketNumber = $intervention['ticket_number'] ?? 'N/A';
        
        $statusMessages = [
            'démarrée' => "🔄 L'intervention #{$ticketNumber} a été démarrée",
            'terminée' => "✅ L'intervention #{$ticketNumber} est terminée",
            'annulée' => "❌ L'intervention #{$ticketNumber} a été annulée"
        ];
        
        $message = $statusMessages[$status] ?? "📌 Intervention #{$ticketNumber} mise à jour";
        
        // Notifier le technicien
        if (!empty($intervention['technician_id'])) {
            $this->notificationModel->createNotification(
                $intervention['technician_id'],
                $message,
                $link,
                'planning'
            );
        }
        
        // Notifier le créateur du ticket
        if (!empty($intervention['created_by'])) {
            $this->notificationModel->createNotification(
                $intervention['created_by'],
                $message,
                $link,
                'planning'
            );
        }
        
        // Notifier les responsables
        $responsables = $db->fetchAll(
            "SELECT id FROM users WHERE role IN ('responsable_support_technique', 'coordinateur', 'admin')"
        );
        foreach ($responsables as $resp) {
            $this->notificationModel->createNotification(
                $resp['id'],
                $message,
                $link,
                'planning'
            );
        }
    }
    
    private function addHistory($interventionId, $action, $details) {
        $this->db->insert(
            "INSERT INTO intervention_history (intervention_id, user_id, action, details, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$interventionId, $_SESSION['user_id'] ?? 0, $action, $details]
        );
    }
}
?>
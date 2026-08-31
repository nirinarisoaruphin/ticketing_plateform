<?php
// controllers/TicketController.php - VERSION COMPLÈTE CORRIGÉE
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/Mailer.php';

class TicketController {
    private $ticketModel;
    private $userModel;
    private $notificationModel;
    private $notificationManager;
    private $mailer;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
        $this->notificationManager = new NotificationManager();
        $this->mailer = new Mailer();
    }
    
    // ============================================
    // LISTE DES TICKETS
    // ============================================
    public function index() {
        global $pageTitle;
        $pageTitle = 'Liste des tickets';
        
        $filters = array();
        if (isset($_GET['status']) && $_GET['status']) $filters['status'] = $_GET['status'];
        if (isset($_GET['priority']) && $_GET['priority']) $filters['priority'] = $_GET['priority'];
        
        $tickets = $this->ticketModel->getTicketsWithUserInfo($filters);
        $technicians = $this->userModel->getTechnicians();
        
        require_once __DIR__ . '/../views/tickets/list.php';
    }
    
    // ============================================
    // AFFICHER UN TICKET
    // ============================================
    public function show() {
        global $pageTitle;
        $pageTitle = 'Détail du ticket';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            setFlash('danger', 'ID de ticket invalide.');
            redirect('index.php?page=tickets');
        }
        
        $ticket = $this->ticketModel->getTicketDetails($id);
        
        if (!$ticket) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        // Vérifier les permissions d'accès
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$this->canViewTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas accès à ce ticket.');
            redirect('index.php?page=tickets');
        }
        
        // Récupérer les commentaires
        $comments = $this->ticketModel->getCommentsByTicket($id);
        
        // Récupérer les techniciens pour l'assignation
        $technicians = $this->userModel->getTechnicians();
        
        // Vérifier les permissions d'action
        $canEdit = $this->canEditTicket($ticket);
        $canDelete = $this->canDeleteTicket($ticket);
        $canComment = $this->canCommentTicket($ticket);
        $canAct = $this->canActOnTicket($ticket);
        
        require_once __DIR__ . '/../views/tickets/show.php';
    }
    
    // ============================================
    // CRÉER UN TICKET
    // ============================================
    public function create() {
        global $pageTitle;
        $pageTitle = 'Nouveau ticket';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $category = isset($_POST['category']) ? $_POST['category'] : 'support_technique';
            $typeDemande = isset($_POST['type_demande']) ? $_POST['type_demande'] : 'etude';
            
            $categoryResponsibleMap = [
                'support_technique' => 'responsable_support_technique',
                'sav' => 'responsable_sav',
                'travaux' => 'responsable_travaux'
            ];
            
            $responsibleRole = $categoryResponsibleMap[$category] ?? null;
            
            $db = Database::getInstance();
            $assignedTo = null;
            if ($responsibleRole) {
                $responsible = $db->fetch(
                    "SELECT id FROM users WHERE role = ? LIMIT 1",
                    [$responsibleRole]
                );
                $assignedTo = $responsible ? $responsible['id'] : null;
            }
            
            $ticketNumber = generateTicketNumber($category);
            
            $data = array(
                'ticket_number' => $ticketNumber,
                'title' => sanitize($_POST['title']),
                'description' => sanitize($_POST['description']),
                'category' => $category,
                'type_demande' => $typeDemande,
                'priority' => isset($_POST['priority']) ? $_POST['priority'] : 'moyenne',
                'status' => 'nouveau',
                'validation_status' => 'en_attente',
                'created_by' => $_SESSION['user_id'],
                'assigned_to' => $assignedTo,
                'commercial_dedie' => sanitize($_POST['commercial_dedie'] ?? $_SESSION['user_name']),
                'client_name' => sanitize($_POST['client_name'] ?? ''),
                'adresse_client' => sanitize($_POST['adresse_client'] ?? ''),
                'interlocuteur' => sanitize($_POST['interlocuteur'] ?? ''),
                'contact_technique' => sanitize($_POST['contact_technique'] ?? ''),
                'lieu_visite' => sanitize($_POST['lieu_visite'] ?? ''),
                'visite_date' => !empty($_POST['visite_date']) ? $_POST['visite_date'] : null,
                'visite_heure' => !empty($_POST['visite_heure']) ? $_POST['visite_heure'] : null,
                'moyen_transport' => sanitize($_POST['moyen_transport'] ?? ''),
                'elements_complement' => sanitize($_POST['elements_complement'] ?? '')
            );
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                $filename = 'ticket_' . date('YmdHis') . '.' . $extension;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                    $data['attachment'] = 'uploads/' . $filename;
                }
            }
            
            $ticketId = $this->ticketModel->create($data);
            
            if ($ticketId) {
                $ticket = $this->ticketModel->getTicketDetails($ticketId);
                $link = "index.php?page=tickets&action=show&id=" . $ticketId;
                
                // ============================================
                // NOTIFICATIONS IN-APP
                // ============================================
                
                // 1. Notifier le responsable assigné
                if ($assignedTo) {
                    $assignMessage = "📌 Nouveau ticket #{$ticket['ticket_number']} : {$ticket['title']}";
                    $this->notificationModel->notifyUser($assignedTo, $assignMessage, $link);
                }
                
                // 2. Notifier les chargés d'étude
                if (in_array($category, ['support_technique', 'bureau_etude'])) {
                    $charges = $db->fetchAll(
                        "SELECT id, full_name FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
                    );
                    foreach ($charges as $charge) {
                        $chargeMessage = "📌 Nouveau ticket #{$ticket['ticket_number']} en attente d'étude : {$ticket['title']}";
                        $this->notificationModel->notifyUser($charge['id'], $chargeMessage, $link);
                    }
                }
                
                // 3. Notifier le Coordinateur
                $coordinateurs = $db->fetchAll("SELECT id FROM users WHERE role = 'coordinateur'");
                foreach ($coordinateurs as $coord) {
                    $this->notificationModel->notifyUser($coord['id'], "📌 Nouveau ticket #{$ticket['ticket_number']} créé", $link);
                }
                
                // 4. Notifier l'Admin
                $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
                foreach ($admins as $admin) {
                    $this->notificationModel->notifyUser($admin['id'], "📌 Nouveau ticket #{$ticket['ticket_number']} créé", $link);
                }
                
                // 5. Notifier le créateur
                $confirmMessage = "✅ Votre demande #{$ticket['ticket_number']} a été créée avec succès.";
                $this->notificationModel->notifyUser($ticket['created_by'], $confirmMessage, $link);
                
                // ============================================
                // ENVOI D'EMAIL
                // ============================================
                try {
                    $this->notificationManager->notifyTicketCreated($ticket);
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi des emails: " . $e->getMessage());
                }
                
                setFlash('success', 'Demande créée avec succès ! Numéro : ' . $ticketNumber);
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            } else {
                setFlash('danger', 'Erreur lors de la création de la demande.');
                redirect('index.php?page=tickets&action=create');
            }
        }
        
        require_once __DIR__ . '/../views/tickets/create.php';
    }
    
    // ============================================
    // MODIFIER UN TICKET - CORRIGÉE
    // ============================================
    public function edit() {
        global $pageTitle;
        $pageTitle = 'Détail du ticket';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            setFlash('danger', 'ID de ticket invalide.');
            redirect('index.php?page=tickets');
        }
        
        // ✅ Récupérer le ticket
        $ticket = $this->ticketModel->getTicketDetails($id);
        
        // ✅ VÉRIFIER SI LE TICKET EXISTE AVANT TOUTE UTILISATION
        if (!$ticket || empty($ticket)) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ✅ VÉRIFICATION DES PERMISSIONS D'ACCÈS
        if (!$this->canViewTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas accès à ce ticket.');
            redirect('index.php?page=tickets');
        }
        
        // ✅ VÉRIFICATION DES PERMISSIONS D'ÉDITION
        if (!$this->canEditTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas la permission de modifier ce ticket.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        // ❌ Si le ticket est clôturé, bloquer (sauf admin)
        if ($ticket['status'] === 'cloture' && $role !== 'admin') {
            setFlash('danger', 'Ce ticket est clôturé et ne peut plus être modifié.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        // ❌ Si le ticket est résolu, bloquer (sauf admin et coordinateur)
        if ($ticket['status'] === 'resolu' && !in_array($role, ['admin', 'coordinateur'])) {
            setFlash('danger', 'Ce ticket est résolu et ne peut plus être modifié.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $oldStatus = $ticket['status'] ?? 'nouveau';
            $oldAssignedTo = $ticket['assigned_to'] ?? null;
            $newStatus = isset($_POST['status']) ? $_POST['status'] : $ticket['status'];
            
            if ($ticket['status'] === 'cloture') {
                setFlash('danger', 'Ce ticket est clôturé et ne peut plus être modifié.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            if ($newStatus === 'cloture') {
                $data = array('status' => 'cloture');
            } else {
                $data = array(
                    'title' => sanitize($_POST['title'] ?? $ticket['title']),
                    'description' => sanitize($_POST['description'] ?? $ticket['description']),
                    'category' => $_POST['category'] ?? $ticket['category'],
                    'priority' => $_POST['priority'] ?? $ticket['priority'],
                    'status' => $newStatus,
                    'type_demande' => sanitize($_POST['type_demande'] ?? $ticket['type_demande'] ?? 'etude'),
                    'client_name' => sanitize($_POST['client_name'] ?? $ticket['client_name'] ?? ''),
                    'adresse_client' => sanitize($_POST['adresse_client'] ?? $ticket['adresse_client'] ?? ''),
                    'interlocuteur' => sanitize($_POST['interlocuteur'] ?? $ticket['interlocuteur'] ?? ''),
                    'contact_technique' => sanitize($_POST['contact_technique'] ?? $ticket['contact_technique'] ?? ''),
                    'lieu_visite' => sanitize($_POST['lieu_visite'] ?? $ticket['lieu_visite'] ?? ''),
                    'visite_date' => !empty($_POST['visite_date']) ? $_POST['visite_date'] : ($ticket['visite_date'] ?? null),
                    'visite_heure' => !empty($_POST['visite_heure']) ? $_POST['visite_heure'] : ($ticket['visite_heure'] ?? null),
                    'moyen_transport' => sanitize($_POST['moyen_transport'] ?? $ticket['moyen_transport'] ?? ''),
                    'elements_complement' => sanitize($_POST['elements_complement'] ?? $ticket['elements_complement'] ?? '')
                );
                
                if (in_array($role, array('admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux'))) {
                    $data['assigned_to'] = isset($_POST['assigned_to']) && $_POST['assigned_to'] ? $_POST['assigned_to'] : null;
                }
                
                if ($newStatus === 'resolu') {
                    $data['resolved_at'] = date('Y-m-d H:i:s');
                }
            }
            
            $this->ticketModel->update($id, $data);
            
            $updatedTicket = $this->ticketModel->getTicketDetails($id);
            $link = "index.php?page=tickets&action=show&id=" . $id;
            
            $db = Database::getInstance();
            
            // ============================================
            // CHANGEMENT DE STATUT
            // ============================================
            if ($oldStatus !== $updatedTicket['status']) {
                $statusMessage = "📊 Le ticket #{$updatedTicket['ticket_number']} a changé de statut : " . 
                                getStatusLabel($oldStatus) . " → " . getStatusLabel($updatedTicket['status']);
                
                $this->notificationModel->notifyUser($updatedTicket['created_by'], $statusMessage, $link);
                
                if (!empty($updatedTicket['assigned_to'])) {
                    $this->notificationModel->notifyUser($updatedTicket['assigned_to'], $statusMessage, $link);
                }
                
                if (in_array($updatedTicket['category'], ['support_technique', 'bureau_etude'])) {
                    $charges = $db->fetchAll(
                        "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
                    );
                    foreach ($charges as $charge) {
                        if ($charge['id'] != $_SESSION['user_id']) {
                            $this->notificationModel->notifyUser($charge['id'], $statusMessage, $link);
                        }
                    }
                }
                
                if ($updatedTicket['status'] === 'resolu') {
                    $resolveMessage = "✅ Le ticket #{$updatedTicket['ticket_number']} a été résolu.";
                    $this->notificationModel->notifyUser($updatedTicket['created_by'], $resolveMessage, $link);
                    $this->notificationModel->notifyTechnicians("✅ Ticket #{$updatedTicket['ticket_number']} résolu", $link);
                }
                
                if ($updatedTicket['status'] === 'cloture') {
                    $closeMessage = "🔒 Le ticket #{$updatedTicket['ticket_number']} a été clôturé.";
                    $this->notificationModel->notifyUser($updatedTicket['created_by'], $closeMessage, $link);
                }
                
                try {
                    $this->notificationManager->notifyStatusChange($updatedTicket, $oldStatus, $updatedTicket['status']);
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
            }
            
            // ============================================
            // CHANGEMENT D'ASSIGNATION AVEC EMAIL POUR CHARGÉ D'ÉTUDE
            // ============================================
            if ($oldAssignedTo != $updatedTicket['assigned_to'] && !empty($updatedTicket['assigned_to'])) {
                $assignMessage = "👤 Le ticket #{$updatedTicket['ticket_number']} vous a été assigné.";
                $this->notificationModel->notifyUser($updatedTicket['assigned_to'], $assignMessage, $link);
                
                $notifyCreator = "👤 Le ticket #{$updatedTicket['ticket_number']} a été assigné à un technicien.";
                $this->notificationModel->notifyUser($updatedTicket['created_by'], $notifyCreator, $link);
                
                // ✅ ENVOI D'EMAIL AU CHARGÉ D'ÉTUDE ASSIGNÉ
                $assignedUser = $db->fetch("SELECT email, full_name, role FROM users WHERE id = ?", [$updatedTicket['assigned_to']]);
                if ($assignedUser && in_array($assignedUser['role'], ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
                    try {
                        $this->sendAssignmentEmail($updatedTicket, $assignedUser);
                    } catch (Exception $e) {
                        error_log("Erreur d'envoi d'email d'assignation: " . $e->getMessage());
                    }
                }
                
                // ✅ ENVOI D'EMAIL DE CONFIRMATION AU RESPONSABLE
                $assigner = $db->fetch("SELECT email, full_name FROM users WHERE id = ?", [$userId]);
                if ($assigner && $assignedUser) {
                    try {
                        $this->sendAssignmentConfirmationEmail($updatedTicket, $assigner, $assignedUser);
                    } catch (Exception $e) {
                        error_log("Erreur d'envoi d'email de confirmation: " . $e->getMessage());
                    }
                }
            }
            
            if ($newStatus === 'cloture') {
                setFlash('success', 'Ticket clôturé avec succès !');
            } else {
                setFlash('success', 'Ticket mis à jour avec succès !');
            }
            
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        $technicians = $this->userModel->getTechnicians();
        require_once __DIR__ . '/../views/tickets/edit.php';
    }
    
    // ============================================
    // ENVOYER UN EMAIL D'ASSIGNATION AU CHARGÉ D'ÉTUDE
    // ============================================
    private function sendAssignmentEmail($ticket, $assignedUser) {
        $subject = "📌 Ticket #{$ticket['ticket_number']} assigné - " . getRoleLabel($assignedUser['role']);
        
        $message = "
            <h2>📌 Nouvelle assignation de ticket</h2>
            <p>Bonjour <strong>" . htmlspecialchars($assignedUser['full_name']) . "</strong>,</p>
            <p>Un ticket vous a été assigné par <strong>" . $_SESSION['user_name'] . "</strong>.</p>
            <div style='background:#f8fafc;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #2563eb;'>
                <p><strong>Ticket :</strong> #{$ticket['ticket_number']}</p>
                <p><strong>Titre :</strong> {$ticket['title']}</p>
                <p><strong>Catégorie :</strong> " . getCategoryLabel($ticket['category']) . "</p>
                <p><strong>Statut :</strong> " . getStatusLabel($ticket['status']) . "</p>
                <p><strong>Priorité :</strong> " . getPriorityLabel($ticket['priority']) . "</p>
                <p><strong>Client :</strong> " . ($ticket['client_name'] ?? 'Non renseigné') . "</p>
            </div>
            <p style='text-align:center;margin:25px 0;'>
                <a href='" . APP_URL . "/index.php?page=tickets&action=show&id=" . $ticket['id'] . "' style='display:inline-block;background:#2563eb;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>Voir le ticket</a>
            </p>
            <hr>
            <p style='font-size:12px;color:#64748b;'>Cet email est un message automatique. Merci de ne pas y répondre directement.</p>
        ";
        
        $htmlMessage = Mailer::getTemplate('📌 Nouvelle assignation', $message);
        return $this->mailer->send($assignedUser['email'], $subject, $htmlMessage, $assignedUser['full_name']);
    }
    
    // ============================================
    // ENVOYER UN EMAIL DE CONFIRMATION AU RESPONSABLE
    // ============================================
    private function sendAssignmentConfirmationEmail($ticket, $responsable, $assignedUser) {
        $subject = "✅ Ticket #{$ticket['ticket_number']} assigné à " . $assignedUser['full_name'];
        
        $message = "
            <h2>✅ Confirmation d'assignation</h2>
            <p>Bonjour <strong>" . htmlspecialchars($responsable['full_name']) . "</strong>,</p>
            <p>Le ticket a été assigné avec succès à <strong>" . htmlspecialchars($assignedUser['full_name']) . "</strong>.</p>
            <div style='background:#f8fafc;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #10B981;'>
                <p><strong>Ticket :</strong> #{$ticket['ticket_number']}</p>
                <p><strong>Titre :</strong> {$ticket['title']}</p>
                <p><strong>Assigné à :</strong> " . htmlspecialchars($assignedUser['full_name']) . "</p>
                <p><strong>Statut :</strong> " . getStatusLabel($ticket['status']) . "</p>
            </div>
            <p style='text-align:center;margin:25px 0;'>
                <a href='" . APP_URL . "/index.php?page=tickets&action=show&id=" . $ticket['id'] . "' style='display:inline-block;background:#2563eb;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>Voir le ticket</a>
            </p>
            <hr>
            <p style='font-size:12px;color:#64748b;'>Cet email est un message automatique. Merci de ne pas y répondre directement.</p>
        ";
        
        $htmlMessage = Mailer::getTemplate('✅ Confirmation d\'assignation', $message);
        return $this->mailer->send($responsable['email'], $subject, $htmlMessage, $responsable['full_name']);
    }
    
    // ============================================
    // SUPPRIMER UN TICKET
    // ============================================
    public function delete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            setFlash('danger', 'ID de ticket invalide.');
            redirect('index.php?page=tickets');
        }
        
        $ticket = $this->ticketModel->getTicketDetails($id);
        
        if (!$ticket) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        if (!in_array($role, ['admin', 'coordinateur'])) {
            setFlash('danger', 'Vous n\'avez pas la permission de supprimer ce ticket.');
            redirect('index.php?page=tickets');
        }
        
        if ($ticket['status'] === 'cloture') {
            setFlash('danger', 'Ce ticket est clôturé et ne peut pas être supprimé.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        $this->ticketModel->delete($id);
        setFlash('success', 'Ticket supprimé avec succès.');
        redirect('index.php?page=tickets');
    }
    
    // ============================================
    // AJOUTER UN COMMENTAIRE
    // ============================================
    public function addComment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $content = sanitize($_POST['content']);
            
            if ($ticketId <= 0) {
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($ticketId);
            
            if (!$ticket) {
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $role = $_SESSION['user_role'] ?? 'commercial';
            $userId = $_SESSION['user_id'] ?? 0;
            
            if (!$this->canCommentTicket($ticket)) {
                setFlash('danger', 'Vous n\'avez pas la permission de commenter ce ticket.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
                setFlash('danger', 'Ce ticket est ' . strtolower(getStatusLabel($ticket['status'])) . '. Les commentaires ne sont plus autorisés.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if (empty($content)) {
                setFlash('danger', 'Le commentaire ne peut pas être vide.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            $this->ticketModel->addComment($ticketId, $_SESSION['user_id'], $content);
            
            $link = "index.php?page=tickets&action=show&id=" . $ticketId;
            $commentMessage = "💬 Nouveau commentaire sur le ticket #{$ticket['ticket_number']} de " . $_SESSION['user_name'];
            
            $db = Database::getInstance();
            
            if ($ticket['created_by'] != $_SESSION['user_id']) {
                $this->notificationModel->notifyUser($ticket['created_by'], $commentMessage, $link);
            }
            
            if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != $_SESSION['user_id']) {
                $this->notificationModel->notifyUser($ticket['assigned_to'], $commentMessage, $link);
            }
            
            if (in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
                $charges = $db->fetchAll(
                    "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation') AND id != ?",
                    [$_SESSION['user_id']]
                );
                foreach ($charges as $charge) {
                    $this->notificationModel->notifyUser($charge['id'], $commentMessage, $link);
                }
            }
            
            $this->notificationModel->notifyTechnicians($commentMessage, $link);
            
            try {
                $this->notificationManager->notifyCommentAdded(
                    $ticket, 
                    $content, 
                    $_SESSION['user_name'] ?? 'Utilisateur'
                );
            } catch (Exception $e) {
                error_log("Erreur d'envoi d'email: " . $e->getMessage());
            }
            
            setFlash('success', 'Commentaire ajouté avec succès.');
            redirect('index.php?page=tickets&action=show&id=' . $ticketId);
        }
    }
    
    // ============================================
    // AJOUTER UNE ACTION
    // ============================================
    public function addAction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $actionType = isset($_POST['action_type']) ? $_POST['action_type'] : 'commentaire';
            $content = sanitize($_POST['content'] ?? '');
            
            $notifyRoles = isset($_POST['notify_roles']) ? $_POST['notify_roles'] : array();
            $notifyRolesStr = is_array($notifyRoles) ? implode(',', $notifyRoles) : $notifyRoles;
            
            if ($ticketId <= 0) {
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($ticketId);
            
            if (!$ticket) {
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $role = $_SESSION['user_role'] ?? 'commercial';
            $userId = $_SESSION['user_id'] ?? 0;
            
            if (!$this->canActOnTicket($ticket)) {
                setFlash('danger', 'Vous n\'avez pas la permission d\'agir sur ce ticket.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if (!empty($content) || in_array($actionType, ['resolu', 'en_cours', 'en_attente'])) {
                if (in_array($actionType, ['resolu', 'en_cours', 'en_attente'])) {
                    $content = $content ?: "Statut changé vers " . getStatusLabel($actionType);
                }
                
                $this->ticketModel->addAction(
                    $ticketId, 
                    $userId, 
                    $content, 
                    $actionType, 
                    $notifyRolesStr
                );
                
                if (in_array($actionType, ['resolu', 'en_cours', 'en_attente'])) {
                    $statusMap = [
                        'resolu' => 'resolu',
                        'en_cours' => 'en_cours',
                        'en_attente' => 'en_attente'
                    ];
                    
                    $newStatus = $statusMap[$actionType] ?? null;
                    if ($newStatus) {
                        $data = ['status' => $newStatus];
                        if ($newStatus === 'resolu') {
                            $data['resolved_at'] = date('Y-m-d H:i:s');
                        }
                        $this->ticketModel->update($ticketId, $data);
                        $ticket['status'] = $newStatus;
                    }
                }
                
                $this->sendActionNotifications($ticket, $actionType, $content, $notifyRoles);
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($ticketId);
                    if ($updatedTicket) {
                        $this->notificationManager->notifyStatusChange($updatedTicket, $ticket['status'] ?? 'nouveau', $updatedTicket['status']);
                    }
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
                
                $actionLabels = [
                    'signaler_probleme' => '⚠️ Problème signalé avec succès',
                    'notifier_client' => '📢 Client notifié avec succès',
                    'demander_info' => '❓ Demande d\'information envoyée',
                    'escalader' => '⬆️ Ticket escaladé avec succès',
                    'resolu' => '✅ Ticket marqué comme résolu',
                    'en_cours' => '🔄 Ticket marqué comme en cours',
                    'en_attente' => '⏳ Ticket marqué comme en attente',
                    'commentaire' => '💬 Commentaire ajouté avec succès'
                ];
                
                setFlash('success', $actionLabels[$actionType] ?? 'Action effectuée avec succès');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            } else {
                setFlash('danger', 'Veuillez ajouter un message pour cette action.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
        }
    }
    
    // ============================================
    // VALIDER UN TICKET
    // ============================================
    public function validateTicket() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $action = isset($_POST['validation_action']) ? $_POST['validation_action'] : '';
            $comment = sanitize($_POST['validation_comment'] ?? '');
            
            if ($id <= 0) {
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($id);
            
            if (!$ticket) {
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $role = $_SESSION['user_role'] ?? 'commercial';
            
            if (!in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux'])) {
                setFlash('danger', 'Vous n\'avez pas la permission de valider ce ticket.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            if ($ticket['validation_status'] !== 'en_attente') {
                setFlash('danger', 'Ce ticket a déjà été traité.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            $db = Database::getInstance();
            $link = "index.php?page=tickets&action=show&id=" . $id;
            
            if ($action === 'valider') {
                $this->ticketModel->update($id, array(
                    'validation_status' => 'valide',
                    'validated_by' => $_SESSION['user_id'],
                    'validated_at' => date('Y-m-d H:i:s'),
                    'status' => 'assigne'
                ));
                
                $this->notifyCommercial($ticket, 'Votre ticket a été validé', 'validated');
                
                if (in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
                    $charges = $db->fetchAll(
                        "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
                    );
                    foreach ($charges as $charge) {
                        if ($charge['id'] != $_SESSION['user_id']) {
                            $this->notificationModel->notifyUser($charge['id'], "✅ Ticket #{$ticket['ticket_number']} validé", $link);
                        }
                    }
                }
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($id);
                    if ($updatedTicket) {
                        $this->notificationManager->notifyValidation($updatedTicket, $_SESSION['user_name'] ?? 'Utilisateur', 'valide');
                    }
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
                
                setFlash('success', 'Ticket validé avec succès !');
                
            } elseif ($action === 'refuser') {
                $this->ticketModel->update($id, array(
                    'validation_status' => 'refuse',
                    'refused_by' => $_SESSION['user_id'],
                    'refused_at' => date('Y-m-d H:i:s'),
                    'validation_comment' => $comment,
                    'status' => 'en_attente'
                ));
                
                $this->notifyCommercial($ticket, 'Votre ticket a été refusé', 'refused', $comment);
                
                if (in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
                    $charges = $db->fetchAll(
                        "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
                    );
                    foreach ($charges as $charge) {
                        if ($charge['id'] != $_SESSION['user_id']) {
                            $this->notificationModel->notifyUser($charge['id'], "❌ Ticket #{$ticket['ticket_number']} refusé : {$comment}", $link);
                        }
                    }
                }
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($id);
                    if ($updatedTicket) {
                        $this->notificationManager->notifyValidation($updatedTicket, $_SESSION['user_name'] ?? 'Utilisateur', 'refuse');
                    }
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
                
                $this->ticketModel->update($id, array('reformulation_count' => ($ticket['reformulation_count'] ?? 0) + 1));
                
                setFlash('warning', 'Ticket refusé. Le commercial a été notifié.');
                
            } else {
                setFlash('danger', 'Action de validation invalide.');
            }
            
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
    }
    
    // ============================================
    // RÉPONDRE AU COMMERCIAL
    // ============================================
    public function returnToCommercial() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $message = sanitize($_POST['return_message'] ?? '');
            
            if ($id <= 0) {
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($id);
            
            if (!$ticket) {
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $role = $_SESSION['user_role'] ?? 'commercial';
            
            if (!in_array($role, ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux'])) {
                setFlash('danger', 'Vous n\'avez pas la permission de faire un retour.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            if (empty($message)) {
                setFlash('danger', 'Veuillez saisir un message de retour.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            $this->ticketModel->update($id, array(
                'return_message' => $message,
                'returned_by' => $_SESSION['user_id'],
                'returned_at' => date('Y-m-d H:i:s')
            ));
            
            $this->notifyCommercial($ticket, 'Retour du responsable sur votre ticket', 'returned', $message);
            
            setFlash('success', 'Retour envoyé au commercial.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
    }
    
    // ============================================
    // TRAITER LE TICKET
    // ============================================
    public function processTicket() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            
            if ($id <= 0) {
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($id);
            
            if (!$ticket) {
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $allowedStatus = ['en_cours', 'en_attente', 'resolu', 'cloture'];
            if (!in_array($status, $allowedStatus)) {
                setFlash('danger', 'Statut invalide.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            $data = array('status' => $status);
            if ($status === 'resolu') {
                $data['resolved_at'] = date('Y-m-d H:i:s');
            }
            
            $this->ticketModel->update($id, $data);
            
            if ($status === 'resolu') {
                $this->notifyCommercial($ticket, 'Votre ticket a été résolu', 'resolved');
                
                if (in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
                    $db = Database::getInstance();
                    $charges = $db->fetchAll(
                        "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
                    );
                    $link = "index.php?page=tickets&action=show&id=" . $id;
                    foreach ($charges as $charge) {
                        if ($charge['id'] != $_SESSION['user_id']) {
                            $this->notificationModel->notifyUser($charge['id'], "✅ Ticket #{$ticket['ticket_number']} résolu", $link);
                        }
                    }
                }
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($id);
                    if ($updatedTicket) {
                        $this->notificationManager->notifyStatusChange($updatedTicket, $ticket['status'] ?? 'nouveau', 'resolu');
                    }
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
            }
            
            setFlash('success', 'Statut du ticket mis à jour : ' . getStatusLabel($status));
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
    }
    
    // ============================================
    // NOTIFICATIONS IN-APP
    // ============================================
    
    private function notifyCommercial($ticket, $subject, $type, $comment = null) {
        $db = Database::getInstance();
        $creator = $db->fetch("SELECT id, full_name FROM users WHERE id = ?", [$ticket['created_by']]);
        
        if (!$creator) return;
        
        $link = APP_URL . "/index.php?page=tickets&action=show&id=" . $ticket['id'];
        $this->notificationModel->createNotification($creator['id'], "📧 {$subject} - Ticket #{$ticket['ticket_number']}", $link);
    }
    
    // ============================================
    // ENVOYER LES NOTIFICATIONS POUR LES ACTIONS
    // ============================================
    private function sendActionNotifications($ticket, $actionType, $content, $notifyRoles) {
        $link = "index.php?page=tickets&action=show&id=" . $ticket['id'];
        $db = Database::getInstance();
        $senderName = $_SESSION['user_name'] ?? 'Utilisateur';
        
        $actionMessages = [
            'en_cours' => [
                'message' => "🔄 Le ticket #{$ticket['ticket_number']} est en cours de traitement par {$senderName}",
                'recipients' => ['client', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ],
            'resolu' => [
                'message' => "✅ Le ticket #{$ticket['ticket_number']} a été résolu par {$senderName}",
                'recipients' => ['client', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ],
            'en_attente' => [
                'message' => "⏳ Le ticket #{$ticket['ticket_number']} est en attente d'informations",
                'recipients' => ['client', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ],
            'signaler_probleme' => [
                'message' => "⚠️ Problème signalé sur le ticket #{$ticket['ticket_number']} par {$senderName}",
                'recipients' => ['responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ],
            'demander_info' => [
                'message' => "❓ Demande d'information sur le ticket #{$ticket['ticket_number']} de {$senderName}",
                'recipients' => ['client']
            ],
            'notifier_client' => [
                'message' => "📢 {$senderName} vous informe sur le ticket #{$ticket['ticket_number']}",
                'recipients' => ['client']
            ],
            'escalader' => [
                'message' => "⬆️ Le ticket #{$ticket['ticket_number']} a été escaladé par {$senderName}",
                'recipients' => ['responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ],
            'commentaire' => [
                'message' => "💬 Nouveau commentaire sur le ticket #{$ticket['ticket_number']} de {$senderName}",
                'recipients' => ['client', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
            ]
        ];
        
        $messageInfo = $actionMessages[$actionType] ?? [
            'message' => "Action sur le ticket #{$ticket['ticket_number']} par {$senderName}",
            'recipients' => ['client', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'coordinateur', 'admin']
        ];
        
        $message = $messageInfo['message'];
        
        if (!empty($content)) {
            $message .= "\n\n📝 Message : " . $content;
        }
        
        $rolesArray = array();
        if (is_string($notifyRoles) && !empty($notifyRoles)) {
            $rolesArray = explode(',', $notifyRoles);
        } elseif (is_array($notifyRoles)) {
            $rolesArray = $notifyRoles;
        }
        
        if (empty($rolesArray)) {
            $rolesArray = $messageInfo['recipients'];
        }
        
        $rolesArray = array_map('trim', $rolesArray);
        $rolesArray = array_filter($rolesArray);
        
        $recipients = array();
        
        foreach ($rolesArray as $role) {
            switch ($role) {
                case 'client':
                    if ($ticket['created_by'] != $_SESSION['user_id']) {
                        $recipients[] = $ticket['created_by'];
                    }
                    break;
                case 'responsable':
                    $responsables = $db->fetchAll("SELECT id FROM users WHERE role IN ('responsable_support_technique', 'responsable_sav', 'responsable_travaux')");
                    foreach ($responsables as $resp) {
                        $recipients[] = $resp['id'];
                    }
                    break;
                case 'coordinateur':
                    $coordinateurs = $db->fetchAll("SELECT id FROM users WHERE role = 'coordinateur'");
                    foreach ($coordinateurs as $coord) {
                        $recipients[] = $coord['id'];
                    }
                    break;
                case 'admin':
                    $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
                    foreach ($admins as $admin) {
                        $recipients[] = $admin['id'];
                    }
                    break;
                case 'charge_etude':
                    $charges = $db->fetchAll("SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')");
                    foreach ($charges as $charge) {
                        $recipients[] = $charge['id'];
                    }
                    break;
            }
        }
        
        if (in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
            $charges = $db->fetchAll(
                "SELECT id FROM users WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation')"
            );
            foreach ($charges as $charge) {
                if ($charge['id'] != $_SESSION['user_id'] && !in_array($charge['id'], $recipients)) {
                    $recipients[] = $charge['id'];
                }
            }
        }
        
        if (!in_array($ticket['created_by'], $recipients) && $ticket['created_by'] != $_SESSION['user_id']) {
            $recipients[] = $ticket['created_by'];
        }
        
        if (!empty($ticket['assigned_to']) && !in_array($ticket['assigned_to'], $recipients) && $ticket['assigned_to'] != $_SESSION['user_id']) {
            $recipients[] = $ticket['assigned_to'];
        }
        
        $recipients = array_unique($recipients);
        
        foreach ($recipients as $userId) {
            if ($userId == $_SESSION['user_id']) {
                continue;
            }
            $this->notificationModel->createNotification($userId, $message, $link);
        }
    }
    
    // ============================================
    // FONCTIONS DE PERMISSIONS
    // ============================================
    
    private function canViewTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        $ticketCategory = $ticket['category'] ?? '';
        
        // ✅ ADMIN : peut voir tous les tickets
        if ($role === 'admin') {
            return true;
        }
        
        // ✅ COORDINATEUR : peut voir tous les tickets
        if ($role === 'coordinateur') {
            return true;
        }
        
        // ✅ RESPONSABLE SUPPORT TECHNIQUE : peut voir ses catégories
        if ($role === 'responsable_support_technique') {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        // ✅ RESPONSABLE SAV : peut voir SAV
        if ($role === 'responsable_sav') {
            return $ticketCategory === 'sav';
        }
        
        // ✅ RESPONSABLE TRAVAUX : peut voir Travaux
        if ($role === 'responsable_travaux') {
            return $ticketCategory === 'travaux';
        }
        
        // ✅ CHARGÉS D'ÉTUDE : peuvent voir leur domaine
        if (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        // ✅ COMMERCIAL : ne voit que SES PROPRES tickets
        if ($role === 'commercial') {
            return $ticket['created_by'] == $userId;
        }
        
        return false;
    }
    
    private function canEditTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        $ticketCategory = $ticket['category'] ?? '';
        
        // ✅ ADMIN : peut modifier tous les tickets
        if ($role === 'admin') {
            return true;
        }
        
        // ✅ COORDINATEUR : peut modifier tous les tickets
        if ($role === 'coordinateur') {
            return true;
        }
        
        // ✅ RESPONSABLE SUPPORT TECHNIQUE : peut modifier les tickets de ses catégories
        if ($role === 'responsable_support_technique') {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        // ✅ RESPONSABLE SAV : peut modifier les tickets SAV
        if ($role === 'responsable_sav') {
            return $ticketCategory === 'sav';
        }
        
        // ✅ RESPONSABLE TRAVAUX : peut modifier les tickets Travaux
        if ($role === 'responsable_travaux') {
            return $ticketCategory === 'travaux';
        }
        
        // ✅ CHARGÉS D'ÉTUDE : peuvent modifier les tickets de leur domaine
        if (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        // ❌ COMMERCIAL : ne peut PAS modifier (même ses propres tickets)
        if ($role === 'commercial') {
            return false;
        }
        
        return false;
    }
    
    private function canDeleteTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        // ❌ Commercial ne peut PAS supprimer
        if ($role === 'commercial') {
            return false;
        }
        
        // ❌ Responsables et Chargés d'Étude ne peuvent PAS supprimer
        if (in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            return false;
        }
        
        // ✅ Seul Admin et Coordinateur peuvent supprimer
        if (!in_array($role, ['admin', 'coordinateur'])) {
            return false;
        }
        
        if ($ticket['status'] === 'cloture') {
            return false;
        }
        
        return true;
    }
    
    private function canCommentTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
            return false;
        }
        
        // ✅ Commercial peut commenter SES PROPRES tickets
        if ($role === 'commercial' && $ticket['created_by'] == $userId) {
            return true;
        }
        
        // ✅ Admin, Coordinateur, Responsables, Chargés d'Étude peuvent commenter
        if (in_array($role, [
            'admin', 
            'coordinateur',
            'responsable_support_technique', 
            'responsable_sav', 
            'responsable_travaux',
            'charge_etude_electricite',
            'charge_etude_courant_faible',
            'charge_etude_climatisation'
        ])) {
            return true;
        }
        
        return false;
    }
    
    private function canActOnTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ❌ Commercial ne peut PAS agir (sauf commenter)
        if ($role === 'commercial') {
            return false;
        }
        
        if ($ticket['status'] === 'cloture') {
            return false;
        }
        
        // ✅ Admin, Coordinateur, Responsables, Chargés d'Étude peuvent agir
        if (in_array($role, [
            'admin', 
            'coordinateur',
            'responsable_support_technique', 
            'responsable_sav', 
            'responsable_travaux',
            'charge_etude_electricite',
            'charge_etude_courant_faible',
            'charge_etude_climatisation'
        ])) {
            return true;
        }
        
        return false;
    }
}
?>
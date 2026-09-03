<?php
// controllers/TicketController.php - VERSION COMPLÈTE AVEC WHATSAPP IMAGE

require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/NotificationManager.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/EmailManager.php';
require_once __DIR__ . '/../includes/WhatsAppImageGenerator.php';
require_once __DIR__ . '/../config/app.php';

class TicketController {
    private $ticketModel;
    private $userModel;
    private $notificationModel;
    private $notificationManager;
    private $mailer;
    private $emailManager;
    private $db;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
        $this->notificationManager = new NotificationManager();
        $this->mailer = new Mailer();
        $this->emailManager = new EmailManager();
        $this->db = Database::getInstance();
    }
    
    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode($data);
        exit;
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
        
        foreach ($tickets as &$ticket) {
            $ticket['assigned_users'] = $this->ticketModel->getAssignedUsers($ticket['id']);
        }
        
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
        
        $db = Database::getInstance();
        $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$id]);
        
        if (!$ticket) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        $canView = false;
        
        if ($role === 'admin' || $role === 'coordinateur') {
            $canView = true;
        } elseif ($role === 'commercial' && $ticket['created_by'] == $userId) {
            $canView = true;
        } elseif ($role === 'responsable_support_technique' && in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
            $canView = true;
        } elseif ($role === 'responsable_sav' && $ticket['category'] === 'sav') {
            $canView = true;
        } elseif ($role === 'responsable_travaux' && $ticket['category'] === 'travaux') {
            $canView = true;
        } elseif (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            if (in_array($ticket['category'], ['support_technique', 'bureau_etude']) && $ticket['assigned_to'] == $userId) {
                $canView = true;
            }
        }
        
        if (!$canView) {
            setFlash('danger', 'Vous n\'avez pas accès à ce ticket.');
            redirect('index.php?page=tickets');
        }
        
        $creator = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$ticket['created_by']]);
        $ticket['created_by_name'] = $creator ? $creator['full_name'] : 'Inconnu';
        
        if ($ticket['assigned_to']) {
            $assigned = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$ticket['assigned_to']]);
            $ticket['assigned_to_name'] = $assigned ? $assigned['full_name'] : null;
        }
        
        $comments = $this->ticketModel->getCommentsByTicket($id);
        $technicians = $this->userModel->getTechnicians();
        
        $canEdit = $this->canEditTicket($ticket);
        $canDelete = $this->canDeleteTicket($ticket);
        $canComment = $this->canCommentTicket($ticket);
        $canAct = $this->canActOnTicket($ticket);
        $canAssign = $this->canAssignTicket($ticket);
        
        $chargesEtude = [];
        if ($role === 'responsable_support_technique' || in_array($role, ['admin', 'coordinateur'])) {
            $chargesEtude = $db->fetchAll(
                "SELECT id, full_name, role, email FROM users 
                 WHERE role IN ('charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation') 
                 ORDER BY full_name"
            );
        }
        
        $assignedUsers = $this->ticketModel->getAssignedUsers($id);
        $actions = $this->ticketModel->getActionsByTicket($id);
        
        require_once __DIR__ . '/../views/tickets/show.php';
    }
    
    // ============================================
    // CRÉER UN TICKET
    // ============================================
    
    public function create() {
        global $pageTitle;
        $pageTitle = 'Nouveau ticket';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['submission_token'] ?? '';
            $validToken = isset($_SESSION['ticket_submission_token'])
                && $submittedToken !== ''
                && hash_equals($_SESSION['ticket_submission_token'], $submittedToken);
            
            if (!$validToken) {
                error_log("⛔ Soumission de ticket bloquée : jeton invalide ou déjà utilisé");
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'duplicate' => true,
                        'message' => 'Ce ticket a déjà été soumis.',
                        'redirect' => 'index.php?page=tickets'
                    ]);
                }
                setFlash('warning', 'Ce ticket a déjà été soumis.');
                redirect('index.php?page=tickets');
            }
            unset($_SESSION['ticket_submission_token']);
            
            $category = isset($_POST['category']) ? $_POST['category'] : 'support_technique';
            $typeDemande = isset($_POST['type_demande']) ? $_POST['type_demande'] : 'etude';
            
            $db = Database::getInstance();
            
            $categoryResponsibleRoleMap = [
                'support_technique' => 'responsable_support_technique',
                'bureau_etude'      => 'responsable_support_technique',
                'sav'               => 'responsable_sav',
                'travaux'           => 'responsable_travaux'
            ];
            
            $assignedTo = null;
            $responsibleRole = $categoryResponsibleRoleMap[$category] ?? null;
            
            if ($responsibleRole) {
                $responsible = $db->fetch(
                    "SELECT id FROM users WHERE role = ? AND active = 1 ORDER BY id ASC LIMIT 1",
                    [$responsibleRole]
                );
                if ($responsible) {
                    $assignedTo = $responsible['id'];
                }
            }
            
            if ($assignedTo === null) {
                $admin = $db->fetch("SELECT id FROM users WHERE role = 'admin' AND active = 1 LIMIT 1");
                $assignedTo = $admin ? $admin['id'] : 1;
            }
            
            try {
                $ticketNumber = generateTicketNumber($category);
            } catch (Exception $e) {
                error_log("❌ Génération du numéro de ticket impossible : " . $e->getMessage());
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Impossible de générer un numéro de ticket. Veuillez réessayer.'
                    ]);
                }
                setFlash('danger', 'Impossible de générer un numéro de ticket. Veuillez réessayer.');
                redirect('index.php?page=tickets&action=create');
            }
            
            $userName = $_SESSION['user_name'] ?? 'Utilisateur';
            $userRole = $_SESSION['user_role'] ?? 'commercial';
            
            $errors = [];
            
            if (empty($_POST['title'])) {
                $_POST['title'] = generateUniqueTitle($category);
            }
            
            if (empty($_POST['description'])) {
                $errors['description'] = 'La description est requise.';
            }
            if (empty($_POST['visite_date'])) {
                $errors['visite_date'] = 'La date de visite est requise.';
            }
            if (empty($_POST['visite_heure'])) {
                $errors['visite_heure'] = 'L\'heure de visite est requise.';
            }
            
            if (!in_array($userRole, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux'])) {
                if (empty($_POST['client_name'])) {
                    $errors['client_name'] = 'Le nom du client est requis.';
                }
                if (empty($_POST['adresse_client'])) {
                    $errors['adresse_client'] = 'L\'adresse du client est requise.';
                }
            }
            
            if (!empty($errors)) {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'errors' => $errors,
                        'message' => 'Veuillez corriger les erreurs ci-dessous.'
                    ]);
                }
                $_SESSION['form_errors'] = $errors;
                setFlash('danger', 'Veuillez corriger les erreurs ci-dessous.');
                redirect('index.php?page=tickets&action=create');
            }
            
            $commercialDedie = sanitize($_POST['commercial_dedie'] ?? $userName);
            $role = $userRole;
            
            if (in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux'])) {
                $commercialDedie = 'Responsable - ' . $commercialDedie;
            } elseif ($role === 'admin') {
                $commercialDedie = 'Administrateur - ' . $commercialDedie;
            }
            
            $title = !empty($_POST['title']) ? sanitize($_POST['title']) : generateUniqueTitle($category);
            
            $data = array(
                'ticket_number' => $ticketNumber,
                'title' => $title,
                'description' => sanitize($_POST['description']),
                'category' => $category,
                'type_demande' => $typeDemande,
                'priority' => isset($_POST['priority']) ? $_POST['priority'] : 'moyenne',
                'status' => 'nouveau',
                'validation_status' => 'en_attente',
                'created_by' => $_SESSION['user_id'],
                'assigned_to' => $assignedTo,
                'commercial_dedie' => $commercialDedie,
                'client_name' => isset($_POST['client_name']) ? sanitize($_POST['client_name']) : null,
                'adresse_client' => isset($_POST['adresse_client']) ? sanitize($_POST['adresse_client']) : null,
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
                $ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);
                
                if (!$ticket) {
                    $ticket = array(
                        'id' => $ticketId,
                        'ticket_number' => $ticketNumber,
                        'title' => $title,
                        'description' => $data['description'],
                        'category' => $data['category'],
                        'type_demande' => $data['type_demande'],
                        'priority' => $data['priority'],
                        'status' => 'nouveau',
                        'created_by' => $data['created_by'],
                        'assigned_to' => $data['assigned_to'],
                        'created_by_name' => $userName,
                        'created_at' => date('Y-m-d H:i:s')
                    );
                }
                
                $link = "index.php?page=tickets&action=show&id=" . $ticketId;
                
                // NOTIFICATIONS IN-APP
                $this->notificationModel->createNotification(
                    $ticket['created_by'],
                    "✅ Votre ticket {$ticket['ticket_number']} a été créé avec succès",
                    $link,
                    'ticket'
                );
                
                if ($assignedTo && $assignedTo != $_SESSION['user_id']) {
                    $this->notificationModel->createNotification(
                        $assignedTo,
                        "📌 Nouveau ticket {$ticket['ticket_number']} : {$ticket['title']} créé par " . $userName,
                        $link,
                        'ticket'
                    );
                }
                
                // ENVOI WHATSAPP
                try {
                    $commercial = $db->fetch("SELECT id, full_name, email, phone FROM users WHERE id = ?", [$ticket['created_by']]);
                    
                    if ($commercial && !empty($commercial['phone'])) {
                        $phone = cleanPhoneNumber($commercial['phone']);
                        if ($phone) {
                            $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
                            $ticketTitle = $ticket['title'] ?? 'Sans titre';
                            $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . $ticketId;
                            
                            $whatsappMessage = "📋 *Nouveau ticket créé - #{$ticketNumber}*\n\n";
                            $whatsappMessage .= "👤 *Créé par :* " . $userName . "\n";
                            $whatsappMessage .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
                            $whatsappMessage .= "📊 *Statut :* Nouveau\n";
                            $whatsappMessage .= "🎯 *Priorité :* " . getPriorityLabel($ticket['priority'] ?? 'moyenne') . "\n";
                            $whatsappMessage .= "📂 *Catégorie :* " . getCategoryLabel($ticket['category'] ?? 'general') . "\n";
                            $whatsappMessage .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
                            $whatsappMessage .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
                            $whatsappMessage .= "---\n";
                            $whatsappMessage .= "Plateforme de Ticketing - SPIDER Madagascar";
                            
                            $db->insert(
                                "INSERT INTO whatsapp_queue (ticket_id, user_id, phone, message, status, created_at) 
                                 VALUES (?, ?, ?, ?, 'pending', NOW())",
                                [$ticketId, $commercial['id'], $phone, $whatsappMessage]
                            );
                            
                            error_log("📱 WhatsApp nouvelle création en file d'attente pour " . $commercial['full_name']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi WhatsApp création: " . $e->getMessage());
                }
                
                // ENVOI D'EMAILS
                try {
                    if (isset($ticket['id']) && $ticket['id'] > 0) {
                        $this->emailManager->notifyTicketCreated($ticket);
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi emails création: " . $e->getMessage());
                }
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'ticket_id' => $ticketId,
                        'ticket_number' => $ticketNumber,
                        'message' => 'Ticket créé avec succès !'
                    ]);
                }
                
                setFlash('success', 'Demande créée avec succès ! Numéro : ' . $ticketNumber);
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            } else {
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => false,
                        'error' => 'Erreur lors de la création du ticket'
                    ]);
                }
                setFlash('danger', 'Erreur lors de la création de la demande.');
                redirect('index.php?page=tickets&action=create');
            }
        }
        
        $submissionToken = bin2hex(random_bytes(32));
        $_SESSION['ticket_submission_token'] = $submissionToken;
        
        require_once __DIR__ . '/../views/tickets/create.php';
    }
    
    // ============================================
    // MODIFIER UN TICKET
    // ============================================
    
    public function edit() {
        global $pageTitle;
        $pageTitle = 'Détail du ticket';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            setFlash('danger', 'ID de ticket invalide.');
            redirect('index.php?page=tickets');
        }
        
        $ticket = $this->ticketModel->getTicketDetails($id);
        
        if (!$ticket || empty($ticket)) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$this->canViewTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas accès à ce ticket.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        if (!$this->canEditTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas la permission de modifier ce ticket.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        if ($ticket['status'] === 'cloture' && $role !== 'admin') {
            setFlash('danger', 'Ce ticket est clôturé et ne peut plus être modifié.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $oldStatus = $ticket['status'] ?? 'nouveau';
            $newStatus = isset($_POST['status']) ? $_POST['status'] : $ticket['status'];
            
            if ($ticket['status'] === 'cloture') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Ce ticket est clôturé']);
                }
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
            
            if ($oldStatus !== $updatedTicket['status']) {
                $statusMessage = "📊 Le ticket {$updatedTicket['ticket_number']} a changé de statut : " . 
                                getStatusLabel($oldStatus) . " → " . getStatusLabel($updatedTicket['status']);
                
                $this->notificationModel->createNotification(
                    $updatedTicket['created_by'],
                    $statusMessage,
                    $link,
                    'status'
                );
                
                if (!empty($updatedTicket['assigned_to'])) {
                    $this->notificationModel->createNotification(
                        $updatedTicket['assigned_to'],
                        $statusMessage,
                        $link,
                        'status'
                    );
                }
                
                // ENVOI WHATSAPP STATUT
                try {
                    $commercial = $this->db->fetch("SELECT id, full_name, email, phone FROM users WHERE id = ?", [$updatedTicket['created_by']]);
                    
                    if ($commercial && !empty($commercial['phone'])) {
                        $phone = cleanPhoneNumber($commercial['phone']);
                        if ($phone) {
                            $ticketNumber = $updatedTicket['ticket_number'] ?? 'N/A';
                            $ticketTitle = $updatedTicket['title'] ?? 'Sans titre';
                            $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . $id;
                            $oldStatusLabel = getStatusLabel($oldStatus);
                            $newStatusLabel = getStatusLabel($updatedTicket['status']);
                            
                            $whatsappMessage = "📊 *Changement de statut - Ticket #{$ticketNumber}*\n\n";
                            $whatsappMessage .= "👤 *Par :* " . $_SESSION['user_name'] . "\n";
                            $whatsappMessage .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
                            $whatsappMessage .= "📊 *Ancien statut :* " . $oldStatusLabel . "\n";
                            $whatsappMessage .= "📊 *Nouveau statut :* " . $newStatusLabel . "\n";
                            $whatsappMessage .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
                            $whatsappMessage .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
                            $whatsappMessage .= "---\n";
                            $whatsappMessage .= "Plateforme de Ticketing - SPIDER Madagascar";
                            
                            $this->db->insert(
                                "INSERT INTO whatsapp_queue (ticket_id, user_id, phone, message, status, created_at) 
                                 VALUES (?, ?, ?, ?, 'pending', NOW())",
                                [$id, $commercial['id'], $phone, $whatsappMessage]
                            );
                            
                            error_log("📱 WhatsApp statut en file d'attente pour " . $commercial['full_name']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi WhatsApp statut: " . $e->getMessage());
                }
                
                try {
                    $this->emailManager->notifyStatusChange($updatedTicket, $oldStatus, $updatedTicket['status']);
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi emails changement de statut: " . $e->getMessage());
                }
            }
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Ticket mis à jour avec succès !',
                    'status' => $newStatus
                ]);
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
        
        if (!function_exists('canDeleteTicket')) {
            require_once __DIR__ . '/../includes/functions.php';
        }
        
        if (!canDeleteTicket($ticket)) {
            setFlash('danger', 'Vous n\'avez pas la permission de supprimer ce ticket.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        if ($ticket['status'] === 'cloture') {
            setFlash('danger', 'Ce ticket est clôturé et ne peut pas être supprimé.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        $role = $_SESSION['user_role'] ?? 'commercial';
        if ($ticket['status'] === 'resolu' && $role !== 'admin') {
            setFlash('danger', 'Ce ticket est résolu et ne peut pas être supprimé.');
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
        
        $db = Database::getInstance();
        $db->query("DELETE FROM ticket_assignments WHERE ticket_id = ?", [$id]);
        $db->query("DELETE FROM comments WHERE ticket_id = ?", [$id]);
        $db->query("DELETE FROM notifications WHERE ticket_id = ?", [$id]);
        $db->query("DELETE FROM interventions WHERE ticket_id = ?", [$id]);
        
        $this->ticketModel->delete($id);
        
        $db->insert(
            "INSERT INTO logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())",
            [
                $_SESSION['user_id'] ?? 0,
                'delete_ticket',
                "Ticket #{$ticket['ticket_number']} supprimé par " . ($_SESSION['user_name'] ?? 'Utilisateur')
            ]
        );
        
        setFlash('success', 'Ticket #' . $ticket['ticket_number'] . ' supprimé avec succès.');
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
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'ID invalide']);
                }
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($ticketId);
            
            if (!$ticket) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Ticket non trouvé']);
                }
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            if (!$this->canCommentTicket($ticket)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Permission refusée']);
                }
                setFlash('danger', 'Vous n\'avez pas la permission de commenter ce ticket.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Ticket clôturé ou résolu']);
                }
                setFlash('danger', 'Ce ticket est ' . strtolower(getStatusLabel($ticket['status'])) . '. Les commentaires ne sont plus autorisés.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if (empty($content)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Commentaire vide']);
                }
                setFlash('danger', 'Le commentaire ne peut pas être vide.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            $commentId = $this->ticketModel->addComment($ticketId, $_SESSION['user_id'], $content);
            
            $link = "index.php?page=tickets&action=show&id=" . $ticketId;
            $commentMessage = "💬 Nouveau commentaire sur le ticket {$ticket['ticket_number']} de " . $_SESSION['user_name'];
            
            $db = Database::getInstance();
            
            if ($ticket['created_by'] != $_SESSION['user_id']) {
                $this->notificationModel->createNotification($ticket['created_by'], $commentMessage, $link, 'comment');
            }
            if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != $_SESSION['user_id']) {
                $this->notificationModel->createNotification($ticket['assigned_to'], $commentMessage, $link, 'comment');
            }
            
            // ENVOI WHATSAPP COMMENTAIRE
            try {
                $commercial = $db->fetch("SELECT id, full_name, email, phone FROM users WHERE id = ?", [$ticket['created_by']]);
                
                if ($commercial && !empty($commercial['phone'])) {
                    $phone = cleanPhoneNumber($commercial['phone']);
                    if ($phone) {
                        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
                        $ticketTitle = $ticket['title'] ?? 'Sans titre';
                        $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . $ticketId;
                        $shortContent = strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                        
                        $whatsappMessage = "💬 *Nouveau commentaire - Ticket #{$ticketNumber}*\n\n";
                        $whatsappMessage .= "👤 *Par :* " . $_SESSION['user_name'] . "\n";
                        $whatsappMessage .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
                        $whatsappMessage .= "📊 *Statut :* " . getStatusLabel($ticket['status']) . "\n";
                        $whatsappMessage .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
                        $whatsappMessage .= "📝 *Message :*\n" . htmlspecialchars($shortContent) . "\n\n";
                        $whatsappMessage .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
                        $whatsappMessage .= "---\n";
                        $whatsappMessage .= "Plateforme de Ticketing - SPIDER Madagascar";
                        
                        $db->insert(
                            "INSERT INTO whatsapp_queue (ticket_id, user_id, phone, message, status, created_at) 
                             VALUES (?, ?, ?, ?, 'pending', NOW())",
                            [$ticketId, $commercial['id'], $phone, $whatsappMessage]
                        );
                        
                        error_log("📱 WhatsApp commentaire en file d'attente pour " . $commercial['full_name']);
                    }
                }
            } catch (Exception $e) {
                error_log("❌ Erreur envoi WhatsApp commentaire: " . $e->getMessage());
            }
            
            try {
                $this->notificationManager->notifyCommentAdded($ticket, $content, $_SESSION['user_name'] ?? 'Utilisateur');
            } catch (Exception $e) {
                error_log("Erreur d'envoi d'email: " . $e->getMessage());
            }
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'comment_id' => $commentId,
                    'message' => 'Commentaire ajouté avec succès.'
                ]);
            }
            
            setFlash('success', 'Commentaire ajouté avec succès.');
            redirect('index.php?page=tickets&action=show&id=' . $ticketId);
        }
    }
    
    // ============================================
    // AJOUTER UNE ACTION - AVEC WHATSAPP IMAGE
    // ============================================
    
    public function addAction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $actionType = isset($_POST['action_type']) ? $_POST['action_type'] : 'commentaire';
            $content = sanitize($_POST['content'] ?? '');
            
            $notifyRoles = isset($_POST['notify_roles']) ? $_POST['notify_roles'] : array();
            $notifyRolesStr = is_array($notifyRoles) ? implode(',', $notifyRoles) : $notifyRoles;
            
            if ($ticketId <= 0) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'ID invalide']);
                }
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($ticketId);
            
            if (!$ticket) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Ticket non trouvé']);
                }
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            if (!$this->canActOnTicket($ticket)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Permission refusée']);
                }
                setFlash('danger', 'Vous n\'avez pas la permission d\'agir sur ce ticket.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
            
            if (!empty($content) || in_array($actionType, ['resolu', 'en_cours', 'en_attente'])) {
                if (in_array($actionType, ['resolu', 'en_cours', 'en_attente'])) {
                    $content = $content ?: "Statut changé vers " . getStatusLabel($actionType);
                }
                
                $this->ticketModel->addAction(
                    $ticketId, 
                    $_SESSION['user_id'], 
                    $content, 
                    $actionType, 
                    $notifyRolesStr
                );
                
                $oldStatus = $ticket['status'] ?? 'nouveau';
                $newStatus = null;
                
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
                
                try {
                    $this->notificationManager->notifyAction($ticket, $actionType, $content, $_SESSION['user_name'] ?? 'Utilisateur');
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi notification action: " . $e->getMessage());
                }
                
                if ($newStatus && $oldStatus !== $newStatus) {
                    $statusMessage = "📊 Votre ticket {$ticket['ticket_number']} est maintenant en " . getStatusLabel($newStatus);
                    
                    $this->notificationModel->createNotification(
                        $ticket['created_by'],
                        $statusMessage,
                        "index.php?page=tickets&action=show&id=" . $ticketId,
                        'status'
                    );
                    
                    if (!empty($ticket['assigned_to'])) {
                        $this->notificationModel->createNotification(
                            $ticket['assigned_to'],
                            $statusMessage,
                            "index.php?page=tickets&action=show&id=" . $ticketId,
                            'status'
                        );
                    }
                }
                
                // ============================================
                // ✅ GÉNÉRATION D'IMAGE WHATSAPP (NOUVEAU)
                // ============================================
                try {
                    $db = Database::getInstance();
                    $imageGenerator = new WhatsAppImageGenerator();
                    $senderName = $_SESSION['user_name'] ?? 'Responsable';
                    
                    // Générer l'image récapitulative
                    $imageData = $imageGenerator->generateActionImage(
                        $ticket, 
                        $actionType, 
                        $senderName, 
                        $content
                    );
                    
                    // Sauvegarder le lien de l'image dans la base
                    $db->insert(
                        "INSERT INTO whatsapp_images (ticket_id, action_type, image_path, image_url, created_at) 
                         VALUES (?, ?, ?, ?, NOW())",
                        [$ticketId, $actionType, $imageData['path'], $imageData['url']]
                    );
                    
                    // Envoyer le lien WhatsApp au commercial
                    $commercial = $db->fetch("SELECT id, full_name, phone FROM users WHERE id = ?", [$ticket['created_by']]);
                    
                    if ($commercial && !empty($commercial['phone'])) {
                        $phone = cleanPhoneNumber($commercial['phone']);
                        if ($phone) {
                            $actionLabels = [
                                'resolu' => '✅ Ticket résolu',
                                'en_cours' => '🔄 Ticket en cours',
                                'en_attente' => '⏳ Ticket en attente',
                                'signaler_probleme' => '⚠️ Problème signalé',
                                'commentaire' => '💬 Nouveau commentaire',
                                'notifier_client' => '📢 Client notifié',
                                'demander_info' => '❓ Demande d\'information',
                                'escalader' => '⬆️ Ticket escaladé'
                            ];
                            $actionLabel = $actionLabels[$actionType] ?? 'Action effectuée';
                            $statusLabel = getStatusLabel($newStatus ?? $ticket['status']);
                            
                            $whatsappMessage = "📋 *Ticket #{$ticket['ticket_number']}* - Action effectuée\n\n";
                            $whatsappMessage .= "📌 *Action :* " . $actionLabel . "\n";
                            $whatsappMessage .= "👤 *Par :* " . $_SESSION['user_name'] . "\n";
                            $whatsappMessage .= "📊 *Statut :* " . $statusLabel . "\n\n";
                            $whatsappMessage .= "📱 *Image récapitulative :*\n" . $imageData['url'] . "\n\n";
                            $whatsappMessage .= "🔗 *Voir le ticket :*\n" . APP_URL . "/index.php?page=tickets&action=show&id=" . $ticketId . "\n\n";
                            $whatsappMessage .= "---\n";
                            $whatsappMessage .= "Plateforme de Ticketing - SPIDER Madagascar";
                            
                            $db->insert(
                                "INSERT INTO whatsapp_queue (ticket_id, user_id, phone, message, image_url, status, created_at) 
                                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                                [$ticketId, $commercial['id'], $phone, $whatsappMessage, $imageData['url']]
                            );
                            
                            error_log("📱 WhatsApp avec image en file d'attente pour " . $commercial['full_name']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur génération image WhatsApp: " . $e->getMessage());
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
                
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $actionLabels[$actionType] ?? 'Action effectuée avec succès',
                        'status' => $newStatus
                    ]);
                }
                
                setFlash('success', $actionLabels[$actionType] ?? 'Action effectuée avec succès');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            } else {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Veuillez ajouter un message']);
                }
                setFlash('danger', 'Veuillez ajouter un message pour cette action.');
                redirect('index.php?page=tickets&action=show&id=' . $ticketId);
            }
        }
    }
    
    // ============================================
    // PROCESS - CHANGEMENT DE STATUT RAPIDE
    // ============================================
    
    public function processTicket() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            
            if ($id <= 0) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'ID invalide']);
                }
                setFlash('danger', 'ID de ticket invalide.');
                redirect('index.php?page=tickets');
            }
            
            $ticket = $this->ticketModel->getTicketDetails($id);
            
            if (!$ticket) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Ticket non trouvé']);
                }
                setFlash('danger', 'Ticket non trouvé.');
                redirect('index.php?page=tickets');
            }
            
            $allowedStatus = ['nouveau', 'assigne', 'en_cours', 'en_attente', 'resolu', 'cloture'];
            if (!in_array($status, $allowedStatus)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'error' => 'Statut invalide']);
                }
                setFlash('danger', 'Statut invalide.');
                redirect('index.php?page=tickets&action=show&id=' . $id);
            }
            
            $oldStatus = $ticket['status'] ?? 'nouveau';
            $data = array('status' => $status);
            if ($status === 'resolu') {
                $data['resolved_at'] = date('Y-m-d H:i:s');
            }
            
            $this->ticketModel->update($id, $data);
            
            if ($oldStatus !== $status) {
                $statusMessage = "📊 Le ticket {$ticket['ticket_number']} a changé de statut : " . 
                                getStatusLabel($oldStatus) . " → " . getStatusLabel($status);
                
                $this->notificationModel->createNotification(
                    $ticket['created_by'],
                    $statusMessage,
                    "index.php?page=tickets&action=show&id=" . $id,
                    'status'
                );
                
                if (!empty($ticket['assigned_to'])) {
                    $this->notificationModel->createNotification(
                        $ticket['assigned_to'],
                        $statusMessage,
                        "index.php?page=tickets&action=show&id=" . $id,
                        'status'
                    );
                }
                
                // ENVOI WHATSAPP STATUT AVEC IMAGE
                try {
                    $db = Database::getInstance();
                    $imageGenerator = new WhatsAppImageGenerator();
                    $senderName = $_SESSION['user_name'] ?? 'Responsable';
                    
                    $imageData = $imageGenerator->generateActionImage(
                        $ticket, 
                        $status, 
                        $senderName, 
                        ''
                    );
                    
                    $db->insert(
                        "INSERT INTO whatsapp_images (ticket_id, action_type, image_path, image_url, created_at) 
                         VALUES (?, ?, ?, ?, NOW())",
                        [$id, $status, $imageData['path'], $imageData['url']]
                    );
                    
                    $commercial = $db->fetch("SELECT id, full_name, phone FROM users WHERE id = ?", [$ticket['created_by']]);
                    
                    if ($commercial && !empty($commercial['phone'])) {
                        $phone = cleanPhoneNumber($commercial['phone']);
                        if ($phone) {
                            $oldStatusLabel = getStatusLabel($oldStatus);
                            $newStatusLabel = getStatusLabel($status);
                            
                            $whatsappMessage = "📊 *Changement de statut - Ticket #{$ticket['ticket_number']}*\n\n";
                            $whatsappMessage .= "👤 *Par :* " . $_SESSION['user_name'] . "\n";
                            $whatsappMessage .= "📊 *Ancien statut :* " . $oldStatusLabel . "\n";
                            $whatsappMessage .= "📊 *Nouveau statut :* " . $newStatusLabel . "\n";
                            $whatsappMessage .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
                            $whatsappMessage .= "📱 *Image récapitulative :*\n" . $imageData['url'] . "\n\n";
                            $whatsappMessage .= "🔗 *Voir le ticket :*\n" . APP_URL . "/index.php?page=tickets&action=show&id=" . $id;
                            
                            $db->insert(
                                "INSERT INTO whatsapp_queue (ticket_id, user_id, phone, message, image_url, status, created_at) 
                                 VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                                [$id, $commercial['id'], $phone, $whatsappMessage, $imageData['url']]
                            );
                            
                            error_log("📱 WhatsApp statut avec image en file d'attente pour " . $commercial['full_name']);
                        }
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur génération image WhatsApp statut: " . $e->getMessage());
                }
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($id);
                    if ($updatedTicket) {
                        $this->emailManager->notifyStatusChange($updatedTicket, $oldStatus, $status);
                    }
                } catch (Exception $e) {
                    error_log("❌ Erreur envoi email changement de statut: " . $e->getMessage());
                }
            }
            
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Statut mis à jour : ' . getStatusLabel($status),
                    'status' => $status,
                    'status_label' => getStatusLabel($status)
                ]);
            }
            
            setFlash('success', 'Statut du ticket mis à jour : ' . getStatusLabel($status));
            redirect('index.php?page=tickets&action=show&id=' . $id);
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
                
                $this->notificationModel->createNotification(
                    $ticket['created_by'],
                    "✅ Votre ticket {$ticket['ticket_number']} a été validé",
                    $link,
                    'validation'
                );
                
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
                
                $this->notificationModel->createNotification(
                    $ticket['created_by'],
                    "❌ Votre ticket {$ticket['ticket_number']} a été refusé : {$comment}",
                    $link,
                    'validation'
                );
                
                try {
                    $updatedTicket = $this->ticketModel->getTicketDetails($id);
                    if ($updatedTicket) {
                        $this->notificationManager->notifyValidation($updatedTicket, $_SESSION['user_name'] ?? 'Utilisateur', 'refuse');
                    }
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email: " . $e->getMessage());
                }
                
                $this->ticketModel->update($id, array('reformulation_count' => ($ticket['reformulation_count'] ?? 0) + 1));
                
                setFlash('warning', 'Ticket refusé. Le responsable a été notifié.');
                
            } else {
                setFlash('danger', 'Action de validation invalide.');
            }
            
            redirect('index.php?page=tickets&action=show&id=' . $id);
        }
    }
    
    // ============================================
    // PERMISSIONS
    // ============================================
    
    private function canViewTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        $ticketCategory = $ticket['category'] ?? '';
        
        if ($role === 'admin' || $role === 'coordinateur') {
            return true;
        }
        
        if ($role === 'commercial') {
            return $ticket['created_by'] == $userId;
        }
        
        if ($role === 'responsable_support_technique') {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        if ($role === 'responsable_sav') {
            return $ticketCategory === 'sav';
        }
        
        if ($role === 'responsable_travaux') {
            return $ticketCategory === 'travaux';
        }
        
        if (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        return false;
    }
    
    private function canEditTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $ticketCategory = $ticket['category'] ?? '';
        
        if ($role === 'admin' || $role === 'coordinateur') {
            return true;
        }
        
        if ($role === 'responsable_support_technique') {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        if ($role === 'responsable_sav') {
            return $ticketCategory === 'sav';
        }
        
        if ($role === 'responsable_travaux') {
            return $ticketCategory === 'travaux';
        }
        
        if (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        if ($role === 'commercial') {
            return false;
        }
        
        return false;
    }
    
    private function canDeleteTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $ticketCategory = $ticket['category'] ?? '';
        
        if ($role === 'admin') {
            return true;
        }
        
        if ($role === 'responsable_support_technique') {
            return in_array($ticketCategory, ['support_technique', 'bureau_etude']);
        }
        
        if ($role === 'responsable_sav') {
            return $ticketCategory === 'sav';
        }
        
        if ($role === 'responsable_travaux') {
            return $ticketCategory === 'travaux';
        }
        
        return false;
    }
    
    private function canCommentTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        $userId = $_SESSION['user_id'] ?? 0;
        
        if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
            return false;
        }
        
        if ($role === 'commercial' && $ticket['created_by'] == $userId) {
            return true;
        }
        
        if (in_array($role, [
            'admin', 'coordinateur',
            'responsable_support_technique', 'responsable_sav', 'responsable_travaux',
            'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'
        ])) {
            return true;
        }
        
        return false;
    }
    
    private function canActOnTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        if ($role === 'commercial') {
            return false;
        }
        
        if ($ticket['status'] === 'cloture') {
            return false;
        }
        
        if (in_array($role, [
            'admin', 'coordinateur',
            'responsable_support_technique', 'responsable_sav', 'responsable_travaux',
            'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'
        ])) {
            return true;
        }
        
        return false;
    }
    
    private function canAssignTicket($ticket) {
        $role = $_SESSION['user_role'] ?? 'commercial';
        
        if ($role === 'commercial') {
            return false;
        }
        
        if ($ticket['status'] === 'cloture' || $ticket['status'] === 'resolu') {
            return false;
        }
        
        if (!in_array($ticket['category'], ['support_technique', 'bureau_etude'])) {
            return false;
        }
        
        if (!in_array($role, ['responsable_support_technique', 'admin', 'coordinateur'])) {
            return false;
        }
        
        return true;
    }
}

// ✅ FONCTION HELPER POUR GÉNÉRER UN TITRE UNIQUE
function generateUniqueTitle($category) {
    $date = new DateTime();
    $timestamp = $date->format('YmdHis');
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    
    $prefix = 'TK-';
    switch ($category) {
        case 'support_technique': $prefix = 'TK-ST'; break;
        case 'bureau_etude': $prefix = 'TK-BE'; break;
        case 'sav': $prefix = 'TK-SAV'; break;
        case 'travaux': $prefix = 'TK-TVX'; break;
        default: $prefix = 'TK-';
    }
    
    return $prefix . $date->format('Ymd') . '-' . $timestamp . '-' . $random;
}
?>
<?php
// controllers/WhatsAppController.php

require_once __DIR__ . '/../includes/WhatsAppImageGenerator.php';
require_once __DIR__ . '/../models/Ticket.php';

class WhatsAppController {
    private $ticketModel;
    
    public function __construct() {
        $this->ticketModel = new Ticket();
    }
    
    public function share() {
        $ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $actionType = isset($_GET['action']) ? $_GET['action'] : 'general';
        
        if ($ticketId <= 0) {
            setFlash('danger', 'ID de ticket invalide.');
            redirect('index.php?page=tickets');
        }
        
        $ticket = $this->ticketModel->getTicketDetails($ticketId);
        
        if (!$ticket) {
            setFlash('danger', 'Ticket non trouvé.');
            redirect('index.php?page=tickets');
        }
        
        $senderName = $_SESSION['user_name'] ?? 'Utilisateur';
        
        try {
            $imageGenerator = new WhatsAppImageGenerator();
            $imageData = $imageGenerator->generateActionImage($ticket, $actionType, $senderName, '');
            
            // Rediriger vers WhatsApp avec le message
            redirect($imageData['whatsapp_url']);
            
        } catch (Exception $e) {
            error_log("❌ Erreur génération image WhatsApp: " . $e->getMessage());
            setFlash('danger', 'Erreur lors de la génération de l\'image WhatsApp.');
            redirect('index.php?page=tickets&action=show&id=' . $ticketId);
        }
    }
}
?>
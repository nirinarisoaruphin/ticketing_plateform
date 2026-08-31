<?php
// controllers/ProfileController.php - Vue uniquement, pas de modification
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/functions.php';

class ProfileController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        global $pageTitle;
        $pageTitle = 'Mon profil';
        
        $userId = $_SESSION['user_id'] ?? 0;
        
        if ($userId <= 0) {
            setFlash('danger', 'Utilisateur non trouvé.');
            redirect('index.php?page=dashboard');
        }
        
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            setFlash('danger', 'Utilisateur non trouvé.');
            redirect('index.php?page=dashboard');
        }
        
        // ✅ STATISTIQUES DE L'UTILISATEUR
        $db = Database::getInstance();
        
        // Nombre de tickets créés
        $ticketsCreated = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets WHERE created_by = ?",
            [$userId]
        );
        
        // Nombre de tickets assignés (pour technicien)
        $ticketsAssigned = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ?",
            [$userId]
        );
        
        // Nombre de tickets résolus
        $ticketsResolved = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ? AND status = 'resolu'",
            [$userId]
        );
        
        // Dernière activité (dernier commentaire ou ticket)
        $lastActivity = $db->fetch(
            "SELECT created_at, 'comment' as type FROM comments WHERE user_id = ? 
             UNION 
             SELECT created_at, 'ticket' as type FROM tickets WHERE created_by = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$userId, $userId]
        );
        
        // Récupérer les derniers tickets de l'utilisateur
        $recentTickets = $db->fetchAll(
            "SELECT * FROM tickets WHERE created_by = ? OR assigned_to = ? 
             ORDER BY created_at DESC LIMIT 5",
            [$userId, $userId]
        );
        
        require_once __DIR__ . '/../views/profile/index.php';
    }
}
?>
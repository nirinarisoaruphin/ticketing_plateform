<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../includes/functions.php';

class UserController {
    private $userModel;
    private $notificationModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Liste des utilisateurs
     */
    public function index() {
        global $pageTitle;
        $pageTitle = 'Gestion des utilisateurs';
        
        if (!isAdmin()) {
            setFlash('danger', 'Accès non autorisé. Seul l\'administrateur peut gérer les utilisateurs.');
            redirect('index.php?page=dashboard');
        }
        
        $users = $this->userModel->findAll();
        $stats = $this->getUserStats();
        
        require_once __DIR__ . '/../views/users/index.php';
    }
    
    /**
     * Créer un utilisateur
     */
    public function create() {
        global $pageTitle;
        $pageTitle = 'Ajouter un utilisateur';
        
        if (!isAdmin()) {
            setFlash('danger', 'Accès non autorisé. Seul l\'administrateur peut créer des comptes.');
            redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $full_name = sanitize($_POST['full_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'commercial';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // TOUS LES RÔLES AUTORISÉS
            $allowedRoles = [
                'admin',
                'coordinateur',
                'responsable_sav',
                'responsable_travaux',
                'responsable_support_technique',
                'commercial',
                'charge_etude_electricite',
                'charge_etude_courant_faible',
                'charge_etude_climatisation'
            ];
            
            if (!in_array($role, $allowedRoles)) {
                $role = 'commercial';
            }
            
            $errors = [];
            
            // VALIDATIONS
            if (strlen($username) < 3) {
                $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email n'est pas valide.";
            }
            if (empty($password)) {
                $errors[] = "Le mot de passe ne peut pas être vide.";
            }
            if ($password !== $confirm_password) {
                $errors[] = "Les mots de passe ne correspondent pas.";
            }
            
            // VÉRIFIER L'UNICITÉ
            if ($this->userModel->findByEmail($email)) {
                $errors[] = "Cet email est déjà utilisé.";
            }
            if ($this->userModel->findByUsername($username)) {
                $errors[] = "Ce nom d'utilisateur est déjà pris.";
            }
            
            if (empty($errors)) {
                $data = [
                    'username' => $username,
                    'email' => $email,
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'password' => $password,
                    'role' => $role,
                    'must_change_password' => 0
                ];
                
                $userId = $this->userModel->createUser($data);
                
                if ($userId) {
                    // NOTIFIER LE NOUVEL UTILISATEUR
                    $this->notificationModel->createNotification(
                        $userId,
                        "Votre compte a été créé avec succès. Rôle : " . getRoleLabel($role),
                        "index.php?page=profile",
                        'general'
                    );
                    
                    setFlash('success', 'Utilisateur créé avec succès ! Rôle : ' . getRoleLabel($role));
                    redirect('index.php?page=users');
                } else {
                    setFlash('danger', '❌ Erreur lors de la création de l\'utilisateur.');
                }
            } else {
                $_SESSION['register_errors'] = $errors;
            }
        }
        
        require_once __DIR__ . '/../views/users/create.php';
    }
    
    /**
     * Modifier un utilisateur - CORRIGÉ
     * Le champ "Nom d'utilisateur" est maintenant modifiable
     */
    public function edit() {
        global $pageTitle;
        $pageTitle = 'Modifier un utilisateur';
        
        if (!isAdmin()) {
            setFlash('danger', 'Accès non autorisé. Seul l\'administrateur peut modifier des comptes.');
            redirect('index.php?page=dashboard');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            setFlash('danger', 'Utilisateur non trouvé.');
            redirect('index.php?page=users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $full_name = sanitize($_POST['full_name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'commercial';
            
            // VALIDATION DU NOM D'UTILISATEUR
            $errors = [];
            
            if (strlen($username) < 3) {
                $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.";
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email n'est pas valide.";
            }
            
            // TOUS LES RÔLES AUTORISÉS
            $allowedRoles = [
                'admin',
                'coordinateur',
                'responsable_sav',
                'responsable_travaux',
                'responsable_support_technique',
                'commercial',
                'charge_etude_electricite',
                'charge_etude_courant_faible',
                'charge_etude_climatisation'
            ];
            
            if (!in_array($role, $allowedRoles)) {
                $role = 'commercial';
            }
            
            // VÉRIFIER L'UNICITÉ DU USERNAME
            $existingUsername = $this->userModel->findByUsername($username);
            if ($existingUsername && $existingUsername['id'] != $id) {
                $errors[] = "Ce nom d'utilisateur est déjà utilisé par un autre compte.";
            }
            
            // VÉRIFIER L'UNICITÉ DE L'EMAIL
            $existingEmail = $this->userModel->findByEmail($email);
            if ($existingEmail && $existingEmail['id'] != $id) {
                $errors[] = "Cet email est déjà utilisé par un autre compte.";
            }
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    setFlash('danger', $error);
                }
                redirect('index.php?page=users&action=edit&id=' . $id);
            }
            
            // PRÉPARER LES DONNÉES AVEC LE USERNAME
            $data = [
                'username' => $username,  // AJOUT DU USERNAME MODIFIABLE
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role
            ];
            
            $this->userModel->update($id, $data);
            
            // NOTIFIER L'UTILISATEUR DE LA MODIFICATION
            if ($id != $_SESSION['user_id']) {
                $this->notificationModel->createNotification(
                    $id,
                    "📝 Votre compte a été modifié par l'administrateur.",
                    "index.php?page=profile",
                    'general'
                );
            }
            
            setFlash('success', 'Utilisateur mis à jour avec succès.');
            redirect('index.php?page=users');
        }
        
        require_once __DIR__ . '/../views/users/edit.php';
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete() {
        if (!isAdmin()) {
            setFlash('danger', 'Accès non autorisé. Seul l\'administrateur peut supprimer des comptes.');
            redirect('index.php?page=dashboard');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id == $_SESSION['user_id']) {
            setFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            redirect('index.php?page=users');
        }
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            setFlash('danger', 'Utilisateur non trouvé.');
            redirect('index.php?page=users');
        }
        
        // VÉRIFIER QUE L'UTILISATEUR N'A PAS DE TICKETS EN COURS
        $db = Database::getInstance();
        $activeTickets = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets 
             WHERE (created_by = ? OR assigned_to = ?) 
             AND status NOT IN ('resolu', 'cloture')",
            [$id, $id]
        );
        
        if ($activeTickets && $activeTickets['count'] > 0) {
            setFlash('danger', '⚠️ Cet utilisateur a ' . $activeTickets['count'] . ' ticket(s) en cours. Supprimez-les d\'abord.');
            redirect('index.php?page=users');
        }
        
        $this->userModel->delete($id);
        setFlash('success', 'Utilisateur supprimé avec succès.');
        redirect('index.php?page=users');
    }
    
    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword() {
        if (!isAdmin()) {
            setFlash('danger', 'Accès non autorisé. Seul l\'administrateur peut réinitialiser les mots de passe.');
            redirect('index.php?page=dashboard');
        }
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            setFlash('danger', 'Utilisateur non trouvé.');
            redirect('index.php?page=users');
        }
        
        // ✅ GÉNÉRER UN MOT DE PASSE TEMPORAIRE
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
        $tempPassword = substr(str_shuffle($chars), 0, 12);
        
        $this->userModel->update($id, [
            'password' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'must_change_password' => 1
        ]);
        
        // ✅ NOTIFIER L'UTILISATEUR
        $this->notificationModel->createNotification(
            $id,
            "🔑 Votre mot de passe a été réinitialisé. Connectez-vous avec le nouveau mot de passe.",
            "index.php?page=login",
            'general'
        );
        
        setFlash('success', '✅ Mot de passe réinitialisé. Nouveau mot de passe : <strong>' . $tempPassword . '</strong>');
        redirect('index.php?page=users');
    }
    
    /**
     * Statistiques des utilisateurs
     */
    private function getUserStats() {
        $db = Database::getInstance();
        
        $stats = [
            'total' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
            'by_role' => []
        ];
        
        $roles = [
            'admin', 'coordinateur', 'responsable_sav', 'responsable_travaux',
            'responsable_support_technique', 'commercial',
            'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'
        ];
        
        foreach ($roles as $role) {
            $count = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = ?", [$role]);
            $stats['by_role'][$role] = $count['count'] ?? 0;
        }
        
        return $stats;
    }
}
?>
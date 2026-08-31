<?php
// controllers/AuthController.php - VERSION COMPLÈTE CORRIGÉE
// Gestion de l'authentification avec protection contre le partage de session

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/functions.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    // ============================================
    // LOGIN - CONNEXION
    // ============================================
    
    public function login() {
        global $pageTitle;
        $pageTitle = 'Connexion';
        
        // ✅ Si déjà connecté, rediriger vers le dashboard
        if (isLoggedIn()) {
            redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            
            // ✅ Vérifier les tentatives de connexion (protection brute force)
            $attempts = $this->checkLoginAttempts($email);
            if ($attempts >= 5) {
                setFlash('danger', 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.');
                redirect('index.php?page=login');
            }
            
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                // ✅ RÉGÉNÉRER L'ID DE SESSION POUR ÉVITER LE PARTAGE
                session_regenerate_id(true);
                
                // ✅ SUPPRIMER LES ANCIENNES SESSIONS ORPHELINES
                $this->cleanupOldSessions($user['id']);
                
                // ✅ STOCKER LES INFORMATIONS DE SESSION
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['session_id'] = session_id();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $_SESSION['last_activity'] = time();
                
                // ✅ ENREGISTRER LA DERNIÈRE ACTIVITÉ EN BASE
                $this->userModel->update($user['id'], [
                    'last_activity_at' => date('Y-m-d H:i:s')
                ]);
                
                // ✅ LOG DE CONNEXION
                $this->logLogin($user['id'], true);
                
                if ($user['must_change_password'] ?? false) {
                    setFlash('warning', 'Veuillez changer votre mot de passe.');
                    redirect('index.php?page=change_password');
                }
                
                setFlash('success', 'Bienvenue ' . $user['full_name'] . ' !');
                redirect('index.php?page=dashboard');
            } else {
                // ✅ LOG DE TENTATIVE ÉCHOUÉE
                $this->logLogin($email, false);
                setFlash('danger', 'Email ou mot de passe incorrect.');
                redirect('index.php?page=login');
            }
        }
        
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    // ============================================
    // LOGOUT - DÉCONNEXION
    // ============================================
    
    public function logout() {
        if (isLoggedIn()) {
            // ✅ LOG DE DÉCONNEXION
            $this->logLogin($_SESSION['user_id'], 'logout');
        }
        
        // ✅ DÉTRUIRE LA SESSION
        $_SESSION = array();
        
        // ✅ SUPPRIMER LE COOKIE DE SESSION
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // ✅ DÉTRUIRE LA SESSION
        session_destroy();
        
        // ✅ REDIRIGER VERS LA PAGE DE CONNEXION
        redirect('index.php?page=login');
    }
    
    // ============================================
    // CHANGER LE MOT DE PASSE
    // ============================================
    
    public function changePassword() {
        global $pageTitle;
        $pageTitle = 'Changer votre mot de passe';
        
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }
        
        // ✅ Mettre à jour la dernière activité
        $_SESSION['last_activity'] = time();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $user = $this->userModel->findById($_SESSION['user_id']);
            
            if (!password_verify($currentPassword, $user['password'])) {
                setFlash('danger', 'Le mot de passe actuel est incorrect.');
            } elseif (empty($newPassword)) {
                setFlash('danger', 'Le nouveau mot de passe ne peut pas être vide.');
            } elseif (strlen($newPassword) < 6) {
                setFlash('danger', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
            } elseif ($newPassword !== $confirmPassword) {
                setFlash('danger', 'Les mots de passe ne correspondent pas.');
            } else {
                // ✅ Mettre à jour le mot de passe
                $this->userModel->update($_SESSION['user_id'], [
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'must_change_password' => 0
                ]);
                
                // ✅ Régénérer la session pour plus de sécurité
                session_regenerate_id(true);
                $_SESSION['session_id'] = session_id();
                
                setFlash('success', 'Mot de passe changé avec succès !');
                redirect('index.php?page=dashboard');
            }
        }
        
        require_once __DIR__ . '/../views/auth/change_password.php';
    }
    
    // ============================================
    // PROTECTION CONTRE LE PARTAGE DE SESSION
    // ============================================
    
    /**
     * Vérifier l'intégrité de la session
     * À appeler dans index.php
     */
    public static function validateSession() {
        if (!isLoggedIn()) {
            return false;
        }
        
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $currentSessionId = session_id();
        
        // ✅ Vérifier l'IP (si l'IP change, la session est invalide)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
            self::destroySession('IP modifiée');
            return false;
        }
        
        // ✅ Vérifier le User-Agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUA) {
            self::destroySession('User-Agent modifié');
            return false;
        }
        
        // ✅ Vérifier l'ID de session
        if (isset($_SESSION['session_id']) && $_SESSION['session_id'] !== $currentSessionId) {
            // Si l'ID a changé, le mettre à jour
            $_SESSION['session_id'] = $currentSessionId;
        }
        
        // ✅ Vérifier l'expiration de la session (1 heure)
        $maxLifetime = 3600; // 1 heure
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $maxLifetime) {
            self::destroySession('Session expirée');
            return false;
        }
        
        // ✅ Mettre à jour la dernière activité
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Détruire la session avec un message
     */
    private static function destroySession($reason = '') {
        if ($reason) {
            error_log("🔒 Session détruite: $reason - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
        
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        setFlash('danger', 'Session invalide. Veuillez vous reconnecter.');
        redirect('index.php?page=login');
    }
    
    // ============================================
    // PROTECTION CONTRE LE BRUTE FORCE
    // ============================================
    
    /**
     * Vérifier les tentatives de connexion
     */
    private function checkLoginAttempts($email) {
        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM login_attempts 
             WHERE email = ? AND success = 0 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$email]
        );
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Enregistrer une tentative de connexion
     */
    private function logLogin($identifier, $success) {
        $db = Database::getInstance();
        
        // Si c'est un ID utilisateur, récupérer l'email
        $email = $identifier;
        if (is_numeric($identifier) && $identifier > 0) {
            $user = $db->fetch("SELECT email FROM users WHERE id = ?", [$identifier]);
            if ($user) {
                $email = $user['email'];
            }
        }
        
        $db->insert(
            "INSERT INTO login_attempts (email, success, ip_address, user_agent, attempted_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [
                $email,
                is_bool($success) ? ($success ? 1 : 0) : 0,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
    }
    
    // ============================================
    // NETTOYAGE DES SESSIONS ORPHELINES
    // ============================================
    
    /**
     * Nettoyer les anciennes sessions d'un utilisateur
     */
    private function cleanupOldSessions($userId) {
        // Cette méthode peut être utilisée pour supprimer les anciennes sessions
        // Si vous stockez les sessions en base de données
        // Pour les sessions fichiers, le garbage collector PHP s'en occupe
        
        // Vous pouvez également enregistrer la session active en base
        $db = Database::getInstance();
        $db->query(
            "UPDATE users SET last_activity_at = NOW() WHERE id = ?",
            [$userId]
        );
    }
    
    // ============================================
    // VÉRIFICATION DE SESSION (MIDDLEWARE)
    // ============================================
    
    /**
     * Vérifier que l'utilisateur est connecté et que la session est valide
     * À utiliser comme middleware dans les contrôleurs
     */
    public static function requireAuth() {
        if (!isLoggedIn()) {
            setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
            redirect('index.php?page=login');
        }
        
        if (!self::validateSession()) {
            // La session est invalidée, redirection déjà faite
            exit;
        }
    }
    
    /**
     * Vérifier que l'utilisateur a un rôle spécifique
     */
    public static function requireRole($role) {
        self::requireAuth();
        
        if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
            setFlash('danger', 'Accès non autorisé. Vous n\'avez pas les permissions nécessaires.');
            redirect('index.php?page=dashboard');
        }
    }
    
    /**
     * Vérifier que l'utilisateur a un rôle minimum
     */
    public static function requireMinRole($minRole) {
        self::requireAuth();
        
        $roles = [
            'commercial' => 1,
            'charge_etude_climatisation' => 2,
            'charge_etude_courant_faible' => 3,
            'charge_etude_electricite' => 4,
            'responsable_travaux' => 5,
            'responsable_sav' => 6,
            'responsable_support_technique' => 7,
            'coordinateur' => 8,
            'admin' => 9
        ];
        
        $userRole = $_SESSION['user_role'] ?? 'commercial';
        $minLevel = $roles[$minRole] ?? 1;
        $userLevel = $roles[$userRole] ?? 1;
        
        if ($userLevel < $minLevel && $userRole !== 'admin') {
            setFlash('danger', 'Accès non autorisé. Vous n\'avez pas les permissions nécessaires.');
            redirect('index.php?page=dashboard');
        }
    }
    
    // ============================================
    // RÉCUPÉRATION DES INFORMATIONS UTILISATEUR
    // ============================================
    
    /**
     * Récupérer l'utilisateur actuel
     */
    public static function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT id, username, email, full_name, phone, role, avatar, created_at 
             FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Récupérer le rôle de l'utilisateur actuel
     */
    public static function getCurrentRole() {
        return $_SESSION['user_role'] ?? 'commercial';
    }
    
    /**
     * Récupérer le nom de l'utilisateur actuel
     */
    public static function getCurrentName() {
        return $_SESSION['user_name'] ?? 'Utilisateur';
    }
    
    /**
     * Récupérer l'ID de l'utilisateur actuel
     */
    public static function getCurrentId() {
        return $_SESSION['user_id'] ?? 0;
    }
}
?>
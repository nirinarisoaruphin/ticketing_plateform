<?php

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
        
        // Si déjà connecté, rediriger vers le dashboard
        if (isLoggedIn()) {
            redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            
            // Vérifier les tentatives de connexion (protection brute force)
            $attempts = $this->checkLoginAttempts($email);
            if ($attempts >= 5) {
                setFlash('danger', 'Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.');
                redirect('index.php?page=login');
            }
            
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                // RÉGÉNÉRER L'ID DE SESSION POUR ÉVITER LE PARTAGE
                session_regenerate_id(true);
                
                // SUPPRIMER LES ANCIENNES SESSIONS ORPHELINES
                $this->cleanupOldSessions($user['id']);
                
                // STOCKER LES INFORMATIONS DE SESSION
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
                
                // ENREGISTRER LA DERNIÈRE ACTIVITÉ EN BASE
                $this->userModel->update($user['id'], [
                    'last_activity_at' => date('Y-m-d H:i:s')
                ]);
                
                // LOG DE CONNEXION
                $this->logLogin($user['id'], true);
                
                // Le changement forcé de mot de passe à la première connexion ne
                // concerne plus que l'administrateur : pour les autres rôles, c'est
                // l'administrateur qui définit/réinitialise le mot de passe lui-même.
                if (($user['must_change_password'] ?? false) && $user['role'] === 'admin') {
                    setFlash('warning', 'Veuillez changer votre mot de passe.');
                    redirect('index.php?page=change_password');
                }
                
                setFlash('success', 'Bienvenue ' . $user['full_name'] . ' !');
                redirect('index.php?page=dashboard');
            } else {
                // LOG DE TENTATIVE ÉCHOUÉE
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
            // LOG DE DÉCONNEXION
            $this->logLogin($_SESSION['user_id'], 'logout');
        }
        
        // DÉTRUIRE LA SESSION
        $_SESSION = array();
        
        // SUPPRIMER LE COOKIE DE SESSION
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
        
        // DÉTRUIRE LA SESSION
        session_destroy();
        
        // REDIRIGER VERS LA PAGE DE CONNEXION
        redirect('index.php?page=login');
    }
    
    // ============================================
    // CHANGER LE MOT DE PASSE
    // ============================================
    
    /**
     * Changement de mot de passe moderne avec vérification par code à 4 chiffres
     * envoyé par email (in-app). Aucune limite de longueur sur le nouveau mot de passe.
     */
    public function changePassword() {
        global $pageTitle;
        $pageTitle = 'Changer votre mot de passe';
        
        if (!isLoggedIn()) {
            redirect('index.php?page=login');
        }
        
        // SEUL L'ADMINISTRATEUR PEUT CHANGER SON MOT DE PASSE LUI-MÊME.
        // Pour tous les autres rôles, c'est l'administrateur qui gère le
        // changement de mot de passe depuis "Gestion des utilisateurs".
        if (!isAdmin()) {
            setFlash('danger', "Accès non autorisé. Seul l'administrateur peut changer votre mot de passe. Veuillez le contacter.");
            redirect('index.php?page=profile');
        }
        
        $_SESSION['last_activity'] = time();
        
        $db = Database::getInstance();
        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        
        // S'assurer que la table OTP existe
        $this->ensurePasswordOtpTable($db);
        
        $step = $_SESSION['pwd_change_step'] ?? 'form'; // form | verify
        $maskedEmail = $this->maskEmail($user['email'] ?? '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'send_code';
            
            // ===== ÉTAPE 1 : Envoyer le code =====
            if ($action === 'send_code' || $action === 'resend_code') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword     = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                // Pour resend, on récupère depuis la session si les champs ne sont plus envoyés
                if ($action === 'resend_code' && empty($newPassword) && !empty($_SESSION['pwd_change_new_hash'])) {
                    // On renvoie juste un nouveau code avec le hash déjà stocké
                    $otpResult = $this->createAndSendPasswordOtp($db, $user, $_SESSION['pwd_change_new_hash']);
                    if ($otpResult['success']) {
                        setFlash('success', 'Un nouveau code à 4 chiffres a été envoyé à ' . $maskedEmail);
                        $step = 'verify';
                        $_SESSION['pwd_change_step'] = 'verify';
                    } else {
                        setFlash('danger', $otpResult['message']);
                    }
                } else {
                    // Validation
                    $currentOk = false;
                    if ($user && $currentPassword !== '') {
                        if (password_verify($currentPassword, $user['password'])) {
                            $currentOk = true;
                        } elseif ($user['password'] === $currentPassword) {
                            // Migration ancien mot de passe en clair
                            $this->userModel->update($userId, ['password' => password_hash($currentPassword, PASSWORD_DEFAULT)]);
                            $currentOk = true;
                        }
                    }
                    
                    if (!$currentOk) {
                        setFlash('danger', 'Le mot de passe actuel est incorrect.');
                    } elseif ($newPassword === '') {
                        setFlash('danger', 'Le nouveau mot de passe ne peut pas être vide.');
                    } elseif ($newPassword !== $confirmPassword) {
                        setFlash('danger', 'Les mots de passe ne correspondent pas.');
                    } else {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $otpResult = $this->createAndSendPasswordOtp($db, $user, $newHash);
                        
                        if ($otpResult['success']) {
                            $_SESSION['pwd_change_new_hash'] = $newHash;
                            $_SESSION['pwd_change_step'] = 'verify';
                            $_SESSION['pwd_change_expires'] = time() + 600; // 10 min
                            setFlash('success', 'Un code de vérification à 4 chiffres a été envoyé à ' . $maskedEmail);
                            $step = 'verify';
                        } else {
                            setFlash('danger', $otpResult['message']);
                        }
                    }
                }
            }
            
            // ===== ÉTAPE 2 : Vérifier le code =====
            elseif ($action === 'verify_code') {
                $code = preg_replace('/\D/', '', $_POST['otp_code'] ?? '');
                
                if (strlen($code) !== 4) {
                    setFlash('danger', 'Le code doit contenir exactement 4 chiffres.');
                    $step = 'verify';
                } else {
                    $otp = $db->fetch(
                        "SELECT * FROM user_password_otps 
                         WHERE user_id = ? AND requested_by = ? AND used = 0 
                         ORDER BY id DESC LIMIT 1",
                        [$userId, $userId]
                    );
                    
                    if (!$otp) {
                        setFlash('danger', 'Aucun code en attente. Veuillez recommencer.');
                        $this->clearPwdChangeSession();
                        $step = 'form';
                    } elseif (strtotime($otp['expires_at']) < time()) {
                        setFlash('danger', 'Le code a expiré. Veuillez en demander un nouveau.');
                        $step = 'verify';
                    } elseif ((int)$otp['attempts'] >= 5) {
                        setFlash('danger', 'Trop de tentatives. Veuillez recommencer.');
                        $this->clearPwdChangeSession();
                        $step = 'form';
                    } elseif (!hash_equals($otp['code'], $code)) {
                        $db->query("UPDATE user_password_otps SET attempts = attempts + 1 WHERE id = ?", [$otp['id']]);
                        $remaining = max(0, 4 - (int)$otp['attempts']); // attempts avant +1 → il reste 4,3,2,1,0
                        setFlash('danger', 'Code incorrect. Il vous reste ' . $remaining . ' tentative(s).');
                        $step = 'verify';
                    } else {
                        // Code valide → appliquer le nouveau mot de passe
                        $this->userModel->update($userId, [
                            'password' => $otp['new_password_hash'],
                            'must_change_password' => 0
                        ]);
                        
                        $db->query("UPDATE user_password_otps SET used = 1 WHERE id = ?", [$otp['id']]);
                        
                        session_regenerate_id(true);
                        $_SESSION['session_id'] = session_id();
                        $this->clearPwdChangeSession();
                        
                        setFlash('success', 'Mot de passe changé avec succès !');
                        redirect('index.php?page=dashboard');
                    }
                }
            }
            
            // ===== Annuler =====
            elseif ($action === 'cancel') {
                $this->clearPwdChangeSession();
                $step = 'form';
                setFlash('info', 'Changement de mot de passe annulé.');
            }
        }
        
        // Variables pour la vue
        $viewStep = $step;
        $viewMaskedEmail = $maskedEmail;
        
        require_once __DIR__ . '/../views/auth/change_password.php';
    }
    
    private function clearPwdChangeSession() {
        unset($_SESSION['pwd_change_step'], $_SESSION['pwd_change_new_hash'], $_SESSION['pwd_change_expires']);
    }
    
    private function maskEmail($email) {
        if (empty($email) || strpos($email, '@') === false) {
            return '***@***';
        }
        list($local, $domain) = explode('@', $email, 2);
        $localMasked = strlen($local) <= 2 ? str_repeat('*', strlen($local)) : substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));
        return $localMasked . '@' . $domain;
    }
    
    private function ensurePasswordOtpTable($db) {
        try {
            $db->query("
                CREATE TABLE IF NOT EXISTS `user_password_otps` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `user_id` INT NOT NULL,
                    `requested_by` INT NOT NULL,
                    `code` VARCHAR(6) NOT NULL,
                    `new_password_hash` VARCHAR(255) NOT NULL,
                    `attempts` INT NOT NULL DEFAULT 0,
                    `used` TINYINT(1) NOT NULL DEFAULT 0,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_otp_user` (`user_id`),
                    INDEX `idx_otp_requested_by` (`requested_by`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("ensurePasswordOtpTable: " . $e->getMessage());
        }
    }
    
    private function createAndSendPasswordOtp($db, $user, $newPasswordHash) {
        $userId = (int)$user['id'];
        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        // Invalider les anciens codes non utilisés
        $db->query(
            "UPDATE user_password_otps SET used = 1 WHERE user_id = ? AND requested_by = ? AND used = 0",
            [$userId, $userId]
        );
        
        $db->insert(
            "INSERT INTO user_password_otps (user_id, requested_by, code, new_password_hash, expires_at) VALUES (?, ?, ?, ?, ?)",
            [$userId, $userId, $code, $newPasswordHash, $expiresAt]
        );
        
        // Envoi email
        $email = $user['email'] ?? '';
        if (empty($email)) {
            return ['success' => false, 'message' => 'Aucune adresse email associée à votre compte.'];
        }
        
        require_once __DIR__ . '/../includes/Mailer.php';
        require_once __DIR__ . '/../config/mail.php';
        
        $mailer = new Mailer();
        $subject = 'Code de vérification – Changement de mot de passe';
        $name = htmlspecialchars($user['full_name'] ?? 'Utilisateur');
        $message = '
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 480px; margin: 0 auto; padding: 24px;">
            <div style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); border-radius: 16px 16px 0 0; padding: 28px; text-align: center;">
                <h1 style="color: #fff; margin: 0; font-size: 20px; font-weight: 600;">Sécurité de votre compte</h1>
            </div>
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 16px 16px; padding: 32px 28px;">
                <p style="color: #374151; font-size: 15px; margin: 0 0 16px;">Bonjour <strong>' . $name . '</strong>,</p>
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 24px; line-height: 1.5;">
                    Vous avez demandé à changer votre mot de passe. Utilisez le code ci-dessous pour confirmer cette opération. Il expire dans <strong>10 minutes</strong>.
                </p>
                <div style="background: #f0fdfa; border: 2px dashed #14b8a6; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px;">
                    <span style="font-size: 32px; font-weight: 700; letter-spacing: 12px; color: #0f766e; font-family: monospace;">' . $code . '</span>
                </div>
                <p style="color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.4;">
                    Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email et contactez un administrateur. Ne partagez jamais ce code.
                </p>
            </div>
            <p style="text-align: center; color: #9ca3af; font-size: 11px; margin-top: 16px;">Plateforme de Ticketing – Spider Madagascar</p>
        </div>';
        
        $sent = $mailer->send($email, $subject, $message, $user['full_name'] ?? '');
        
        // Toujours logger le code en cas de problème SMTP (dev / debug)
        error_log("PWD_OTP user={$userId} code={$code} email={$email} sent=" . ($sent ? '1' : '0'));
        
        if (!$sent) {
            // En environnement sans mail configuré, on considère quand même le code valide
            // (le code est dans les logs). On informe l'utilisateur.
            return [
                'success' => true,
                'message' => 'Code généré. Vérifiez votre boîte mail (ou les logs si l\'email n\'est pas configuré).'
            ];
        }
        
        return ['success' => true, 'message' => 'Code envoyé'];
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
        
        // Vérifier l'IP (si l'IP change, la session est invalide)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
            self::destroySession('IP modifiée');
            return false;
        }
        
        // Vérifier le User-Agent
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUA) {
            self::destroySession('User-Agent modifié');
            return false;
        }
        
        // Vérifier l'ID de session
        if (isset($_SESSION['session_id']) && $_SESSION['session_id'] !== $currentSessionId) {
            // Si l'ID a changé, le mettre à jour
            $_SESSION['session_id'] = $currentSessionId;
        }
        
        // Vérifier l'expiration de la session (1 heure)
        $maxLifetime = 3600; // 1 heure
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $maxLifetime) {
            self::destroySession('Session expirée');
            return false;
        }
        
        // Mettre à jour la dernière activité
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Détruire la session avec un message
     */
    private static function destroySession($reason = '') {
        if ($reason) {
            error_log("Session détruite: $reason - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
            'charge_etude_courant_fort' => 4,
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
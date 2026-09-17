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
                'charge_etude_courant_fort',
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
                    setFlash('danger', 'Erreur lors de la création de l\'utilisateur.');
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
                'charge_etude_courant_fort',
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
                'username' => $username, // AJOUT DU USERNAME MODIFIABLE
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
                    "Votre compte a été modifié par l'administrateur.",
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
            setFlash('danger', 'Cet utilisateur a ' . $activeTickets['count'] . ' ticket(s) en cours. Supprimez-les d\'abord.');
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
        
        // GÉNÉRER UN MOT DE PASSE TEMPORAIRE
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
        $tempPassword = substr(str_shuffle($chars), 0, 12);
        
        $this->userModel->update($id, [
            'password' => password_hash($tempPassword, PASSWORD_DEFAULT),
            'must_change_password' => 1
        ]);
        
        // NOTIFIER L'UTILISATEUR
        $this->notificationModel->createNotification(
            $id,
            "Votre mot de passe a été réinitialisé. Connectez-vous avec le nouveau mot de passe.",
            "index.php?page=login",
            'general'
        );
        
        setFlash('success', 'Mot de passe réinitialisé. Nouveau mot de passe : <strong>' . $tempPassword . '</strong>');
        redirect('index.php?page=users');
    }
    
    /**
     * Changement de mot de passe d'un utilisateur, depuis la page "Modifier
     * l'utilisateur", réservé à l'administrateur. Même méthode de sécurité que
     * "Changer mot de passe" du profil admin : un code de confirmation à
     * 4 chiffres est envoyé par email (in-app) à l'administrateur connecté,
     * avant que le nouveau mot de passe ne soit appliqué.
     * Aucune contrainte de longueur/complexité sur le nouveau mot de passe.
     * Réponse JSON (appelé en AJAX). Étapes ('step') : send_code | resend_code | verify_code
     */
    public function changePassword() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Accès non autorisé."]);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => "Méthode non autorisée."]);
            exit;
        }
        
        $db = Database::getInstance();
        $this->ensurePasswordOtpTable($db);
        
        $adminId = (int)$_SESSION['user_id'];
        $admin = $this->userModel->findById($adminId);
        $step = $_POST['step'] ?? 'send_code';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => "Utilisateur introuvable."]);
            exit;
        }
        
        // ===== ÉTAPE 1 : générer le nouveau mot de passe + envoyer le code =====
        if ($step === 'send_code') {
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // VÉRIFIER LE MOT DE PASSE ACTUEL DE L'ADMINISTRATEUR CONNECTÉ (confirmation d'identité)
            $currentPasswordOk = false;
            if ($admin && $currentPassword !== '') {
                if (password_verify($currentPassword, $admin['password'])) {
                    $currentPasswordOk = true;
                } elseif ($admin['password'] === $currentPassword) {
                    // Ancien compte avec mot de passe encore en clair en base -> on migre vers un hash
                    $this->userModel->update($admin['id'], [
                        'password' => password_hash($currentPassword, PASSWORD_DEFAULT)
                    ]);
                    $currentPasswordOk = true;
                }
            }
            if (!$currentPasswordOk) {
                echo json_encode(['success' => false, 'message' => "Mot de passe actuel incorrect."]);
                exit;
            }
            
            if ($newPassword === '') {
                echo json_encode(['success' => false, 'message' => "Le nouveau mot de passe ne peut pas être vide."]);
                exit;
            }
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => "Les deux mots de passe ne correspondent pas."]);
                exit;
            }
            
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $otpResult = $this->createAndSendPasswordOtp($db, $user, $admin, $newHash);
            
            if ($otpResult['success']) {
                echo json_encode([
                    'success' => true,
                    'step' => 'verify',
                    'masked_email' => $this->maskEmail($admin['email'] ?? ''),
                    'message' => $otpResult['message']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $otpResult['message']]);
            }
            exit;
        }
        
        // ===== Renvoyer un code (réutilise le mot de passe déjà préparé) =====
        if ($step === 'resend_code') {
            $otp = $db->fetch(
                "SELECT * FROM user_password_otps WHERE user_id = ? AND requested_by = ? AND used = 0 ORDER BY id DESC LIMIT 1",
                [$id, $adminId]
            );
            if (!$otp) {
                echo json_encode(['success' => false, 'message' => "Aucune demande en cours. Veuillez recommencer."]);
                exit;
            }
            $otpResult = $this->createAndSendPasswordOtp($db, $user, $admin, $otp['new_password_hash']);
            if ($otpResult['success']) {
                echo json_encode([
                    'success' => true,
                    'step' => 'verify',
                    'masked_email' => $this->maskEmail($admin['email'] ?? ''),
                    'message' => 'Nouveau code envoyé.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $otpResult['message']]);
            }
            exit;
        }
        
        // ===== ÉTAPE 2 : vérifier le code et appliquer le mot de passe =====
        if ($step === 'verify_code') {
            $code = preg_replace('/\D/', '', $_POST['otp_code'] ?? '');
            
            if (strlen($code) !== 4) {
                echo json_encode(['success' => false, 'message' => "Le code doit contenir exactement 4 chiffres."]);
                exit;
            }
            
            $otp = $db->fetch(
                "SELECT * FROM user_password_otps WHERE user_id = ? AND requested_by = ? AND used = 0 ORDER BY id DESC LIMIT 1",
                [$id, $adminId]
            );
            
            if (!$otp) {
                echo json_encode(['success' => false, 'message' => "Aucun code en attente. Veuillez recommencer.", 'reset' => true]);
                exit;
            }
            if (strtotime($otp['expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => "Le code a expiré. Veuillez en demander un nouveau."]);
                exit;
            }
            if ((int)$otp['attempts'] >= 5) {
                echo json_encode(['success' => false, 'message' => "Trop de tentatives. Veuillez recommencer.", 'reset' => true]);
                exit;
            }
            if (!hash_equals($otp['code'], $code)) {
                $db->query("UPDATE user_password_otps SET attempts = attempts + 1 WHERE id = ?", [$otp['id']]);
                $remaining = max(0, 4 - (int)$otp['attempts']);
                echo json_encode(['success' => false, 'message' => "Code incorrect. Il vous reste {$remaining} tentative(s)."]);
                exit;
            }
            
            // Code valide -> appliquer le nouveau mot de passe à l'utilisateur cible
            $this->userModel->update($id, [
                'password' => $otp['new_password_hash']
            ]);
            $db->query("UPDATE user_password_otps SET used = 1 WHERE id = ?", [$otp['id']]);
            
            echo json_encode(['success' => true, 'step' => 'done', 'message' => "Mot de passe changé avec succès."]);
            exit;
        }
        
        // ===== Annuler =====
        if ($step === 'cancel') {
            $db->query(
                "UPDATE user_password_otps SET used = 1 WHERE user_id = ? AND requested_by = ? AND used = 0",
                [$id, $adminId]
            );
            echo json_encode(['success' => true]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => "Étape inconnue."]);
        exit;
    }
    
    /**
     * Crée la table user_password_otps si besoin (au cas où la migration SQL
     * n'a pas encore été appliquée).
     */
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
    
    private function maskEmail($email) {
        if (empty($email) || strpos($email, '@') === false) {
            return '***@***';
        }
        list($local, $domain) = explode('@', $email, 2);
        $localMasked = strlen($local) <= 2 ? str_repeat('*', strlen($local)) : substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 2));
        return $localMasked . '@' . $domain;
    }
    
    /**
     * Génère un code à 4 chiffres, le stocke avec le nouveau mot de passe (déjà
     * haché) pour l'utilisateur cible, et envoie ce code par email à
     * l'administrateur connecté (vérification "step-up").
     */
    private function createAndSendPasswordOtp($db, $targetUser, $admin, $newPasswordHash) {
        $targetId = (int)$targetUser['id'];
        $adminId = (int)$admin['id'];
        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        // Invalider les anciens codes non utilisés pour cette même demande
        $db->query(
            "UPDATE user_password_otps SET used = 1 WHERE user_id = ? AND requested_by = ? AND used = 0",
            [$targetId, $adminId]
        );
        
        $db->insert(
            "INSERT INTO user_password_otps (user_id, requested_by, code, new_password_hash, expires_at) VALUES (?, ?, ?, ?, ?)",
            [$targetId, $adminId, $code, $newPasswordHash, $expiresAt]
        );
        
        $email = $admin['email'] ?? '';
        if (empty($email)) {
            return ['success' => false, 'message' => 'Aucune adresse email associée à votre compte administrateur.'];
        }
        
        require_once __DIR__ . '/../includes/Mailer.php';
        require_once __DIR__ . '/../config/mail.php';
        
        $mailer = new Mailer();
        $subject = 'Code de vérification – Changement de mot de passe utilisateur';
        $adminName = htmlspecialchars($admin['full_name'] ?? 'Administrateur');
        $targetName = htmlspecialchars($targetUser['full_name'] ?? $targetUser['username'] ?? 'un utilisateur');
        $message = '
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 480px; margin: 0 auto; padding: 24px;">
            <div style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); border-radius: 16px 16px 0 0; padding: 28px; text-align: center;">
                <h1 style="color: #fff; margin: 0; font-size: 20px; font-weight: 600;">Sécurité de votre compte</h1>
            </div>
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 16px 16px; padding: 32px 28px;">
                <p style="color: #374151; font-size: 15px; margin: 0 0 16px;">Bonjour <strong>' . $adminName . '</strong>,</p>
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 24px; line-height: 1.5;">
                    Vous avez demandé à changer le mot de passe du compte <strong>' . $targetName . '</strong>.
                    Utilisez le code ci-dessous pour confirmer cette opération. Il expire dans <strong>10 minutes</strong>.
                </p>
                <div style="background: #f0fdfa; border: 2px dashed #14b8a6; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px;">
                    <span style="font-size: 32px; font-weight: 700; letter-spacing: 12px; color: #0f766e; font-family: monospace;">' . $code . '</span>
                </div>
                <p style="color: #9ca3af; font-size: 12px; margin: 0; line-height: 1.4;">
                    Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email et sécurisez votre compte. Ne partagez jamais ce code.
                </p>
            </div>
            <p style="text-align: center; color: #9ca3af; font-size: 11px; margin-top: 16px;">Plateforme de Ticketing – Spider Madagascar</p>
        </div>';
        
        $sent = $mailer->send($email, $subject, $message, $admin['full_name'] ?? '');
        
        error_log("PWD_OTP target_user={$targetId} requested_by={$adminId} code={$code} email={$email} sent=" . ($sent ? '1' : '0'));
        
        if (!$sent) {
            return [
                'success' => true,
                'message' => 'Code généré. Vérifiez votre boîte mail (ou les logs si l\'email n\'est pas configuré).'
            ];
        }
        
        return ['success' => true, 'message' => 'Code envoyé par email.'];
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
            'charge_etude_courant_fort', 'charge_etude_courant_faible', 'charge_etude_climatisation'
        ];
        
        foreach ($roles as $role) {
            $count = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = ?", [$role]);
            $stats['by_role'][$role] = $count['count'] ?? 0;
        }
        
        return $stats;
    }
}
?>
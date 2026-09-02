<?php
// includes/functions.php - VERSION COMPLÈTE CORRIGÉE
// Toutes les fonctions utilitaires de la plateforme

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// AUTHENTIFICATION
// ============================================

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Vérifier si l'utilisateur est authentifié pour l'API
 * ✅ UNIQUE DÉCLARATION - CORRIGÉ
 */
function isApiAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Rediriger vers une URL
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit;
    }
}

// ============================================
// VALIDATION DE SESSION
// ============================================

/**
 * Vérifier l'intégrité de la session (IP + User-Agent + Temps)
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $currentSessionId = session_id();
    
    // ✅ Vérifier l'IP
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
        destroySession('IP modifiée');
        return false;
    }
    
    // ✅ Vérifier le User-Agent
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUA) {
        destroySession('User-Agent modifié');
        return false;
    }
    
    // ✅ Vérifier l'ID de session
    if (isset($_SESSION['session_id']) && $_SESSION['session_id'] !== $currentSessionId) {
        $_SESSION['session_id'] = $currentSessionId;
    }
    
    // ✅ Vérifier l'expiration (1 heure)
    $maxLifetime = 3600;
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $maxLifetime) {
        destroySession('Session expirée');
        return false;
    }
    
    // ✅ Mettre à jour la dernière activité
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Détruire la session proprement
 */
function destroySession($reason = '') {
    if ($reason) {
        error_log("🔒 Session détruite: $reason - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    $_SESSION = array();
    
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
    
    session_destroy();
    
    setFlash('danger', 'Session invalide. Veuillez vous reconnecter.');
    redirect('index.php?page=login');
    exit;
}

// ============================================
// CSRF PROTECTION
// ============================================

/**
 * Générer un token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier le token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Champ CSRF pour les formulaires
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// ============================================
// GESTION DES RÔLES
// ============================================

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Vérifier si l'utilisateur a un rôle minimum
 */
function hasMinRole($minRole) {
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
    
    if (!isset($roles[$userRole]) || !isset($roles[$minRole])) {
        if ($userRole === 'admin') {
            return true;
        }
        return false;
    }
    
    return $roles[$userRole] >= $roles[$minRole];
}

/**
 * Vérifier si l'utilisateur est administrateur
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Vérifier si l'utilisateur est coordinateur
 */
function isCoordinateur() {
    return hasRole('coordinateur');
}

/**
 * Vérifier si l'utilisateur est commercial
 */
function isCommercial() {
    return hasRole('commercial');
}

/**
 * Vérifier si l'utilisateur est responsable
 */
function isResponsable() {
    $responsableRoles = [
        'responsable_support_technique',
        'responsable_sav',
        'responsable_travaux'
    ];
    $userRole = $_SESSION['user_role'] ?? '';
    return in_array($userRole, $responsableRoles);
}

/**
 * Vérifier si l'utilisateur est chargé d'étude
 */
function isChargeEtude() {
    $chargeRoles = [
        'charge_etude_electricite',
        'charge_etude_courant_faible',
        'charge_etude_climatisation'
    ];
    $userRole = $_SESSION['user_role'] ?? '';
    return in_array($userRole, $chargeRoles);
}

// ============================================
// PERMISSIONS PLANNING
// ============================================

/**
 * Vérifier si l'utilisateur peut voir le planning (lecture seule)
 */
function canViewPlanningOnly() {
    $role = $_SESSION['user_role'] ?? 'commercial';
    
    $allowedRoles = [
        'admin',
        'coordinateur',
        'responsable_support_technique',
        'responsable_sav',
        'responsable_travaux',
        'charge_etude_electricite',
        'charge_etude_courant_faible',
        'charge_etude_climatisation',
        'commercial'  // ✅ Commercial peut voir en lecture seule
    ];
    
    return in_array($role, $allowedRoles);
}

/**
 * Vérifier si l'utilisateur peut gérer le planning (ajouter, modifier, supprimer)
 */
function canManagePlanning() {
    $role = $_SESSION['user_role'] ?? 'commercial';
    
    $allowedRoles = [
        'admin',
        'coordinateur',
        'responsable_support_technique',
        'responsable_sav',
        'responsable_travaux'
    ];
    
    return in_array($role, $allowedRoles);
}

// ============================================
// PERMISSIONS D'ACCÈS AUX PAGES
// ============================================

/**
 * Vérifier si l'utilisateur peut accéder à une page
 */
function canAccessPage($page) {
    if (!isLoggedIn()) {
        return $page === 'login' || $page === 'register';
    }
    
    $role = $_SESSION['user_role'] ?? 'commercial';
    
    $publicPages = ['dashboard', 'profile', 'logout', 'change_password', 'historique', 'messages'];
    if (in_array($page, $publicPages)) {
        return true;
    }
    
    if ($role === 'admin') {
        return true;
    }
    
    if ($role === 'coordinateur') {
        return $page !== 'users';
    }
    
    if (in_array($role, ['responsable_support_technique', 'responsable_sav', 'responsable_travaux'])) {
        return $page !== 'users';
    }
    
    if (in_array($role, ['charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation'])) {
        switch ($page) {
            case 'tickets':
            case 'planning':
            case 'export':
            case 'historique':
            case 'messages':
                return true;
            case 'users':
                return false;
            default:
                return true;
        }
    }
    
    if ($role === 'commercial') {
        switch ($page) {
            case 'tickets':
            case 'historique':
            case 'messages':
            case 'planning':  // ✅ Commercial peut voir le planning
                return true;
            case 'export':
                return false;
            case 'users':
                return false;
            default:
                return false;
        }
    }
    
    return false;
}

// ============================================
// PERMISSIONS SPÉCIFIQUES
// ============================================

/**
 * Vérifier si l'utilisateur peut gérer les utilisateurs
 */
function canManageUsers() {
    $role = $_SESSION['user_role'] ?? 'commercial';
    return $role === 'admin';
}

/**
 * Vérifier si l'utilisateur peut gérer les interventions
 */
function canManageInterventions() {
    return canManagePlanning();
}

/**
 * Vérifier si l'utilisateur peut exporter les données
 */
function canExportData() {
    $role = $_SESSION['user_role'] ?? 'commercial';
    return in_array($role, [
        'admin', 
        'coordinateur',
        'responsable_support_technique', 
        'responsable_sav', 
        'responsable_travaux',
        'charge_etude_electricite',
        'charge_etude_courant_faible',
        'charge_etude_climatisation'
    ]);
}

/**
 * Vérifier si l'utilisateur peut supprimer un ticket
 */
function canDeleteTicket($ticket = null) {
    $role = $_SESSION['user_role'] ?? 'commercial';
    
    if ($role === 'admin') {
        return true;
    }
    
    $responsableRoles = [
        'responsable_sav',
        'responsable_travaux',
        'responsable_support_technique'
    ];
    
    if (in_array($role, $responsableRoles)) {
        if ($ticket && isset($ticket['category'])) {
            $categoryMap = [
                'sav' => 'responsable_sav',
                'travaux' => 'responsable_travaux',
                'support_technique' => 'responsable_support_technique',
                'bureau_etude' => 'responsable_support_technique'
            ];
            $requiredRole = $categoryMap[$ticket['category']] ?? null;
            return $role === $requiredRole;
        }
        return true;
    }
    
    return false;
}

// ============================================
// AFFICHAGE DES RÔLES
// ============================================

/**
 * Récupérer le label d'un rôle
 */
function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrateur',
        'coordinateur' => 'Coordinateur / Coordinatrice',
        'responsable_support_technique' => 'Responsable Support Technique',
        'responsable_sav' => 'Responsable SAV',
        'responsable_travaux' => 'Responsable Travaux',
        'commercial' => 'Commercial',
        'charge_etude_electricite' => 'Chargé d\'Étude Electricité',
        'charge_etude_courant_faible' => 'Chargé d\'Étude Courant Faible',
        'charge_etude_climatisation' => 'Chargé d\'Étude Climatisation'
    ];
    return $labels[$role] ?? $role;
}

/**
 * Récupérer l'icône d'un rôle
 */
function getRoleIcon($role) {
    $icons = [
        'admin' => 'fa-crown',
        'coordinateur' => 'fa-user-tie',
        'responsable_support_technique' => 'fa-flask',
        'responsable_sav' => 'fa-headset',
        'responsable_travaux' => 'fa-hard-hat',
        'commercial' => 'fa-handshake',
        'charge_etude_electricite' => 'fa-bolt',
        'charge_etude_courant_faible' => 'fa-wifi',
        'charge_etude_climatisation' => 'fa-snowflake'
    ];
    return $icons[$role] ?? 'fa-user';
}

/**
 * Récupérer la classe CSS pour l'avatar d'un rôle
 */
function getRoleAvatarClass($role) {
    $classes = [
        'admin' => 'bg-red-500',
        'coordinateur' => 'bg-purple-500',
        'responsable_support_technique' => 'bg-indigo-500',
        'responsable_sav' => 'bg-pink-500',
        'responsable_travaux' => 'bg-amber-500',
        'commercial' => 'bg-blue-500',
        'charge_etude_electricite' => 'bg-orange-500',
        'charge_etude_courant_faible' => 'bg-cyan-500',
        'charge_etude_climatisation' => 'bg-emerald-500'
    ];
    return $classes[$role] ?? 'bg-gray-500';
}

/**
 * Récupérer la couleur d'un rôle
 */
function getRoleColor($role) {
    $colors = [
        'admin' => '#EF4444',
        'coordinateur' => '#8B5CF6',
        'responsable_support_technique' => '#4F46E5',
        'responsable_sav' => '#EC4899',
        'responsable_travaux' => '#F59E0B',
        'commercial' => '#3B82F6',
        'charge_etude_electricite' => '#F97316',
        'charge_etude_courant_faible' => '#06B6D4',
        'charge_etude_climatisation' => '#10B981'
    ];
    return $colors[$role] ?? '#6B7280';
}

/**
 * Récupérer la classe CSS pour le badge d'un rôle
 */
function getRoleBadgeClass($role) {
    $classes = [
        'admin' => 'badge-admin',
        'coordinateur' => 'badge-coordinateur',
        'commercial' => 'badge-commercial',
        'responsable_support_technique' => 'badge-responsable_support_technique',
        'responsable_sav' => 'badge-responsable_sav',
        'responsable_travaux' => 'badge-responsable_travaux',
        'charge_etude_electricite' => 'badge-charge_etude_electricite',
        'charge_etude_courant_faible' => 'badge-charge_etude_courant_faible',
        'charge_etude_climatisation' => 'badge-charge_etude_climatisation'
    ];
    return $classes[$role] ?? 'badge-commercial';
}

/**
 * Récupérer la liste des rôles disponibles
 */
function getAvailableRoles() {
    return [
        'admin' => 'Administrateur',
        'coordinateur' => 'Coordinateur / Coordinatrice',
        'responsable_support_technique' => 'Responsable Support Technique',
        'responsable_sav' => 'Responsable SAV',
        'responsable_travaux' => 'Responsable Travaux',
        'commercial' => 'Commercial',
        'charge_etude_electricite' => 'Chargé d\'Étude Electricité',
        'charge_etude_courant_faible' => 'Chargé d\'Étude Courant Faible',
        'charge_etude_climatisation' => 'Chargé d\'Étude Climatisation'
    ];
}

/**
 * Récupérer l'équipe d'un utilisateur
 */
function getUserTeam($role) {
    $teams = [
        'responsable_support_technique' => 'Support Technique',
        'charge_etude_electricite' => 'Support Technique',
        'charge_etude_courant_faible' => 'Support Technique',
        'charge_etude_climatisation' => 'Support Technique',
        'responsable_sav' => 'SAV',
        'responsable_travaux' => 'Travaux',
        'commercial' => 'Commercial',
        'coordinateur' => 'Coordination',
        'admin' => 'Administration'
    ];
    return $teams[$role] ?? 'Autre';
}

// ============================================
// GESTION DES TICKETS
// ============================================

/**
 * Générer un numéro de ticket UNIQUE - CORRIGÉ AVEC TICKET_SEQUENCES
 */
function generateTicketNumber($category = null) {
    $db = Database::getInstance();
    
    $prefix = 'TK-';
    
    switch ($category) {
        case 'support_technique':
        case 'bureau_etude':
            $prefix = 'TK-ST';
            break;
        case 'sav':
            $prefix = 'TK-SAV';
            break;
        case 'travaux':
            $prefix = 'TK-TVX';
            break;
        default:
            $prefix = 'TK-GEN';
            break;
    }
    
    $year = date('Y');
    
    try {
        // ✅ Utiliser la table ticket_sequences pour un compteur atomique
        $pdo = $db->getPDO();
        $stmt = $pdo->prepare(
            "INSERT INTO ticket_sequences (category_prefix, year, next_number)
             VALUES (?, ?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE next_number = LAST_INSERT_ID(next_number + 1)"
        );
        $stmt->execute([$prefix, $year]);
        $nextNumber = (int)$pdo->lastInsertId();
        
        // Sécurité : ne jamais accepter 0
        if ($nextNumber < 1) {
            throw new Exception("Compteur invalide retourné (0), fallback nécessaire");
        }
    } catch (Exception $e) {
        // ✅ Fallback si la table n'existe pas encore
        error_log("⚠️ ticket_sequences indisponible, fallback COUNT(): " . $e->getMessage());
        $likePattern = $prefix . $year . '%';
        $count = $db->fetch(
            "SELECT COUNT(*) as count FROM tickets WHERE ticket_number LIKE ?",
            [$likePattern]
        );
        $nextNumber = ($count ? (int)$count['count'] : 0) + 1;
    }
    
    $number = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    return $prefix . $year . '-' . $number;
}

/**
 * Nettoyer une chaîne de caractères
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// ============================================
// GESTION DES FLASH MESSAGES
// ============================================

/**
 * Définir un message flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupérer et supprimer un message flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================
// FORMATAGE DES DATES
// ============================================

/**
 * Formater une date
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formater une date (uniquement la date)
 */
function formatDateOnly($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// ============================================
// LABELS DES TICKETS
// ============================================

/**
 * Récupérer le label d'un statut
 */
function getStatusLabel($status) {
    $map = [
        'nouveau' => 'Nouveau',
        'assigne' => 'Assigné',
        'en_cours' => 'En cours',
        'en_attente' => 'En attente',
        'resolu' => 'Résolu',
        'cloture' => 'Clôturé'
    ];
    return $map[$status] ?? $status;
}

/**
 * Récupérer le label d'une priorité
 */
function getPriorityLabel($priority) {
    $map = [
        'basse' => 'Basse',
        'moyenne' => 'Moyenne',
        'haute' => 'Haute',
        'critique' => 'Critique'
    ];
    return $map[$priority] ?? $priority;
}

/**
 * Récupérer le label d'une catégorie
 */
function getCategoryLabel($category) {
    $map = [
        'support_technique' => 'Support Technique',
        'sav' => 'SAV',
        'bureau_etude' => 'Bureau d\'Étude',
        'travaux' => 'Travaux'
    ];
    return $map[$category] ?? $category;
}

/**
 * Récupérer le label d'un type de demande
 */
function getTypeDemandeLabel($type) {
    $map = [
        'etude' => 'Étude',
        'visite' => 'Visite',
        'visite_etude' => 'Visite + Étude',
        'visite_etude_installation' => 'Visite + Étude + Installation',
        'installation' => 'Installation',
        'maintenance' => 'Maintenance',
        'panne' => 'Panne',
        'urgence' => 'Urgence',
        'sav' => 'SAV',
        'travaux' => 'Travaux',
        'support' => 'Support',
        'autre' => 'Autre'
    ];
    return $map[$type] ?? $type;
}

// ============================================
// STYLES DES STATUTS ET PRIORITÉS
// ============================================

/**
 * Récupérer la couleur d'un statut
 */
function getStatusColor($status) {
    $colors = [
        'nouveau' => '#3B82F6',
        'assigne' => '#8B5CF6',
        'en_cours' => '#F59E0B',
        'en_attente' => '#F97316',
        'resolu' => '#10B981',
        'cloture' => '#6B7280'
    ];
    return $colors[$status] ?? '#6B7280';
}

/**
 * Récupérer la classe CSS d'un badge de statut
 */
function getStatusBadgeClass($status) {
    $classes = [
        'nouveau' => 'bg-blue-100 text-blue-800 border-blue-200',
        'assigne' => 'bg-purple-100 text-purple-800 border-purple-200',
        'en_cours' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'en_attente' => 'bg-orange-100 text-orange-800 border-orange-200',
        'resolu' => 'bg-green-100 text-green-800 border-green-200',
        'cloture' => 'bg-gray-100 text-gray-800 border-gray-200'
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

/**
 * Récupérer la classe CSS d'un badge de priorité
 */
function getPriorityBadgeClass($priority) {
    $classes = [
        'basse' => 'bg-gray-100 text-gray-800 border-gray-200',
        'moyenne' => 'bg-blue-100 text-blue-800 border-blue-200',
        'haute' => 'bg-orange-100 text-orange-800 border-orange-200',
        'critique' => 'bg-red-100 text-red-800 border-red-200'
    ];
    return $classes[$priority] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

/**
 * Récupérer la couleur d'une priorité
 */
function getPriorityColor($priority) {
    $colors = [
        'basse' => '#6B7280',
        'moyenne' => '#3B82F6',
        'haute' => '#F59E0B',
        'critique' => '#EF4444'
    ];
    return $colors[$priority] ?? '#6B7280';
}

// ============================================
// UTILITAIRES
// ============================================

/**
 * Tronquer un texte
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Récupérer le nom de l'utilisateur actuel
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Utilisateur';
}

/**
 * Récupérer le rôle de l'utilisateur actuel
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? 'commercial';
}

/**
 * Récupérer l'ID de l'utilisateur actuel
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Récupérer le label du rôle de l'utilisateur actuel
 */
function getCurrentUserRoleLabel() {
    return getRoleLabel(getCurrentUserRole());
}

/**
 * Nettoyer un numéro de téléphone
 */
function cleanPhoneNumber($phone) {
    if (empty($phone)) return '';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    if (strlen($phone) === 9) {
        $phone = '261' . $phone;
    }
    if (strlen($phone) === 10 && substr($phone, 0, 3) === '261') {
        return $phone;
    }
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '261') {
        return $phone;
    }
    return $phone;
}

/**
 * Récupérer le numéro WhatsApp d'un utilisateur
 */
function getWhatsAppNumber($userId) {
    $db = Database::getInstance();
    $user = $db->fetch("SELECT phone FROM users WHERE id = ?", [$userId]);
    if ($user && !empty($user['phone'])) {
        return cleanPhoneNumber($user['phone']);
    }
    return null;
}

// ============================================
// NAVIGATION
// ============================================

/**
 * Récupérer les liens de navigation
 */
function getNavLinks($role) {
    $links = [
        ['page' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-chart-pie', 'url' => 'index.php?page=dashboard'],
        ['page' => 'messages', 'label' => 'Messages', 'icon' => 'fa-comments', 'url' => 'index.php?page=messages'],
        ['page' => 'tickets', 'label' => 'Tickets', 'icon' => 'fa-list', 'url' => 'index.php?page=tickets'],
    ];
    
    // Planning - pour certains rôles uniquement
    $planningRoles = ['admin', 'coordinateur', 'responsable_support_technique', 'responsable_sav', 'responsable_travaux', 'charge_etude_electricite', 'charge_etude_courant_faible', 'charge_etude_climatisation', 'commercial'];
    if (in_array($role, $planningRoles)) {
        $links[] = ['page' => 'planning', 'label' => 'Planning', 'icon' => 'fa-calendar-check', 'url' => 'index.php?page=planning'];
    }
    
    // Utilisateurs - admin uniquement
    if ($role === 'admin') {
        $links[] = ['page' => 'users', 'label' => 'Utilisateurs', 'icon' => 'fa-users-cog', 'url' => 'index.php?page=users'];
    }
    
    // Export - pour certains rôles
    if (canExportData()) {
        $links[] = ['page' => 'export', 'label' => 'Exporter', 'icon' => 'fa-file-export', 'url' => 'index.php?page=export'];
    }
    
    return $links;
}

// ============================================
// NOTIFICATIONS
// ============================================

/**
 * Vérifier si un utilisateur peut recevoir des notifications
 */
function canReceiveNotifications($role) {
    $allowedRoles = [
        'admin',
        'coordinateur',
        'responsable_support_technique',
        'responsable_sav',
        'responsable_travaux',
        'charge_etude_electricite',
        'charge_etude_courant_faible',
        'charge_etude_climatisation',
        'commercial'
    ];
    return in_array($role, $allowedRoles);
}

// ============================================
// WHATSAPP
// ============================================

/**
 * Générer le lien WhatsApp pour un ticket
 */
function getWhatsAppLinkForTicket($ticket) {
    $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
    $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ticketing_plateform';
    
    $message = 
        "📋 Ticket " . ($ticket['ticket_number'] ?? 'N/A') . 
        "\n📝 Titre : " . ($ticket['title'] ?? 'Sans titre') . 
        "\n📊 Statut : " . getStatusLabel($ticket['status'] ?? 'nouveau') . 
        "\n🎯 Priorité : " . getPriorityLabel($ticket['priority'] ?? 'moyenne') . 
        "\n📂 Catégorie : " . getCategoryLabel($ticket['category'] ?? 'general') .
        "\n👤 Créé par : " . ($ticket['created_by_name'] ?? 'Inconnu') . 
        "\n📅 Date : " . formatDate($ticket['created_at'] ?? date('Y-m-d H:i:s')) .
        "\n\n🔗 " . $appUrl . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
    
    return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
}

/**
 * Générer un lien WhatsApp avec un message personnalisé
 */
function getWhatsAppLinkWithMessage($message) {
    $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
    return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
}
?>
<?php
// index.php - Routeur principal - VERSION COMPLÈTE CORRIGÉE
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// index.php - AJOUTER CETTE ROUTE EN HAUT

// ✅ ROUTE POUR api_handler.php (indépendant)
if (strpos($_SERVER['REQUEST_URI'], 'api_handler.php') !== false) {
    // api_handler.php est appelé directement
    // Ne pas faire de routage supplémentaire
}

// ✅ DÉMARRER LA SESSION EN TOUT PREMIER
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/models/Database.php';

// ✅ LOG DE LA SESSION POUR DEBUG
if (isset($_SESSION['user_id'])) {
    error_log("✅ Session active: user_id = " . $_SESSION['user_id'] . ", role = " . ($_SESSION['user_role'] ?? 'unknown'));
} else {
    error_log("⚠️ Aucune session active dans index.php");
}

// Définir la page par défaut
$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Routes publiques (sans authentification)
$publicRoutes = array('login', 'register');

// Vérifier l'authentification
if (!in_array($page, $publicRoutes) && !isLoggedIn()) {
    setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
    redirect('index.php?page=login');
}

// Vérifier les permissions d'accès à la page
if (isLoggedIn() && !in_array($page, $publicRoutes)) {
    if (!canAccessPage($page)) {
        setFlash('danger', 'Vous n\'avez pas accès à cette page.');
        redirect('index.php?page=dashboard');
    }
}

// Routage
switch ($page) {
    
    // ============================================
    // AUTHENTIFICATION
    // ============================================
    case 'login':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case 'count_messages':
        $controller->countMessages();
        break;
    
    case 'register':
        require_once __DIR__ . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->create();
        break;
        
    case 'logout':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
    
    case 'change_password':
        require_once __DIR__ . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->changePassword();
        break;
    
    // ============================================
    // DASHBOARD
    // ============================================
    case 'dashboard':
        require_once __DIR__ . '/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
    
    // ============================================
    // TICKETS
    // ============================================
    case 'tickets':
        require_once __DIR__ . '/controllers/TicketController.php';
        $controller = new TicketController();
        
        switch ($action) {
            case 'create':
                $controller->create();
                break;
            case 'show':
                $controller->show();
                break;
            case 'edit':
                $controller->edit();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'comment':
                $controller->addComment();
                break;
            case 'action':
                $controller->addAction();
                break;
            case 'validate':
                $controller->validateTicket();
                break;
            case 'return':
                $controller->returnToCommercial();
                break;
            case 'process':
                $controller->processTicket();
                break;
            default:
                $controller->index();
                break;
        }
        break;
    
    // ============================================
    // PLANNING
    // ============================================
    case 'planning':
        require_once __DIR__ . '/controllers/PlanningController.php';
        $controller = new PlanningController();
        
        switch ($action) {
            case 'create':
                $controller->create();
                break;
            case 'edit':
                $controller->edit();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'start':
                $controller->start();
                break;
            case 'pause':
                $controller->pause();
                break;
            case 'resume':
                $controller->resume();
                break;
            case 'complete':
                $controller->complete();
                break;
            case 'cancel':
                $controller->cancel();
                break;
            case 'postpone':
                $controller->postpone();
                break;
            case 'add_note':
                $controller->addNote();
                break;
            case 'history':
                $controller->history();
                break;
            default:
                $controller->index();
                break;
        }
        break;
    
    // ============================================
    // GESTION DES UTILISATEURS (ADMIN UNIQUEMENT)
    // ============================================
    case 'users':
        require_once __DIR__ . '/controllers/UserController.php';
        $controller = new UserController();
        
        switch ($action) {
            case 'edit':
                $controller->edit();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'reset_password':
                $controller->resetPassword();
                break;
            case 'create':
                $controller->create();
                break;
            default:
                $controller->index();
                break;
        }
        break;
    
    // ============================================
    // PROFIL
    // ============================================
    case 'profile':
        require_once __DIR__ . '/controllers/ProfileController.php';
        $controller = new ProfileController();
        $controller->index();
        break;
    
    // ============================================
    // HISTORIQUE
    // ============================================
    case 'historique':
        require_once __DIR__ . '/controllers/HistoriqueController.php';
        $controller = new HistoriqueController();
        $controller->index();
        break;
    
    // ============================================
    // MESSAGES (MESSENGER)
    // ============================================
    case 'messages':
        require_once __DIR__ . '/controllers/MessagesController.php';
        $controller = new MessagesController();
        $controller->index();
        break;
    
    // ============================================
    // API - VERSION CORRIGÉE
    // ============================================
    case 'api':
        // ✅ FORCER L'EN-TÊTE JSON POUR TOUTES LES RÉPONSES API
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // ✅ VÉRIFIER LA SESSION
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        require_once __DIR__ . '/controllers/ApiController.php';
        $controller = new ApiController();
        
        // ✅ VÉRIFIER L'AUTHENTIFICATION POUR TOUTES LES ACTIONS API
        $publicApiActions = ['login', 'register'];
        if (!in_array($action, $publicApiActions) && !isApiAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Non authentifié',
                'code' => 'AUTH_REQUIRED'
            ]);
            exit;
        }
        
        switch ($action) {
            // Statistiques
            case 'stats':
                $controller->stats();
                break;
            
            // Notifications
            case 'notifications':
                $controller->notifications();
                break;
            case 'mark_read':
                $controller->markRead();
                break;
            case 'mark_all_read':
                $controller->markAllRead();
                break;
            case 'count_unread':
                $controller->countUnread();
                break;
            case 'delete_all_notifications':
                $controller->deleteAllNotifications();
                break;
            
            // Messages
            case 'send_message':
                $controller->sendMessage();
                break;
            case 'get_messages':
                $controller->getMessages();
                break;
            
            // Dashboard
            case 'dashboard_data':
                $controller->dashboardData();
                break;
            case 'get_tickets':
                $controller->getTickets();
                break;
            
            // ✅ SUPPRESSION TICKET
            case 'delete_ticket':
                $controller->deleteTicket();
                break;
            
            default:
                http_response_code(404);
                echo json_encode(['error' => 'Action API non reconnue']);
                break;
        }
        break;
    

// ============================================
// EXPORT
// ============================================
case 'export':
    require_once __DIR__ . '/controllers/ExportController.php';
    $controller = new ExportController();
    
    // Si un format est spécifié, exporter
    if (isset($_GET['format']) && !empty($_GET['format'])) {
        $controller->export();
    } else {
        // Sinon afficher la page d'exportation
        $controller->index();
    }
    break;
    
    // ============================================
    // PAGE 404
    // ============================================
    default:
        http_response_code(404);
        $errorFile = __DIR__ . '/views/errors/404.php';
        if (file_exists($errorFile)) {
            require_once $errorFile;
        } else {
            // Fallback
            echo '<!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Page non trouvée</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            </head>
            <body class="bg-gray-100 min-h-screen flex items-center justify-center">
                <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="mx-auto h-24 w-24 bg-red-100 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-exclamation-triangle text-red-600 text-5xl"></i>
                    </div>
                    <h2 class="mt-4 text-3xl font-extrabold text-gray-900">404</h2>
                    <p class="text-xl font-semibold text-gray-700 mt-2">Page non trouvée</p>
                    <p class="text-sm text-gray-500 mt-2">
                        La page que vous recherchez n\'existe pas ou a été déplacée.
                    </p>
                    <div class="mt-6 flex flex-col gap-3">
                        <a href="index.php?page=dashboard" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center justify-center">
                            <i class="fas fa-home mr-2"></i>Retour au tableau de bord
                        </a>
                        <a href="javascript:history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>Retour à la page précédente
                        </a>
                    </div>
                </div>
            </body>
            </html>';
        }
        break;
}
?>
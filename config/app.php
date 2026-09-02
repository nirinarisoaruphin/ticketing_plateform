<?php
// config/app.php - Configuration globale - VERSION CORRIGÉE

// ✅ DÉFINIR APP_URL DYNAMIQUEMENT
if (!defined('APP_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
    define('APP_URL', $protocol . '://' . $host . $basePath);
}

// Configuration de l'application
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Plateforme de Ticketing');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}

// Configuration de l'upload
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5242880);
}
if (!defined('ALLOWED_EXTENSIONS')) {
    define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);
}

// ❌ SUPPRIMER CES CONSTANTES - Elles sont déjà dans config/mail.php
// define('MAIL_HOST', 'smtp.gmail.com');
// define('MAIL_PORT', 587);
// define('MAIL_USERNAME', '');
// define('MAIL_PASSWORD', '');
// define('MAIL_FROM', 'noreply@ticketing.com');
// define('MAIL_FROM_NAME', 'Plateforme de Ticketing');

// Configuration des notifications
if (!defined('NOTIFICATION_PREFIX')) {
    define('NOTIFICATION_PREFIX', '[Ticketing] ');
}

// Configuration du logging
if (!defined('LOG_ENABLED')) {
    define('LOG_ENABLED', true);
}
if (!defined('LOG_FILE')) {
    define('LOG_FILE', __DIR__ . '/../logs/app.log');
}

// ✅ NUMÉRO WHATSAPP PAR DÉFAUT
if (!defined('WHATSAPP_NUMBER')) {
    define('WHATSAPP_NUMBER', '261340000001');
}

// ============================================
// ✅ FONCTIONS WHATSAPP (gardées)
// ============================================

if (!function_exists('getWhatsAppLinkForTicket')) {
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
}

if (!function_exists('getWhatsAppLinkWithMessage')) {
    function getWhatsAppLinkWithMessage($message) {
        $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

if (!function_exists('getWhatsAppLinkWithAction')) {
    function getWhatsAppLinkWithAction($ticket, $actionType, $senderName, $content = '') {
        $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
        $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ticketing_plateform';
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketTitle = $ticket['title'] ?? 'Sans titre';
        $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
        $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
        $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
        $ticketUrl = $appUrl . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        $actionLabels = [
            'resolu' => '✅ Ticket résolu',
            'en_cours' => '🔄 Ticket en cours',
            'en_attente' => '⏳ Ticket en attente',
            'signaler_probleme' => '⚠️ Problème signalé',
            'commentaire' => '💬 Nouveau commentaire'
        ];
        $actionLabel = $actionLabels[$actionType] ?? 'Action effectuée';
        
        $message = "📋 *Mise à jour du ticket #{$ticketNumber}*\n\n";
        $message .= "📌 *Action :* {$actionLabel}\n";
        $message .= "👤 *Par :* " . htmlspecialchars($senderName) . "\n";
        $message .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
        $message .= "📊 *Statut :* " . $statusLabel . "\n";
        $message .= "🎯 *Priorité :* " . $priorityLabel . "\n";
        $message .= "📂 *Catégorie :* " . $categoryLabel . "\n";
        $message .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
        
        if (!empty($content)) {
            $message .= "📝 *Message :*\n" . htmlspecialchars($content) . "\n\n";
        }
        
        $message .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
        $message .= "---\n";
        $message .= "Plateforme de Ticketing - SPIDER Madagascar";
        
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

if (!function_exists('getWhatsAppLinkForStatusChange')) {
    function getWhatsAppLinkForStatusChange($ticket, $oldStatus, $newStatus, $senderName) {
        $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
        $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ticketing_plateform';
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketTitle = $ticket['title'] ?? 'Sans titre';
        $oldLabel = getStatusLabel($oldStatus);
        $newLabel = getStatusLabel($newStatus);
        $ticketUrl = $appUrl . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        $message = "📊 *Changement de statut - Ticket #{$ticketNumber}*\n\n";
        $message .= "👤 *Par :* " . htmlspecialchars($senderName) . "\n";
        $message .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
        $message .= "📊 *Ancien statut :* " . $oldLabel . "\n";
        $message .= "📊 *Nouveau statut :* " . $newLabel . "\n";
        $message .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
        $message .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
        $message .= "---\n";
        $message .= "Plateforme de Ticketing - SPIDER Madagascar";
        
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

if (!function_exists('getWhatsAppLinkForAssignment')) {
    function getWhatsAppLinkForAssignment($ticket, $assignedTo, $assignedBy) {
        $db = Database::getInstance();
        $assignedUser = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$assignedTo]);
        $assignedName = $assignedUser ? $assignedUser['full_name'] : 'Un technicien';
        
        $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
        $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ticketing_plateform';
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketTitle = $ticket['title'] ?? 'Sans titre';
        $ticketUrl = $appUrl . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        $message = "👤 *Ticket assigné - #{$ticketNumber}*\n\n";
        $message .= "📌 *Assigné à :* " . htmlspecialchars($assignedName) . "\n";
        $message .= "👤 *Par :* " . htmlspecialchars($assignedBy) . "\n";
        $message .= "📝 *Titre :* " . htmlspecialchars($ticketTitle) . "\n";
        $message .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
        $message .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
        $message .= "---\n";
        $message .= "Plateforme de Ticketing - SPIDER Madagascar";
        
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

if (!function_exists('copyToClipboard')) {
    function copyToClipboard($text) {
        echo '<script>
        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast("✅ Lien copié dans le presse-papier !", "success");
                }).catch(() => fallbackCopy(text));
            } else {
                fallbackCopy(text);
            }
        }
        
        function fallbackCopy(text) {
            const input = document.createElement("input");
            input.value = text;
            input.style.position = "fixed";
            input.style.opacity = "0";
            document.body.appendChild(input);
            input.select();
            try {
                document.execCommand("copy");
                showToast("✅ Lien copié dans le presse-papier !", "success");
            } catch (e) {
                showToast("❌ Impossible de copier", "danger");
            }
            document.body.removeChild(input);
        }
        </script>';
    }
}
?>
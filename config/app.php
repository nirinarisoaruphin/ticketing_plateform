<?php
// config/app.php - Configuration globale

// ✅ DÉFINIR APP_URL DYNAMIQUEMENT
if (!defined('APP_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
    define('APP_URL', $protocol . '://' . $host . $basePath);
}

// Configuration de l'application
define('APP_NAME', 'Plateforme de Ticketing');
define('APP_ENV', 'development'); // development ou production

// Configuration de l'upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Configuration des emails
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM', 'noreply@ticketing.com');
define('MAIL_FROM_NAME', 'Plateforme de Ticketing');

// Configuration des notifications
define('NOTIFICATION_PREFIX', '[Ticketing] ');

// Configuration du logging
define('LOG_ENABLED', true);
define('LOG_FILE', __DIR__ . '/../logs/app.log');

// ✅ DÉFINIR LA CONSTANTE POUR LE NUMÉRO WHATSAPP
if (!defined('WHATSAPP_NUMBER')) {
    define('WHATSAPP_NUMBER', '261340000001'); // ⚠️ REMPLACER PAR VOTRE NUMÉRO
}

// ✅ FONCTION POUR GÉNÉRER LE LIEN WHATSAPP (accessible partout)
if (!function_exists('getWhatsAppLinkForTicket')) {
    function getWhatsAppLinkForTicket($ticket) {
        $phoneNumber = WHATSAPP_NUMBER;
        
        $message = 
            "📋 Ticket " . ($ticket['ticket_number'] ?? 'N/A') . 
            "\n📝 Titre : " . ($ticket['title'] ?? 'Sans titre') . 
            "\n📊 Statut : " . getStatusLabel($ticket['status'] ?? 'nouveau') . 
            "\n🎯 Priorité : " . getPriorityLabel($ticket['priority'] ?? 'moyenne') . 
            "\n📂 Catégorie : " . getCategoryLabel($ticket['category'] ?? 'general') .
            "\n👤 Créé par : " . ($ticket['created_by_name'] ?? 'Inconnu') . 
            "\n📅 Date : " . formatDate($ticket['created_at'] ?? date('Y-m-d H:i:s')) .
            "\n\n🔗 " . APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

// ✅ FONCTION POUR GÉNÉRER LE LIEN WHATSAPP AVEC MESSAGE PERSONNALISÉ
if (!function_exists('getWhatsAppLinkWithMessage')) {
    function getWhatsAppLinkWithMessage($message) {
        $phoneNumber = WHATSAPP_NUMBER;
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}

// ✅ FONCTION POUR COPIER LE LIEN (fallback)
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
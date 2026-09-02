<?php
// includes/WhatsAppImageGenerator.php - Générateur d'images pour WhatsApp

require_once __DIR__ . '/../config/app.php';

class WhatsAppImageGenerator {
    private $width = 800;
    private $height = 600;
    private $image;
    private $colors = [];
    
    public function __construct() {
        $this->image = imagecreatetruecolor($this->width, $this->height);
        
        // Couleurs
        $this->colors = [
            'bg' => imagecolorallocate($this->image, 255, 255, 255),
            'header_bg' => imagecolorallocate($this->image, 26, 35, 126),
            'header_text' => imagecolorallocate($this->image, 255, 255, 255),
            'text_primary' => imagecolorallocate($this->image, 15, 23, 42),
            'text_secondary' => imagecolorallocate($this->image, 71, 85, 105),
            'text_light' => imagecolorallocate($this->image, 148, 163, 184),
            'border' => imagecolorallocate($this->image, 226, 232, 240),
            'success' => imagecolorallocate($this->image, 16, 185, 129),
            'warning' => imagecolorallocate($this->image, 245, 158, 11),
            'danger' => imagecolorallocate($this->image, 239, 68, 68),
            'info' => imagecolorallocate($this->image, 37, 99, 235),
            'badge_bg' => imagecolorallocate($this->image, 219, 234, 254),
            'badge_text' => imagecolorallocate($this->image, 30, 64, 175),
            'card_bg' => imagecolorallocate($this->image, 248, 250, 252),
            'line' => imagecolorallocate($this->image, 200, 200, 200),
            'whatsapp_green' => imagecolorallocate($this->image, 37, 211, 102),
            'shadow' => imagecolorallocate($this->image, 200, 200, 200),
        ];
    }
    
    /**
     * Générer une image pour une action sur un ticket
     */
    public function generateActionImage($ticket, $actionType, $senderName, $content = '') {
        $this->drawBackground();
        $this->drawHeader($ticket);
        $this->drawActionBanner($actionType);
        $this->drawTicketInfo($ticket);
        $this->drawActionMessage($content);
        $this->drawFooter($ticket);
        
        // Sauvegarder l'image
        $filename = 'whatsapp_' . $ticket['ticket_number'] . '_' . date('Ymd_His') . '.png';
        $path = __DIR__ . '/../uploads/whatsapp/' . $filename;
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        
        imagepng($this->image, $path, 9);
        imagedestroy($this->image);
        
        return [
            'path' => $path,
            'filename' => $filename,
            'url' => APP_URL . '/uploads/whatsapp/' . $filename,
            'whatsapp_url' => $this->getWhatsAppShareUrl($ticket, $actionType, $senderName, $content, $filename)
        ];
    }
    
    /**
     * Dessiner le fond
     */
    private function drawBackground() {
        imagefill($this->image, 0, 0, $this->colors['bg']);
        
        // Ligne décorative en haut
        imagefilledrectangle($this->image, 0, 0, $this->width, 8, $this->colors['header_bg']);
    }
    
    /**
     * Dessiner l'en-tête
     */
    private function drawHeader($ticket) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $title = $ticket['title'] ?? 'Sans titre';
        
        // Fond d'en-tête
        imagefilledrectangle($this->image, 0, 8, $this->width, 90, $this->colors['header_bg']);
        
        // Logo / Icône
        $iconX = 30;
        $iconY = 30;
        $iconSize = 40;
        imagefilledellipse($this->image, $iconX + 20, $iconY + 20, $iconSize, $iconSize, $this->colors['whatsapp_green']);
        imagestring($this->image, 5, $iconX + 18, $iconY + 18, '📋', $this->colors['header_text']);
        
        // Texte "Ticket"
        imagestring($this->image, 5, $iconX + $iconSize + 15, 28, 'Ticket #' . $ticketNumber, $this->colors['header_text']);
        
        // Titre
        $truncatedTitle = strlen($title) > 40 ? substr($title, 0, 40) . '...' : $title;
        imagestring($this->image, 4, $iconX + $iconSize + 15, 52, $truncatedTitle, imagecolorallocate($this->image, 200, 200, 255));
        
        // Date
        $date = date('d/m/Y H:i');
        imagestring($this->image, 3, $this->width - 200, 28, '📅 ' . $date, imagecolorallocate($this->image, 150, 150, 220));
    }
    
    /**
     * Dessiner la bannière d'action
     */
    private function drawActionBanner($actionType) {
        $y = 100;
        $height = 70;
        
        $actionLabels = [
            'resolu' => ['label' => '✅ Ticket résolu', 'color' => $this->colors['success']],
            'en_cours' => ['label' => '🔄 Ticket en cours', 'color' => $this->colors['info']],
            'en_attente' => ['label' => '⏳ Ticket en attente', 'color' => $this->colors['warning']],
            'signaler_probleme' => ['label' => '⚠️ Problème signalé', 'color' => $this->colors['danger']],
            'commentaire' => ['label' => '💬 Nouveau commentaire', 'color' => $this->colors['text_secondary']],
            'valide' => ['label' => '✅ Ticket validé', 'color' => $this->colors['success']],
            'refuse' => ['label' => '❌ Ticket refusé', 'color' => $this->colors['danger']]
        ];
        
        $action = $actionLabels[$actionType] ?? ['label' => 'Action effectuée', 'color' => $this->colors['text_secondary']];
        
        // Fond de la bannière
        $x = 30;
        $width = $this->width - 60;
        imagefilledrectangle($this->image, $x, $y, $x + $width, $y + $height, $action['color']);
        
        // Texte de l'action
        imagestring($this->image, 5, $x + 20, $y + 15, $action['label'], $this->colors['bg']);
        
        // Sous-texte
        $senderName = $_SESSION['user_name'] ?? 'Responsable';
        $subText = 'par ' . $senderName . ' · ' . date('d/m/Y H:i');
        imagestring($this->image, 3, $x + 20, $y + 42, $subText, imagecolorallocate($this->image, 240, 240, 255));
    }
    
    /**
     * Dessiner les informations du ticket
     */
    private function drawTicketInfo($ticket) {
        $y = 190;
        $x = 30;
        $width = $this->width - 60;
        
        // Fond de la carte
        imagefilledrectangle($this->image, $x, $y, $x + $width, $y + 180, $this->colors['card_bg']);
        imagerectangle($this->image, $x, $y, $x + $width, $y + 180, $this->colors['border']);
        
        $rowY = $y + 20;
        $col1X = $x + 20;
        $col2X = $x + 350;
        $labelColor = $this->colors['text_secondary'];
        $valueColor = $this->colors['text_primary'];
        
        // Statut
        imagestring($this->image, 3, $col1X, $rowY, '📊 Statut :', $labelColor);
        $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
        $statusColor = $this->getStatusColor($ticket['status'] ?? 'nouveau');
        imagefilledrectangle($this->image, $col1X + 120, $rowY - 2, $col1X + 230, $rowY + 18, $statusColor);
        imagestring($this->image, 3, $col1X + 125, $rowY, $statusLabel, $this->colors['bg']);
        
        // Priorité
        $rowY += 30;
        imagestring($this->image, 3, $col1X, $rowY, '🎯 Priorité :', $labelColor);
        $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
        $priorityColor = $this->getPriorityColor($ticket['priority'] ?? 'moyenne');
        imagefilledrectangle($this->image, $col1X + 120, $rowY - 2, $col1X + 230, $rowY + 18, $priorityColor);
        imagestring($this->image, 3, $col1X + 125, $rowY, $priorityLabel, $this->colors['bg']);
        
        // Catégorie
        $rowY += 30;
        imagestring($this->image, 3, $col1X, $rowY, '📂 Catégorie :', $labelColor);
        $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
        imagestring($this->image, 3, $col1X + 120, $rowY, $categoryLabel, $valueColor);
        
        // Assigné à
        $rowY += 30;
        imagestring($this->image, 3, $col1X, $rowY, '👤 Assigné à :', $labelColor);
        $assignedName = $ticket['assigned_to_name'] ?? 'Non assigné';
        imagestring($this->image, 3, $col1X + 120, $rowY, $assignedName, $valueColor);
        
        // Client
        $rowY += 30;
        imagestring($this->image, 3, $col1X, $rowY, '🏢 Client :', $labelColor);
        $clientName = $ticket['client_name'] ?? 'Non renseigné';
        imagestring($this->image, 3, $col1X + 120, $rowY, $clientName, $valueColor);
        
        // Commentaires
        $rowY += 30;
        imagestring($this->image, 3, $col2X, $y + 20, '💬 Commentaires :', $labelColor);
        $db = Database::getInstance();
        $commentCount = $db->fetch("SELECT COUNT(*) as count FROM comments WHERE ticket_id = ?", [$ticket['id']]);
        $count = $commentCount['count'] ?? 0;
        imagestring($this->image, 3, $col2X + 130, $y + 20, $count . ' message(s)', $valueColor);
        
        // Créé par
        $rowY2 = $y + 50;
        imagestring($this->image, 3, $col2X, $rowY2, '👤 Créé par :', $labelColor);
        $createdByName = $ticket['created_by_name'] ?? 'Inconnu';
        imagestring($this->image, 3, $col2X + 130, $rowY2, $createdByName, $valueColor);
        
        // Date de création
        $rowY2 += 30;
        imagestring($this->image, 3, $col2X, $rowY2, '📅 Créé le :', $labelColor);
        $createdAt = formatDate($ticket['created_at'] ?? date('Y-m-d H:i:s'));
        imagestring($this->image, 3, $col2X + 130, $rowY2, $createdAt, $valueColor);
    }
    
    /**
     * Dessiner le message d'action
     */
    private function drawActionMessage($content) {
        if (empty($content)) return;
        
        $y = 390;
        $x = 30;
        $width = $this->width - 60;
        
        // Fond du message
        imagefilledrectangle($this->image, $x, $y, $x + $width, $y + 70, $this->colors['card_bg']);
        imagerectangle($this->image, $x, $y, $x + $width, $y + 70, $this->colors['border']);
        
        // Label "Message"
        imagestring($this->image, 3, $x + 15, $y + 8, '📝 Message :', $this->colors['text_secondary']);
        
        // Contenu du message (tronqué)
        $truncated = strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
        $lines = explode("\n", wordwrap($truncated, 50, "\n"));
        $lineY = $y + 32;
        foreach ($lines as $line) {
            imagestring($this->image, 3, $x + 15, $lineY, $line, $this->colors['text_primary']);
            $lineY += 18;
            if ($lineY > $y + 65) break;
        }
    }
    
    /**
     * Dessiner le pied de page
     */
    private function drawFooter($ticket) {
        $y = $this->height - 70;
        $x = 30;
        $width = $this->width - 60;
        
        // Ligne de séparation
        imageline($this->image, $x, $y, $x + $width, $y, $this->colors['border']);
        
        // QR Code / Logo WhatsApp
        $y += 10;
        imagefilledrectangle($this->image, $this->width - 80, $y - 5, $this->width - 30, $y + 35, $this->colors['whatsapp_green']);
        imagestring($this->image, 5, $this->width - 70, $y + 2, '📱', $this->colors['bg']);
        
        // Texte du pied
        imagestring($this->image, 3, $x + 20, $y + 5, '📱 Partagez ce ticket sur WhatsApp', $this->colors['whatsapp_green']);
        imagestring($this->image, 2, $x + 20, $y + 25, 'Plateforme de Ticketing - SPIDER Madagascar', $this->colors['text_light']);
        
        // Numéro de ticket en bas à droite
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        imagestring($this->image, 3, $this->width - 200, $y + 30, '#' . $ticketNumber, $this->colors['text_secondary']);
        
        // Ligne de séparation en bas
        imagefilledrectangle($this->image, 0, $this->height - 5, $this->width, $this->height, $this->colors['header_bg']);
    }
    
    /**
     * Obtenir la couleur du statut
     */
    private function getStatusColor($status) {
        $colors = [
            'nouveau' => imagecolorallocate($this->image, 59, 130, 246),
            'assigne' => imagecolorallocate($this->image, 139, 92, 246),
            'en_cours' => imagecolorallocate($this->image, 245, 158, 11),
            'en_attente' => imagecolorallocate($this->image, 249, 115, 22),
            'resolu' => imagecolorallocate($this->image, 16, 185, 129),
            'cloture' => imagecolorallocate($this->image, 107, 114, 128)
        ];
        return $colors[$status] ?? $this->colors['text_secondary'];
    }
    
    /**
     * Obtenir la couleur de la priorité
     */
    private function getPriorityColor($priority) {
        $colors = [
            'basse' => imagecolorallocate($this->image, 107, 114, 128),
            'moyenne' => imagecolorallocate($this->image, 59, 130, 246),
            'haute' => imagecolorallocate($this->image, 245, 158, 11),
            'critique' => imagecolorallocate($this->image, 239, 68, 68)
        ];
        return $colors[$priority] ?? $this->colors['text_secondary'];
    }
    
    /**
     * Générer l'URL de partage WhatsApp avec l'image
     */
    private function getWhatsAppShareUrl($ticket, $actionType, $senderName, $content, $filename) {
        $phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
        $imageUrl = APP_URL . '/uploads/whatsapp/' . $filename;
        
        $actionLabels = [
            'resolu' => '✅ Ticket résolu',
            'en_cours' => '🔄 Ticket en cours',
            'en_attente' => '⏳ Ticket en attente',
            'signaler_probleme' => '⚠️ Problème signalé',
            'commentaire' => '💬 Nouveau commentaire',
            'valide' => '✅ Ticket validé',
            'refuse' => '❌ Ticket refusé'
        ];
        $actionLabel = $actionLabels[$actionType] ?? 'Action effectuée';
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        $message = "📋 *Récapitulatif du ticket #{$ticketNumber}*\n\n";
        $message .= "📌 *Action :* {$actionLabel}\n";
        $message .= "👤 *Par :* " . htmlspecialchars($senderName) . "\n";
        $message .= "📝 *Titre :* " . htmlspecialchars($ticket['title'] ?? 'Sans titre') . "\n";
        $message .= "📊 *Statut :* " . getStatusLabel($ticket['status'] ?? 'nouveau') . "\n";
        $message .= "🎯 *Priorité :* " . getPriorityLabel($ticket['priority'] ?? 'moyenne') . "\n";
        $message .= "📂 *Catégorie :* " . getCategoryLabel($ticket['category'] ?? 'general') . "\n";
        $message .= "👤 *Assigné à :* " . ($ticket['assigned_to_name'] ?? 'Non assigné') . "\n";
        $message .= "📅 *Date :* " . date('d/m/Y à H:i') . "\n\n";
        
        if (!empty($content)) {
            $message .= "📝 *Message :*\n" . htmlspecialchars($content) . "\n\n";
        }
        
        $message .= "🔗 *Lien :* " . $ticketUrl . "\n\n";
        $message .= "📱 *Image récapitulative :* " . $imageUrl . "\n\n";
        $message .= "---\n";
        $message .= "Plateforme de Ticketing - SPIDER Madagascar";
        
        return "https://wa.me/" . $phoneNumber . "?text=" . urlencode($message);
    }
}
?>
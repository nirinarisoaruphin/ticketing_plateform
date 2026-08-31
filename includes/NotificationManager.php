<?php
// includes/NotificationManager.php - VERSION COMPLÈTE CORRIGÉE (UN SEUL EMAIL)

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/mail.php';

class NotificationManager {
    private $mailer;
    private $notificationModel;
    private $logFile;
    private $enabled;
    
    public function __construct() {
        if (!defined('MAIL_ENABLED')) {
            require_once __DIR__ . '/../config/mail.php';
        }
        
        $this->enabled = defined('MAIL_ENABLED') ? MAIL_ENABLED : false;
        $this->logFile = __DIR__ . '/../logs/emails.log';
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $this->mailer = new Mailer();
        $this->notificationModel = new Notification();
    }
    
    /**
     * ✅ NOTIFIER UNE ACTION - UN SEUL EMAIL
     */
    public function notifyAction($ticket, $actionType, $content, $senderName) {
        if (!$ticket || !isset($ticket['id'])) {
            $this->log("❌ notifyAction: Ticket invalide");
            return false;
        }
        
        $db = Database::getInstance();
        
        // Récupérer le ticket complet
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        if ($fullTicket) {
            $ticket = array_merge($ticket, $fullTicket);
        }
        
        // ✅ 1. NOTIFICATION IN-APP
        $this->createInAppNotification($ticket, $actionType, $content, $senderName);
        
        // ✅ 2. UN SEUL EMAIL
        $this->sendSingleEmail($ticket, $actionType, $content, $senderName);
        
        return true;
    }
    
    /**
     * ✅ NOTIFIER UN COMMENTAIRE
     */
    public function notifyCommentAdded($ticket, $comment, $author) {
        if (!$ticket || !isset($ticket['id'])) {
            $this->log("❌ notifyCommentAdded: Ticket invalide");
            return false;
        }
        
        $db = Database::getInstance();
        
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        if ($fullTicket) {
            $ticket = array_merge($ticket, $fullTicket);
        }
        
        // ✅ 1. NOTIFICATION IN-APP
        $this->createCommentNotification($ticket, $comment, $author);
        
        // ✅ 2. UN SEUL EMAIL
        $this->sendCommentEmail($ticket, $comment, $author);
        
        return true;
    }
    
    /**
     * ✅ NOTIFIER UNE VALIDATION
     */
    public function notifyValidation($ticket, $validatedBy, $status) {
        if (!$ticket || !isset($ticket['id'])) {
            $this->log("❌ notifyValidation: Ticket invalide");
            return false;
        }
        
        $db = Database::getInstance();
        
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        if ($fullTicket) {
            $ticket = array_merge($ticket, $fullTicket);
        }
        
        // ✅ 1. NOTIFICATION IN-APP
        $this->createValidationNotification($ticket, $validatedBy, $status);
        
        // ✅ 2. UN SEUL EMAIL
        $this->sendValidationEmail($ticket, $validatedBy, $status);
        
        return true;
    }
    
    /**
     * ✅ NOTIFIER UN RETOUR
     */
    public function notifyReturn($ticket, $message, $senderName) {
        if (!$ticket || !isset($ticket['id'])) {
            $this->log("❌ notifyReturn: Ticket invalide");
            return false;
        }
        
        $db = Database::getInstance();
        
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        if ($fullTicket) {
            $ticket = array_merge($ticket, $fullTicket);
        }
        
        // ✅ 1. NOTIFICATION IN-APP
        $this->createReturnNotification($ticket, $message, $senderName);
        
        // ✅ 2. UN SEUL EMAIL
        $this->sendReturnEmail($ticket, $message, $senderName);
        
        return true;
    }
    
    /**
     * ✅ CRÉER LA NOTIFICATION IN-APP POUR ACTION
     */
    private function createInAppNotification($ticket, $actionType, $content, $senderName) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $link = "index.php?page=tickets&action=show&id=" . $ticket['id'];
        $db = Database::getInstance();
        
        $actionLabels = [
            'resolu' => "✅ Ticket $ticketNumber résolu par $senderName",
            'en_cours' => "🔄 Ticket $ticketNumber en cours par $senderName",
            'en_attente' => "⏳ Ticket $ticketNumber en attente par $senderName",
            'signaler_probleme' => "⚠️ Problème signalé sur $ticketNumber par $senderName",
            'commentaire' => "💬 Nouveau commentaire sur $ticketNumber par $senderName"
        ];
        
        $message = $actionLabels[$actionType] ?? "Action sur le ticket $ticketNumber par $senderName";
        
        // Notifier le créateur
        if (!empty($ticket['created_by']) && $ticket['created_by'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['created_by'], $message, $link);
        }
        
        // Notifier le responsable assigné
        if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['assigned_to'], $message, $link);
        }
        
        // Notifier tous les responsables
        $responsables = $db->fetchAll(
            "SELECT id FROM users WHERE role IN ('responsable_support_technique', 'responsable_sav', 'responsable_travaux') AND id != ?",
            [$_SESSION['user_id'] ?? 0]
        );
        foreach ($responsables as $resp) {
            $this->notificationModel->createNotification($resp['id'], $message, $link);
        }
    }
    
    /**
     * ✅ CRÉER LA NOTIFICATION IN-APP POUR COMMENTAIRE
     */
    private function createCommentNotification($ticket, $comment, $author) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $link = "index.php?page=tickets&action=show&id=" . $ticket['id'];
        $db = Database::getInstance();
        
        $shortComment = substr($comment, 0, 100) . (strlen($comment) > 100 ? '...' : '');
        $message = "💬 Nouveau commentaire sur le ticket $ticketNumber de $author : $shortComment";
        
        if (!empty($ticket['created_by']) && $ticket['created_by'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['created_by'], $message, $link);
        }
        
        if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['assigned_to'], $message, $link);
        }
        
        $responsables = $db->fetchAll(
            "SELECT id FROM users WHERE role IN ('responsable_support_technique', 'responsable_sav', 'responsable_travaux') AND id != ?",
            [$_SESSION['user_id'] ?? 0]
        );
        foreach ($responsables as $resp) {
            $this->notificationModel->createNotification($resp['id'], $message, $link);
        }
    }
    
    /**
     * ✅ CRÉER LA NOTIFICATION IN-APP POUR VALIDATION
     */
    private function createValidationNotification($ticket, $validatedBy, $status) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $link = "index.php?page=tickets&action=show&id=" . $ticket['id'];
        $statusText = $status === 'valide' ? 'validé' : 'refusé';
        $message = "📋 Ticket $ticketNumber $statusText par $validatedBy";
        
        if (!empty($ticket['created_by']) && $ticket['created_by'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['created_by'], $message, $link);
        }
        
        if (!empty($ticket['assigned_to']) && $ticket['assigned_to'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['assigned_to'], $message, $link);
        }
    }
    
    /**
     * ✅ CRÉER LA NOTIFICATION IN-APP POUR RETOUR
     */
    private function createReturnNotification($ticket, $message, $senderName) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $link = "index.php?page=tickets&action=show&id=" . $ticket['id'];
        $notifMessage = "📧 Retour de $senderName sur le ticket $ticketNumber";
        
        if (!empty($ticket['created_by']) && $ticket['created_by'] != ($_SESSION['user_id'] ?? 0)) {
            $this->notificationModel->createNotification($ticket['created_by'], $notifMessage, $link);
        }
    }
    
    /**
     * ✅ ENVOYER UN SEUL EMAIL POUR ACTION
     */
    private function sendSingleEmail($ticket, $actionType, $content, $senderName) {
        if (!$this->enabled) {
            $this->log("⚠️ MAIL_DISABLED");
            return;
        }
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire pour l'action");
            return;
        }
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $actionLabels = [
            'resolu' => '✅ Ticket résolu',
            'en_cours' => '🔄 Ticket en cours',
            'en_attente' => '⏳ Ticket en attente',
            'signaler_probleme' => '⚠️ Problème signalé',
            'commentaire' => '💬 Nouveau commentaire'
        ];
        $actionLabel = $actionLabels[$actionType] ?? 'Action effectuée';
        $subject = "$actionLabel - Ticket $ticketNumber";
        
        $message = $this->buildEmailTemplate($ticket, $actionType, $content, $senderName);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email d'action envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails d'action envoyés");
    }
    
    /**
     * ✅ ENVOYER UN SEUL EMAIL POUR COMMENTAIRE
     */
    private function sendCommentEmail($ticket, $comment, $author) {
        if (!$this->enabled) {
            $this->log("⚠️ MAIL_DISABLED");
            return;
        }
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire pour le commentaire");
            return;
        }
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $subject = "💬 Nouveau commentaire - Ticket $ticketNumber";
        $message = $this->buildCommentEmailTemplate($ticket, $comment, $author);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email commentaire envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails de commentaire envoyés");
    }
    
    /**
     * ✅ ENVOYER UN SEUL EMAIL POUR VALIDATION
     */
    private function sendValidationEmail($ticket, $validatedBy, $status) {
        if (!$this->enabled) {
            $this->log("⚠️ MAIL_DISABLED");
            return;
        }
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire pour la validation");
            return;
        }
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $statusText = $status === 'valide' ? 'validé' : 'refusé';
        $subject = "📋 Ticket $ticketNumber - $statusText";
        $message = $this->buildValidationEmailTemplate($ticket, $validatedBy, $status);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email validation envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails de validation envoyés");
    }
    
    /**
     * ✅ ENVOYER UN SEUL EMAIL POUR RETOUR
     */
    private function sendReturnEmail($ticket, $message, $senderName) {
        if (!$this->enabled) {
            $this->log("⚠️ MAIL_DISABLED");
            return;
        }
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire pour le retour");
            return;
        }
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $subject = "📧 Retour sur le ticket $ticketNumber";
        $message = $this->buildReturnEmailTemplate($ticket, $message, $senderName);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email retour envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails de retour envoyés");
    }
    
    /**
     * ✅ RÉCUPÉRER LES DESTINATAIRES
     */
    private function getEmailRecipients($ticket) {
        $db = Database::getInstance();
        $recipients = [];
        $emails = [];
        
        // Admin
        $admins = $db->fetchAll("SELECT id, email, full_name FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            if (!empty($admin['email']) && !in_array($admin['email'], $emails)) {
                $recipients[$admin['email']] = $admin['full_name'] . ' (Admin)';
                $emails[] = $admin['email'];
            }
        }
        
        // Coordinateur
        $coordinateurs = $db->fetchAll("SELECT id, email, full_name FROM users WHERE role = 'coordinateur'");
        foreach ($coordinateurs as $coord) {
            if (!empty($coord['email']) && !in_array($coord['email'], $emails)) {
                $recipients[$coord['email']] = $coord['full_name'] . ' (Coordinateur)';
                $emails[] = $coord['email'];
            }
        }
        
        // Responsable selon catégorie
        $categoryMap = [
            'support_technique' => 'responsable_support_technique',
            'bureau_etude' => 'responsable_support_technique',
            'sav' => 'responsable_sav',
            'travaux' => 'responsable_travaux'
        ];
        
        $responsibleRole = $categoryMap[$ticket['category'] ?? ''] ?? null;
        if ($responsibleRole) {
            $responsables = $db->fetchAll("SELECT id, email, full_name FROM users WHERE role = ?", [$responsibleRole]);
            foreach ($responsables as $resp) {
                if (!empty($resp['email']) && !in_array($resp['email'], $emails)) {
                    $recipients[$resp['email']] = $resp['full_name'] . ' (Responsable)';
                    $emails[] = $resp['email'];
                }
            }
        }
        
        // Créateur
        if (!empty($ticket['created_by'])) {
            $creator = $db->fetch("SELECT id, email, full_name FROM users WHERE id = ?", [$ticket['created_by']]);
            if ($creator && !empty($creator['email']) && !in_array($creator['email'], $emails)) {
                $recipients[$creator['email']] = $creator['full_name'] . ' (Créateur)';
                $emails[] = $creator['email'];
            }
        }
        
        // Assigné
        if (!empty($ticket['assigned_to'])) {
            $assigned = $db->fetch("SELECT id, email, full_name FROM users WHERE id = ?", [$ticket['assigned_to']]);
            if ($assigned && !empty($assigned['email']) && !in_array($assigned['email'], $emails)) {
                $recipients[$assigned['email']] = $assigned['full_name'] . ' (Assigné)';
                $emails[] = $assigned['email'];
            }
        }
        
        // Fallback pour les tests
        if (empty($recipients) || $ticket['id'] == 999) {
            $recipients['ralijaonanirinarisoa@gmail.com'] = 'Testeur';
        }
        
        return $recipients;
    }

/**
 * ✅ TEMPLATE EMAIL POUR ACTION - BOUTON AMÉLIORÉ
 */
private function buildEmailTemplate($ticket, $actionType, $content, $senderName) {
    $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
    $ticketTitle = $ticket['title'] ?? 'Sans titre';
    $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
    $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
    $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
    $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
    
    $actionLabels = [
        'resolu' => ['label' => '✅ Ticket résolu', 'color' => '#10b981'],
        'en_cours' => ['label' => '🔄 Ticket en cours', 'color' => '#2563eb'],
        'en_attente' => ['label' => '⏳ Ticket en attente', 'color' => '#f59e0b'],
        'signaler_probleme' => ['label' => '⚠️ Problème signalé', 'color' => '#ef4444'],
        'commentaire' => ['label' => '💬 Nouveau commentaire', 'color' => '#6b7280']
    ];
    
    $action = $actionLabels[$actionType] ?? ['label' => 'Action effectuée', 'color' => '#6b7280'];
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $action['label'] . '</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #1e293b; }
            .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden; }
            .header { background: linear-gradient(135deg, #0f172a 0%, #1a237e 100%); color: white; padding: 28px 36px 24px; text-align: center; }
            .header .logo { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
            .header .badge { display: inline-block; margin-top: 6px; padding: 3px 16px; background: rgba(255,255,255,0.12); border-radius: 20px; font-size: 11px; font-weight: 500; }
            .content { padding: 28px 36px 20px; }
            .banner { background: ' . $action['color'] . '; color: white; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
            .banner .text { font-size: 16px; font-weight: 600; }
            .banner .sub { font-size: 12px; opacity: 0.85; }
            .info { background: #f8fafc; border-radius: 12px; padding: 16px 20px; border-left: 4px solid ' . $action['color'] . '; margin-bottom: 18px; }
            .info table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .info td { padding: 4px 0; }
            .info .lbl { color: #64748b; width: 90px; }
            .info .val { font-weight: 600; color: #0f172a; }
            
            /* ✅ BOUTON AMÉLIORÉ - PLUS VISIBLE */
            .btn-container { 
                text-align: center; 
                margin: 25px 0 10px; 
                padding: 10px 0;
            }
            .btn-ticket { 
                display: inline-block; 
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff !important; 
                padding: 14px 40px; 
                text-decoration: none; 
                border-radius: 10px; 
                font-weight: 700; 
                font-size: 16px;
                letter-spacing: 0.5px;
                box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            .btn-ticket:hover { 
                background: linear-gradient(135deg, #1d4ed8 0%, #1a3a8a 100%);
                box-shadow: 0 6px 25px rgba(37, 99, 235, 0.5);
                transform: translateY(-2px);
            }
            .btn-ticket .icon { 
                margin-right: 10px; 
                font-size: 18px;
            }
            .btn-ticket .arrow {
                display: inline-block;
                transition: transform 0.3s ease;
                margin-left: 8px;
            }
            .btn-ticket:hover .arrow {
                transform: translateX(5px);
            }
            
            .footer { background: #f8fafc; padding: 14px 36px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; }
            .footer .meta { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-top: 2px; }
            @media (max-width: 600px) {
                .header { padding: 20px; }
                .content { padding: 20px; }
                .footer { padding: 10px 20px; }
                .banner { flex-wrap: wrap; }
                .info td { display: block; padding: 2px 0; }
                .info .lbl { width: auto; }
                .btn-ticket { padding: 12px 28px; font-size: 14px; width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Plateforme de Ticketing</div>
                <div class="badge">🔔 Notification</div>
            </div>
            <div class="content">
                <div class="banner">
                    <div>
                        <div class="text">' . $action['label'] . '</div>
                        <div class="sub">par ' . htmlspecialchars($senderName) . ' · ' . date('d/m/Y H:i') . '</div>
                    </div>
                </div>
                <div class="info">
                    <table>
                        <tr><td class="lbl">🔹 Numéro</td><td class="val">' . $ticketNumber . '</td></tr>
                        <tr><td class="lbl">📝 Titre</td><td class="val">' . htmlspecialchars($ticketTitle) . '</td></tr>
                        <tr><td class="lbl">📊 Statut</td><td class="val"><span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $statusLabel . '</span></td></tr>
                        <tr><td class="lbl">🎯 Priorité</td><td class="val"><span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                        <tr><td class="lbl">📂 Catégorie</td><td class="val">' . $categoryLabel . '</td></tr>
                    </table>
                </div>
                ' . (!empty($content) ? '
                <div style="background:#f1f5f9;border-radius:8px;padding:12px 16px;margin-bottom:18px;border-left:4px solid #94a3b8;">
                    <p style="font-size:10px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;margin:0 0 2px 0;">📝 Message</p>
                    <p style="margin:0;color:#1e293b;font-size:13px;">' . nl2br(htmlspecialchars($content)) . '</p>
                </div>
                ' : '') . '
                
                <!-- ✅ BOUTON AMÉLIORÉ -->
                <div class="btn-container">
                    <a href="' . $ticketUrl . '" class="btn-ticket">
                        <span class="icon">🔍</span>
                        Voir le ticket
                        <span class="arrow">→</span>
                    </a>
                </div>
                
                <!-- ✅ LIEN TEXTE EN PETIT (fallback) -->
                <div style="text-align:center;font-size:11px;color:#94a3b8;margin-top:5px;">
                    <a href="' . $ticketUrl . '" style="color:#94a3b8;text-decoration:underline;">' . $ticketUrl . '</a>
                </div>
            </div>
            <div class="footer">
                <p style="margin:0;">© ' . date('Y') . ' Plateforme de Ticketing - Spider Madagascar</p>
                <div class="meta">
                    <span>📧 Email</span>
                    <span>🔒 Ne pas répondre</span>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * ✅ TEMPLATE EMAIL POUR COMMENTAIRE - BOUTON AMÉLIORÉ
 */
private function buildCommentEmailTemplate($ticket, $comment, $author) {
    $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
    $ticketTitle = $ticket['title'] ?? 'Sans titre';
    $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
    $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
    $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
    $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>💬 Nouveau commentaire</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #1e293b; }
            .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden; }
            .header { background: linear-gradient(135deg, #0f172a 0%, #1a237e 100%); color: white; padding: 28px 36px 24px; text-align: center; }
            .header .logo { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
            .header .badge { display: inline-block; margin-top: 6px; padding: 3px 16px; background: rgba(255,255,255,0.12); border-radius: 20px; font-size: 11px; font-weight: 500; }
            .content { padding: 28px 36px 20px; }
            .banner { background: #6b7280; color: white; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
            .banner .text { font-size: 16px; font-weight: 600; }
            .banner .sub { font-size: 12px; opacity: 0.85; }
            .info { background: #f8fafc; border-radius: 12px; padding: 16px 20px; border-left: 4px solid #6b7280; margin-bottom: 18px; }
            .info table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .info td { padding: 4px 0; }
            .info .lbl { color: #64748b; width: 90px; }
            .info .val { font-weight: 600; color: #0f172a; }
            .comment-box { background: #f1f5f9; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; border-left: 4px solid #94a3b8; }
            .comment-box .label { font-size: 10px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin: 0 0 2px 0; }
            .comment-box .text { margin: 0; color: #1e293b; font-size: 13px; }
            
            /* ✅ BOUTON AMÉLIORÉ */
            .btn-container { text-align: center; margin: 25px 0 10px; padding: 10px 0; }
            .btn-ticket { 
                display: inline-block; 
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                color: #ffffff !important; 
                padding: 14px 40px; 
                text-decoration: none; 
                border-radius: 10px; 
                font-weight: 700; 
                font-size: 16px;
                letter-spacing: 0.5px;
                box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            .btn-ticket:hover { 
                background: linear-gradient(135deg, #1d4ed8 0%, #1a3a8a 100%);
                box-shadow: 0 6px 25px rgba(37, 99, 235, 0.5);
                transform: translateY(-2px);
            }
            .btn-ticket .icon { margin-right: 10px; font-size: 18px; }
            .btn-ticket .arrow { display: inline-block; transition: transform 0.3s ease; margin-left: 8px; }
            .btn-ticket:hover .arrow { transform: translateX(5px); }
            
            .footer { background: #f8fafc; padding: 14px 36px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; }
            .footer .meta { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-top: 2px; }
            @media (max-width: 600px) {
                .header { padding: 20px; }
                .content { padding: 20px; }
                .footer { padding: 10px 20px; }
                .info td { display: block; padding: 2px 0; }
                .info .lbl { width: auto; }
                .btn-ticket { padding: 12px 28px; font-size: 14px; width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Plateforme de Ticketing</div>
                <div class="badge">🔔 Notification</div>
            </div>
            <div class="content">
                <div class="banner">
                    <div>
                        <div class="text">💬 Nouveau commentaire</div>
                        <div class="sub">par ' . htmlspecialchars($author) . ' · ' . date('d/m/Y H:i') . '</div>
                    </div>
                </div>
                <div class="info">
                    <table>
                        <tr><td class="lbl">🔹 Numéro</td><td class="val">' . $ticketNumber . '</td></tr>
                        <tr><td class="lbl">📝 Titre</td><td class="val">' . htmlspecialchars($ticketTitle) . '</td></tr>
                        <tr><td class="lbl">📊 Statut</td><td class="val"><span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $statusLabel . '</span></td></tr>
                        <tr><td class="lbl">🎯 Priorité</td><td class="val"><span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                        <tr><td class="lbl">📂 Catégorie</td><td class="val">' . $categoryLabel . '</td></tr>
                    </table>
                </div>
                <div class="comment-box">
                    <p class="label">📝 Message</p>
                    <p class="text">' . nl2br(htmlspecialchars($comment)) . '</p>
                </div>
                
                <!-- ✅ BOUTON AMÉLIORÉ -->
                <div class="btn-container">
                    <a href="' . $ticketUrl . '" class="btn-ticket">
                        <span class="icon">🔍</span>
                        Voir le ticket
                        <span class="arrow">→</span>
                    </a>
                </div>
                
                <div style="text-align:center;font-size:11px;color:#94a3b8;margin-top:5px;">
                    <a href="' . $ticketUrl . '" style="color:#94a3b8;text-decoration:underline;">' . $ticketUrl . '</a>
                </div>
            </div>
            <div class="footer">
                <p style="margin:0;">© ' . date('Y') . ' Plateforme de Ticketing - Spider Madagascar</p>
                <div class="meta">
                    <span>📧 Email automatique</span>
                    <span>🔒 Ne pas répondre</span>
                </div>
            </div>
        </div>
    </body>
    </html>';
}
    
    /**
     * ✅ TEMPLATE EMAIL POUR RETOUR
     */
    private function buildReturnEmailTemplate($ticket, $message, $senderName) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketTitle = $ticket['title'] ?? 'Sans titre';
        $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
        $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
        $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
        $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>📧 Retour sur le ticket</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #1e293b; }
                .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden; }
                .header { background: linear-gradient(135deg, #0f172a 0%, #1a237e 100%); color: white; padding: 28px 36px 24px; text-align: center; }
                .header .logo { font-size: 18px; font-weight: 700; letter-spacing: -0.5px; }
                .header .badge { display: inline-block; margin-top: 6px; padding: 3px 16px; background: rgba(255,255,255,0.12); border-radius: 20px; font-size: 11px; font-weight: 500; }
                .content { padding: 28px 36px 20px; }
                .banner { background: #8b5cf6; color: white; padding: 14px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
                .banner .text { font-size: 16px; font-weight: 600; }
                .banner .sub { font-size: 12px; opacity: 0.85; }
                .info { background: #f8fafc; border-radius: 12px; padding: 16px 20px; border-left: 4px solid #8b5cf6; margin-bottom: 18px; }
                .info table { width: 100%; border-collapse: collapse; font-size: 13px; }
                .info td { padding: 4px 0; }
                .info .lbl { color: #64748b; width: 90px; }
                .info .val { font-weight: 600; color: #0f172a; }
                .message-box { background: #f1f5f9; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; border-left: 4px solid #94a3b8; }
                .message-box .label { font-size: 10px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; margin: 0 0 2px 0; }
                .message-box .text { margin: 0; color: #1e293b; font-size: 13px; }
                .btn { display: inline-block; background: #2563eb; color: white; padding: 10px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 13px; }
                .footer { background: #f8fafc; padding: 14px 36px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; }
                .footer .meta { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-top: 2px; }
                @media (max-width: 600px) {
                    .header { padding: 20px; }
                    .content { padding: 20px; }
                    .footer { padding: 10px 20px; }
                    .info td { display: block; padding: 2px 0; }
                    .info .lbl { width: auto; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">Plateforme de Ticketing</div>
                    <div class="badge">🔔 Notification</div>
                </div>
                <div class="content">
                    <div class="banner">
                        <div>
                            <div class="text">📧 Retour sur le ticket</div>
                            <div class="sub">par ' . htmlspecialchars($senderName) . ' · ' . date('d/m/Y H:i') . '</div>
                        </div>
                    </div>
                    <div class="info">
                        <table>
                            <tr><td class="lbl">🔹 Numéro</td><td class="val">' . $ticketNumber . '</td></tr>
                            <tr><td class="lbl">📝 Titre</td><td class="val">' . htmlspecialchars($ticketTitle) . '</td></tr>
                            <tr><td class="lbl">📊 Statut</td><td class="val"><span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $statusLabel . '</span></td></tr>
                            <tr><td class="lbl">🎯 Priorité</td><td class="val"><span style="background:#fef3c7;color:#92400e;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                            <tr><td class="lbl">📂 Catégorie</td><td class="val">' . $categoryLabel . '</td></tr>
                        </table>
                    </div>
                    <div class="message-box">
                        <p class="label">📝 Message</p>
                        <p class="text">' . nl2br(htmlspecialchars($message)) . '</p>
                    </div>
                    <div style="text-align:center;margin:20px 0 10px;">
                        <a href="' . $ticketUrl . '" class="btn">🔍 Voir le ticket</a>
                    </div>
                </div>
                <div class="footer">
                    <p style="margin:0;">© ' . date('Y') . ' Plateforme de Ticketing - Spider Madagascar</p>
                    <div class="meta">
                        <span>📧 Email</span>
                        <span>🔒 Ne pas répondre</span>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * ✅ LOG
     */
    private function log($message) {
        $log = date('Y-m-d H:i:s') . " | " . $message . "\n";
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }
}
?>
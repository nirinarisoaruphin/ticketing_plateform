<?php
// includes/EmailManager.php - VERSION CORRIGÉE (UN SEUL EMAIL)

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/Database.php';

class EmailManager {
    private $mailer;
    private $enabled;
    private $logFile;
    
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
        
        if (!$this->enabled) {
            $this->log("⚠️ MAIL_DISABLED");
            return;
        }
        
        $this->mailer = new Mailer();
        $this->log("📧 EmailManager initialisé");
    }
    
    public function send($to, $subject, $message, $toName = '') {
        if (!$this->enabled) {
            $this->log("⚠️ Envoi bloqué - MAIL_DISABLED: $to");
            return false;
        }
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->log("❌ Email invalide: $to");
            return false;
        }
        
        $result = $this->mailer->send($to, $subject, $message, $toName);
        
        if ($result) {
            $this->log("✅ Email envoyé à : $to");
        } else {
            $this->log("❌ Erreur envoi à $to: " . $this->mailer->getLastError());
        }
        
        return $result;
    }
    
    /**
     * ✅ NOTIFICATION DE CRÉATION - UN SEUL EMAIL
     */
    public function notifyTicketCreated($ticket) {
        if (!$ticket || !isset($ticket['id'])) {
            $this->log("❌ Ticket invalide ou NULL");
            return false;
        }
        
        $this->log("📧 NOTIFICATION CRÉATION: Ticket " . ($ticket['ticket_number'] ?? 'N/A'));
        
        // Ticket de test
        if ($ticket['id'] == 999) {
            $this->log("📧 Ticket de test détecté - Envoi direct");
            return $this->sendTestEmail($ticket);
        }
        
        $db = Database::getInstance();
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        
        if (!$fullTicket) {
            $this->log("⚠️ Ticket " . $ticket['id'] . " non trouvé en base");
        }
        
        if ($fullTicket) {
            $ticket = array_merge($ticket, $fullTicket);
        }
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire");
            $recipients['ralijaonanirinarisoa@gmail.com'] = 'Testeur (Fallback)';
        }
        
        $this->log("📧 Total destinataires: " . count($recipients));
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $subject = "📌 Nouveau ticket " . $ticketNumber . " - " . ($ticket['title'] ?? 'Sans titre');
        $message = $this->buildEmailTemplate($ticket, 'created');
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails envoyés sur " . count($recipients));
        return $count;
    }
    
    /**
     * ✅ NOTIFICATION DE CHANGEMENT DE STATUT - UN SEUL EMAIL
     */
    public function notifyStatusChange($ticket, $oldStatus, $newStatus) {
        if (!$this->enabled || !$ticket || !isset($ticket['id'])) {
            return false;
        }
        
        $db = Database::getInstance();
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        
        if (!$fullTicket) {
            $this->log("⚠️ Ticket " . $ticket['id'] . " non trouvé");
            return false;
        }
        
        $ticket = array_merge($ticket, $fullTicket);
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $oldStatusLabel = getStatusLabel($oldStatus);
        $newStatusLabel = getStatusLabel($newStatus);
        
        $this->log("📧 CHANGEMENT STATUT: $ticketNumber ($oldStatusLabel → $newStatusLabel)");
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $this->log("⚠️ Aucun destinataire");
            $recipients['ralijaonanirinarisoa@gmail.com'] = 'Testeur (Fallback)';
        }
        
        $subject = "📊 Ticket " . $ticketNumber . " - Changement de statut";
        $message = $this->buildEmailTemplate($ticket, 'status_change', [
            'old_status' => $oldStatusLabel,
            'new_status' => $newStatusLabel
        ]);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email envoyé à : $email");
            } else {
                $this->log("❌ Erreur envoi à $email: " . $this->mailer->getLastError());
            }
        }
        
        $this->log("📧 $count emails envoyés sur " . count($recipients));
        return $count;
    }
    
    /**
     * ✅ NOTIFICATION D'ACTION - UN SEUL EMAIL
     */
    public function notifyTicketAction($ticket, $actionType, $content, $senderName) {
        if (!$this->enabled || !$ticket || !isset($ticket['id'])) {
            return false;
        }
        
        $db = Database::getInstance();
        $fullTicket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticket['id']]);
        
        if (!$fullTicket) {
            $this->log("⚠️ Ticket " . $ticket['id'] . " non trouvé");
            return false;
        }
        
        $ticket = array_merge($ticket, $fullTicket);
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        
        $this->log("📧 ACTION: $ticketNumber - $actionType");
        
        $recipients = $this->getEmailRecipients($ticket);
        
        if (empty($recipients)) {
            $recipients['ralijaonanirinarisoa@gmail.com'] = 'Testeur (Fallback)';
        }
        
        $actionLabels = [
            'signaler_probleme' => '⚠️ Problème signalé',
            'notifier_client' => '📢 Client notifié',
            'demander_info' => '❓ Demande d\'information',
            'escalader' => '⬆️ Ticket escaladé',
            'resolu' => '✅ Ticket résolu',
            'en_cours' => '🔄 Ticket en cours',
            'en_attente' => '⏳ Ticket en attente',
            'commentaire' => '💬 Nouveau commentaire'
        ];
        $actionLabel = $actionLabels[$actionType] ?? 'Action effectuée';
        
        $subject = "📌 " . $actionLabel . " - Ticket " . $ticketNumber;
        $message = $this->buildActionEmailTemplate($ticket, $actionType, $content, $senderName);
        
        $count = 0;
        foreach ($recipients as $email => $name) {
            if ($this->mailer->send($email, $subject, $message, $name)) {
                $count++;
                $this->log("📧 Email envoyé à : $email");
            }
        }
        
        $this->log("📧 $count emails envoyés sur " . count($recipients));
        return $count;
    }
    
    private function sendTestEmail($ticket) {
        $to = 'ralijaonanirinarisoa@gmail.com';
        $subject = '🧪 Test Notification - ' . date('d/m/Y H:i:s');
        $message = $this->buildEmailTemplate($ticket, 'created');
        return $this->mailer->send($to, $subject, $message);
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
     * ✅ TEMPLATE EMAIL MODERNE ET PROFESSIONNEL - UN SEUL EMAIL
     */
    private function buildEmailTemplate($ticket, $type, $extra = []) {
        $db = Database::getInstance();
        
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $title = $ticket['title'] ?? 'Sans titre';
        $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
        $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
        $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
        $description = $ticket['description'] ?? '';
        $createdAt = formatDate($ticket['created_at'] ?? date('Y-m-d H:i:s'));
        $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . ($ticket['id'] ?? 0);
        
        // ✅ DÉFINIR $headerIcon ICI (CORRECTION)
        $headerIcon = 'fa-ticket-alt'; // Valeur par défaut
        
        // Nom du créateur
        $createdByName = 'Inconnu';
        if (!empty($ticket['created_by'])) {
            $creator = $db->fetch("SELECT full_name FROM users WHERE id = ?", [$ticket['created_by']]);
            if ($creator) {
                $createdByName = $creator['full_name'];
            }
        }
        
        // Déterminer le titre et le contenu selon le type
        switch ($type) {
            case 'created':
                $headerTitle = '📌 Nouveau ticket créé';
                $headerColor = '#2563eb';
                $headerIcon = 'fa-ticket-alt'; // ✅ DÉFINI
                $content = '
                    <p style="font-size:16px;color:#334155;margin:0 0 4px 0;">Bonjour,</p>
                    <p style="font-size:14px;color:#475569;margin:0 0 20px 0;">Un nouveau ticket a été créé sur la plateforme.</p>
                    <div style="background:#f8fafc;border-radius:12px;padding:20px;border-left:4px solid #2563eb;margin-bottom:20px;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <tr><td style="padding:6px 0;color:#64748b;width:120px;">🔹 Numéro</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . $ticketNumber . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📝 Titre</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . htmlspecialchars($title) . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📊 Statut</td><td style="padding:6px 0;"><span style="background:#dbeafe;color:#1e40af;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $statusLabel . '</span></td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">🎯 Priorité</td><td style="padding:6px 0;"><span style="background:#fef3c7;color:#92400e;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📂 Catégorie</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . $categoryLabel . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">👤 Créé par</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . htmlspecialchars($createdByName) . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📅 Date</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . $createdAt . '</td></tr>
                        </table>
                        ' . (!empty($description) ? '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;"><p style="color:#64748b;font-size:13px;margin:0 0 4px 0;">📄 Description</p><p style="color:#475569;font-size:14px;margin:0;">' . nl2br(htmlspecialchars($description)) . '</p></div>' : '') . '
                    </div>';
                break;
                
            case 'status_change':
                $oldStatusLabel = $extra['old_status'] ?? 'Inconnu';
                $newStatusLabel = $extra['new_status'] ?? 'Inconnu';
                $headerTitle = '📊 Changement de statut';
                $headerColor = '#f59e0b';
                $headerIcon = 'fa-exchange-alt'; // ✅ DÉFINI
                $content = '
                    <p style="font-size:16px;color:#334155;margin:0 0 4px 0;">Bonjour,</p>
                    <p style="font-size:14px;color:#475569;margin:0 0 20px 0;">Le statut du ticket <strong>' . $ticketNumber . '</strong> a été modifié.</p>
                    <div style="background:#f8fafc;border-radius:12px;padding:20px;border-left:4px solid #f59e0b;margin-bottom:20px;">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <tr><td style="padding:6px 0;color:#64748b;width:120px;">🔹 Numéro</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . $ticketNumber . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📝 Titre</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . htmlspecialchars($title) . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📊 Ancien statut</td><td style="padding:6px 0;"><span style="background:#e2e8f0;color:#475569;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $oldStatusLabel . '</span></td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📊 Nouveau statut</td><td style="padding:6px 0;"><span style="background:#dbeafe;color:#1e40af;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $newStatusLabel . '</span></td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">📂 Catégorie</td><td style="padding:6px 0;font-weight:600;color:#0f172a;">' . $categoryLabel . '</td></tr>
                            <tr><td style="padding:6px 0;color:#64748b;">🎯 Priorité</td><td style="padding:6px 0;"><span style="background:#fef3c7;color:#92400e;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                        </table>
                    </div>';
                break;
                
            default:
                $headerTitle = '📌 Mise à jour du ticket';
                $headerColor = '#6b7280';
                $headerIcon = 'fa-bell'; // ✅ DÉFINI
                $content = '<p>Une mise à jour a été effectuée sur le ticket ' . $ticketNumber . '.</p>';
                break;
        }
        
        // ✅ TEMPLATE COMPLET - DESIGN MODERNE ET PROFESSIONNEL
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $headerTitle . '</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #1e293b; }
                .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); overflow: hidden; }
                .header { background: linear-gradient(135deg, #0f172a 0%, #1a237e 100%); color: white; padding: 32px 40px 28px; text-align: center; }
                .header .logo { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-align: center; }
                .header .logo span { opacity: 0.7; font-weight: 400; font-size: 14px; }
                .header .badge { display: inline-block; margin-top: 10px; padding: 4px 18px; background: rgba(255,255,255,0.12); border-radius: 20px; font-size: 12px; font-weight: 500; letter-spacing: 0.3px; backdrop-filter: blur(4px); }
                .content { padding: 32px 40px 24px; }
                .content .title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; display: flex; align-items: center; gap: 10px; }
                .content .title .icon { font-size: 24px; }
                .content .subtitle { font-size: 14px; color: #64748b; margin: 0 0 20px 0; }
                
                .btn-container { text-align: center; margin: 30px 0 15px; padding: 10px 0; }
                .btn-ticket { 
                    display: inline-block; 
                    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                    color: #ffffff !important; 
                    padding: 16px 48px; 
                    text-decoration: none; 
                    border-radius: 12px; 
                    font-weight: 700; 
                    font-size: 18px;
                    letter-spacing: 0.5px;
                    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                    text-transform: uppercase;
                }
                .btn-ticket:hover { 
                    background: linear-gradient(135deg, #1d4ed8 0%, #1a3a8a 100%);
                    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.6);
                    transform: translateY(-3px) scale(1.02);
                }
                .btn-ticket .icon { margin-right: 12px; font-size: 20px; }
                .btn-ticket .arrow { display: inline-block; transition: transform 0.3s ease; margin-left: 10px; font-size: 20px; }
                .btn-ticket:hover .arrow { transform: translateX(8px); }
                
                .footer { background: #f8fafc; padding: 16px 40px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
                .footer a { color: #2563eb; text-decoration: none; }
                .footer .meta { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 4px; }
                @media (max-width: 600px) {
                    .container { border-radius: 12px; }
                    .header { padding: 24px 20px; }
                    .content { padding: 24px 20px; }
                    .footer { padding: 12px 20px; }
                    .content .title { font-size: 18px; flex-wrap: wrap; }
                    .btn-ticket { padding: 14px 28px; font-size: 15px; width: 100%; display: block; text-align: center; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">Plateforme de Ticketing <span>SPIDER Ankorondrano</span></div>
                    <div class="badge">🔔 Notification</div>
                </div>
                <div class="content">
                    <div class="title">
                        <span class="icon"><i class="fas ' . $headerIcon . '"></i></span>
                        ' . $headerTitle . '
                    </div>
                    <div class="subtitle">' . date('d/m/Y à H:i') . '</div>
                    ' . $content . '
                    
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
                    <p style="margin:0;">© ' . date('Y') . ' Plateforme de Ticketing - SPIDER Ankorondrano</p>
                    <div class="meta">
                        <span>📧 Cet email est automatique</span>
                        <span>🔒 Ne pas y répondre directement</span>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * ✅ TEMPLATE ACTION - DESIGN MODERNE
     */
    private function buildActionEmailTemplate($ticket, $actionType, $content, $senderName) {
        $ticketNumber = $ticket['ticket_number'] ?? 'N/A';
        $ticketTitle = $ticket['title'] ?? 'Sans titre';
        $ticketId = $ticket['id'] ?? 0;
        $ticketUrl = APP_URL . "/index.php?page=tickets&action=show&id=" . $ticketId;
        $statusLabel = getStatusLabel($ticket['status'] ?? 'nouveau');
        $priorityLabel = getPriorityLabel($ticket['priority'] ?? 'moyenne');
        $categoryLabel = getCategoryLabel($ticket['category'] ?? 'general');
        
        $actionLabels = [
            'resolu' => ['label' => '✅ Ticket résolu', 'color' => '#10b981', 'icon' => 'fa-check-circle'],
            'en_cours' => ['label' => '🔄 Ticket en cours', 'color' => '#2563eb', 'icon' => 'fa-spinner'],
            'en_attente' => ['label' => '⏳ Ticket en attente', 'color' => '#f59e0b', 'icon' => 'fa-clock'],
            'signaler_probleme' => ['label' => '⚠️ Problème signalé', 'color' => '#ef4444', 'icon' => 'fa-exclamation-triangle'],
            'commentaire' => ['label' => '💬 Nouveau commentaire', 'color' => '#6b7280', 'icon' => 'fa-comment'],
            'notifier_client' => ['label' => '📢 Client notifié', 'color' => '#3b82f6', 'icon' => 'fa-bullhorn'],
            'demander_info' => ['label' => '❓ Demande d\'information', 'color' => '#f97316', 'icon' => 'fa-question-circle'],
            'escalader' => ['label' => '⬆️ Ticket escaladé', 'color' => '#8b5cf6', 'icon' => 'fa-arrow-up']
        ];
        
        $action = $actionLabels[$actionType] ?? ['label' => 'Action effectuée', 'color' => '#6b7280', 'icon' => 'fa-bolt'];
        
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
                .header { background: linear-gradient(135deg, #0f172a 0%, #1a237e 100%); color: white; padding: 32px 40px 28px; text-align: center; }
                .header .logo { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; text-align: center; }
                .header .logo span { opacity: 0.7; font-weight: 400; font-size: 14px; }
                .header .badge { display: inline-block; margin-top: 10px; padding: 4px 18px; background: rgba(255,255,255,0.12); border-radius: 20px; font-size: 12px; font-weight: 500; }
                .content { padding: 32px 40px 24px; }
                .action-banner { background: ' . $action['color'] . '; color: white; padding: 16px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
                .action-banner .icon { font-size: 22px; }
                .action-banner .text { font-size: 16px; font-weight: 600; }
                .action-banner .sub { font-size: 13px; opacity: 0.85; font-weight: 400; }
                .info-table { background: #f8fafc; border-radius: 12px; padding: 20px; border-left: 4px solid ' . $action['color'] . '; margin-bottom: 20px; }
                .info-table table { width: 100%; border-collapse: collapse; font-size: 14px; }
                .info-table td { padding: 5px 0; }
                .info-table .label { color: #64748b; width: 100px; }
                .info-table .value { font-weight: 600; color: #0f172a; }
                
                .btn-container { text-align: center; margin: 30px 0 15px; padding: 10px 0; }
                .btn-ticket { 
                    display: inline-block; 
                    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                    color: #ffffff !important; 
                    padding: 16px 48px; 
                    text-decoration: none; 
                    border-radius: 12px; 
                    font-weight: 700; 
                    font-size: 18px;
                    letter-spacing: 0.5px;
                    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                    text-transform: uppercase;
                }
                .btn-ticket:hover { 
                    background: linear-gradient(135deg, #1d4ed8 0%, #1a3a8a 100%);
                    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.6);
                    transform: translateY(-3px) scale(1.02);
                }
                .btn-ticket .icon { margin-right: 12px; font-size: 20px; }
                .btn-ticket .arrow { display: inline-block; transition: transform 0.3s ease; margin-left: 10px; font-size: 20px; }
                .btn-ticket:hover .arrow { transform: translateX(8px); }
                
                .footer { background: #f8fafc; padding: 16px 40px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
                .footer .meta { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 4px; }
                @media (max-width: 600px) {
                    .container { border-radius: 12px; }
                    .header { padding: 24px 20px; }
                    .content { padding: 24px 20px; }
                    .footer { padding: 12px 20px; }
                    .action-banner { flex-wrap: wrap; }
                    .info-table td { display: block; padding: 3px 0; }
                    .info-table .label { width: auto; }
                    .btn-ticket { padding: 14px 28px; font-size: 15px; width: 100%; display: block; text-align: center; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">Plateforme de Ticketing <span>SPIDER Ankorondrano</span></div>
                    <div class="badge">🔔 Notification</div>
                </div>
                <div class="content">
                    <div class="action-banner">
                        <span class="icon"><i class="fas ' . $action['icon'] . '"></i></span>
                        <div>
                            <div class="text">' . $action['label'] . '</div>
                            <div class="sub">par ' . htmlspecialchars($senderName) . ' · ' . date('d/m/Y H:i') . '</div>
                        </div>
                    </div>
                    <div class="info-table">
                        <table>
                            <tr><td class="label">🔹 Numéro</td><td class="value">' . $ticketNumber . '</td></tr>
                            <tr><td class="label">📝 Titre</td><td class="value">' . htmlspecialchars($ticketTitle) . '</td></tr>
                            <tr><td class="label">📊 Statut</td><td class="value"><span style="background:#dbeafe;color:#1e40af;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $statusLabel . '</span></td></tr>
                            <tr><td class="label">🎯 Priorité</td><td class="value"><span style="background:#fef3c7;color:#92400e;padding:2px 12px;border-radius:20px;font-size:13px;font-weight:600;">' . $priorityLabel . '</span></td></tr>
                            <tr><td class="label">📂 Catégorie</td><td class="value">' . $categoryLabel . '</td></tr>
                        </table>
                    </div>
                    ' . (!empty($content) ? '
                    <div style="background:#f1f5f9;border-radius:8px;padding:16px;margin-bottom:20px;border-left:4px solid #94a3b8;">
                        <p style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px 0;">📝 Message</p>
                        <p style="margin:0;color:#1e293b;font-size:14px;">' . nl2br(htmlspecialchars($content)) . '</p>
                    </div>
                    ' : '') . '
                    
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
                    <p style="margin:0;">© ' . date('Y') . ' Plateforme de Ticketing - SPIDER Ankorondrano</p>
                    <div class="meta">
                        <span>📧 Cet email est automatique</span>
                        <span>🔒 Ne pas y répondre directement</span>
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
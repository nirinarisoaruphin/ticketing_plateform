<?php
// includes/Mailer.php - VERSION CORRIGÉE AVEC DÉBOGAGE
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    private $enabled;
    private $lastError;
    
    public function __construct() {
        if (!defined('MAIL_HOST')) {
            require_once __DIR__ . '/../config/mail.php';
        }
        
        $this->enabled = defined('MAIL_ENABLED') ? MAIL_ENABLED : true;
        $this->lastError = null;
        
        if (!$this->enabled) {
            error_log("⚠️ MAIL_DISABLED: Les emails sont désactivés");
            return;
        }
        
        if (empty(MAIL_USERNAME) || empty(MAIL_PASSWORD)) {
            error_log("❌ MAIL_ERROR: Identifiants email vides");
            $this->enabled = false;
            return;
        }
        
        try {
            $this->mail = new PHPMailer(true);
            
            // ✅ CONFIGURATION SMTP
            $this->mail->isSMTP();
            $this->mail->Host = MAIL_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = MAIL_USERNAME;
            $this->mail->Password = MAIL_PASSWORD;
            $this->mail->SMTPSecure = MAIL_ENCRYPTION;
            $this->mail->Port = MAIL_PORT;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $this->mail->isHTML(true);
            
            // ✅ DÉBOGAGE SMTP - ACTIVÉ
            $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: " . $str);
            };
            
            // ✅ OPTIONS SSL POUR GMAIL
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $this->mail->Timeout = 30;
            
            error_log("📧 Mailer initialisé avec succès");
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->enabled = false;
            error_log("❌ Mailer Error: " . $e->getMessage());
        }
    }
    
    public function send($to, $subject, $message, $toName = '') {
        if (!$this->enabled) {
            $this->logEmail($to, $subject, 'MAIL_DISABLED');
            return false;
        }
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logEmail($to, $subject, 'EMAIL_INVALIDE');
            return false;
        }
        
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $toName ?: $to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $message;
            $this->mail->AltBody = strip_tags($message);
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("✅ Email envoyé à : " . $to);
                $this->logEmail($to, $subject, 'SUCCESS');
                return true;
            } else {
                $this->logEmail($to, $subject, 'SEND_FAILED');
                return false;
            }
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("❌ Mailer Error: " . $e->getMessage() . " - To: " . $to);
            $this->logEmail($to, $subject, $e->getMessage());
            return false;
        }
    }
    
    private function logEmail($to, $subject, $status) {
        $logFile = __DIR__ . '/../logs/emails.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $log = date('Y-m-d H:i:s') . " | ";
        $log .= "To: $to | ";
        $log .= "Subject: $subject | ";
        $log .= "Status: $status";
        $log .= "\n";
        
        file_put_contents($logFile, $log, FILE_APPEND);
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public static function getTemplate($title, $content) {
        $appName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Plateforme de Ticketing';
        $appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ticketing_plateform';
        $year = date('Y');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }
        .container {
            max-width: 640px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header .subtitle {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 4px;
            font-weight: 300;
        }
        .header .badge-header {
            display: inline-block;
            margin-top: 10px;
            padding: 4px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.15);
            font-size: 12px;
            font-weight: 500;
        }
        .content {
            padding: 32px 32px 24px;
            color: #1e293b;
            line-height: 1.7;
        }
        .content h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 8px;
        }
        .content p {
            margin: 6px 0;
            color: #475569;
        }
        .info-block {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 16px 0;
            border-left: 4px solid #2563eb;
        }
        .info-block .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 4px;
        }
        .info-block .row:last-child {
            border-bottom: none;
        }
        .info-block .label {
            font-weight: 500;
            color: #64748b;
            font-size: 13px;
        }
        .info-block .value {
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s;
            margin: 8px 0;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .footer {
            background: #f8fafc;
            padding: 16px 32px;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .container { margin: 10px; border-radius: 12px; }
            .header { padding: 20px; }
            .content { padding: 20px; }
            .info-block { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ {$appName}</h1>
            <div class="subtitle">Plateforme de gestion des tickets</div>
            <div class="badge-header">🔔 Notification automatique</div>
        </div>
        <div class="content">
            {$content}
        </div>
        <div class="footer">
            <p>© {$year} {$appName} - Tous droits réservés</p>
            <p style="font-size: 10px; color: #cbd5e1;">
                Cet email est automatique, merci de ne pas y répondre.
            </p>
            <p style="font-size: 10px; color: #cbd5e1;">
                <a href="{$appUrl}">Accéder à la plateforme</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
?>
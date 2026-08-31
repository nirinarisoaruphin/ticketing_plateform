<?php
// models/EmailNotification.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotification {
    private $mailer;
    private $enabled;
    
    public function __construct() {
        $this->enabled = MAIL_ENABLED;
        if (!$this->enabled) return;
        
        try {
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
            $this->mailer->Port = MAIL_PORT;
            $this->mailer->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            $this->enabled = false;
            error_log("Mailer Error: " . $e->getMessage());
        }
    }
    
    public function send($to, $subject, $message) {
        if (!$this->enabled) return false;
        
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->AltBody = strip_tags($message);
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
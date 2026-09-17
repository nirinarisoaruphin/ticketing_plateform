<?php
// includes/MailerPHPMailer.php
require_once __DIR__ . '/../vendor/autoload.php'; // Si Composer
// Ou require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailerPHPMailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Configuration SMTP
        $this->mail->isSMTP();
        $this->mail->Host = MAIL_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = MAIL_USERNAME;
        $this->mail->Password = MAIL_PASSWORD;
        $this->mail->SMTPSecure = MAIL_ENCRYPTION;
        $this->mail->Port = MAIL_PORT;
        
        $this->mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }
    
    public function send($to, $subject, $message, $toName = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $message;
            $this->mail->AltBody = strip_tags($message);
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Erreur PHPMailer: " . $e->getMessage());
            return false;
        }
    }
}
?>
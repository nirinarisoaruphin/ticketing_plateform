<?php
// includes/EmailQueue.php
// ============================================
// FILE D'ATTENTE DES EMAILS
// ============================================
// Les emails sont insérés en base (table email_queue) au lieu d'être
// envoyés pendant la requête HTTP. Un cron (cron/email_sender.php) les
// envoie ensuite en arrière-plan, avec UNE SEULE connexion SMTP.
//
// Pourquoi : une connexion SMTP (TCP + TLS + AUTH) coûte 1 à 3 secondes.
// Avec 5 destinataires, la création d'un ticket attendait 5 à 15 secondes
// avant d'afficher quoi que ce soit. L'insertion en file prend ~1 ms.

require_once __DIR__ . '/../models/Database.php';

class EmailQueue {

    /**
     * La file est-elle active ?
     * Pilotée par MAIL_QUEUE_ENABLED dans config/mail.php.
     * Si la constante n'existe pas, la file est active par défaut :
     * c'est le comportement rapide, et il ne perd aucun email.
     */
    public static function isEnabled() {
        return defined('MAIL_QUEUE_ENABLED') ? (bool)MAIL_QUEUE_ENABLED : true;
    }

    /**
     * Mettre en file un lot de destinataires pour un même message.
     *
     * @param array  $recipients   [email => nom]
     * @param string $subject
     * @param string $message      corps HTML
     * @param array  $ticket       ticket associé (facultatif)
     * @return int   nombre d'emails mis en file
     */
    public static function pushBatch($recipients, $subject, $message, $ticket = null) {
        if (empty($recipients)) {
            return 0;
        }

        $ticketId     = isset($ticket['id']) ? (int)$ticket['id'] : null;
        $ticketNumber = isset($ticket['ticket_number']) ? $ticket['ticket_number'] : null;

        // Une seule requête INSERT pour tous les destinataires
        $placeholders = [];
        $params       = [];

        foreach ($recipients as $email => $name) {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $placeholders[] = "(?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $params[] = $ticketId;
            $params[] = $ticketNumber;
            $params[] = $email;
            $params[] = $name;
            $params[] = $subject;
            $params[] = $message;
        }

        if (empty($placeholders)) {
            return 0;
        }

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO email_queue
                    (ticket_id, ticket_number, recipient_email, recipient_name,
                     subject, message, status, created_at)
                 VALUES " . implode(', ', $placeholders),
                $params
            );
            return count($placeholders) ;
        } catch (Exception $e) {
            error_log("EmailQueue: insertion impossible - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mettre en file un seul email.
     */
    public static function push($email, $name, $subject, $message, $ticket = null) {
        return self::pushBatch([$email => $name], $subject, $message, $ticket);
    }
}

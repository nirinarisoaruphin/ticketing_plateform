<?php
// views/whatsapp/share.php - Page de partage WhatsApp

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$actionType = isset($_GET['action']) ? $_GET['action'] : '';

if ($ticketId <= 0) {
    redirect('index.php?page=dashboard');
}

$db = Database::getInstance();
$ticket = $db->fetch("SELECT * FROM tickets WHERE id = ?", [$ticketId]);

if (!$ticket) {
    redirect('index.php?page=tickets');
}

// Générer l'image WhatsApp
require_once __DIR__ . '/../../includes/WhatsAppImageGenerator.php';
$imageGenerator = new WhatsAppImageGenerator();
$senderName = $_SESSION['user_name'] ?? 'Utilisateur';
$imageData = $imageGenerator->generateActionImage($ticket, $actionType, $senderName, '');

// Envoyer le message WhatsApp
$phoneNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '261340000001';
$whatsappUrl = $imageData['whatsapp_url'];

redirect($whatsappUrl);
?>
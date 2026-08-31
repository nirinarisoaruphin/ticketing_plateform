<?php
// cron/planning_automation.php - AUTOMATISATION COMPLÈTE (VERSION CORRIGÉE SANS #)
// À exécuter toutes les minutes via cron ou tâche planifiée

// ============================================
// CONFIGURATION
// ============================================

$root = __DIR__ . '/../';
require_once $root . 'config/database.php';
require_once $root . 'includes/functions.php';
require_once $root . 'models/Intervention.php';
require_once $root . 'models/Ticket.php';
require_once $root . 'models/Notification.php';
require_once $root . 'includes/Mailer.php';

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si le script est exécuté en ligne de commande ou via cron
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: application/json');
}

$db = Database::getInstance();
$interventionModel = new Intervention();
$ticketModel = new Ticket();
$notificationModel = new Notification();
$mailer = new Mailer();

$now = new DateTime();
$nowStr = $now->format('Y-m-d H:i:s');
$nowDate = $now->format('Y-m-d');

// ============================================
// FICHIER DE VERROUILLAGE (LOCK)
// ============================================

$lockFile = __DIR__ . '/../tmp/planning_automation.lock';
$lockDir = dirname($lockFile);

if (!is_dir($lockDir)) {
    mkdir($lockDir, 0777, true);
}

// Vérifier si le script est déjà en cours d'exécution
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    if (time() - $lockTime < 300) { // 5 minutes
        if ($isCli) {
            echo "⏳ Script déjà en cours d'exécution\n";
        } else {
            echo json_encode(['status' => 'locked', 'message' => 'Script déjà en cours']);
        }
        exit(0);
    }
}

// Créer le fichier de verrouillage
touch($lockFile);

// ============================================
// LOGS
// ============================================

function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/planning_automation.log';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

logMessage("🔍 Vérification des interventions à traiter...");

// ============================================
// 1. DÉMARRER LES INTERVENTIONS PLANIFIÉES
// ============================================

// Récupérer les interventions planifiées dont l'heure est <= maintenant
$toStart = $db->fetchAll(
    "SELECT i.*, 
            t.ticket_number, 
            t.title as ticket_title,
            t.created_by,
            u.full_name as technician_name,
            u.email as technician_email
     FROM interventions i
     INNER JOIN tickets t ON i.ticket_id = t.id
     INNER JOIN users u ON i.technician_id = u.id
     WHERE i.status = 'planifiee' 
       AND CONCAT(i.planned_date, ' ', i.planned_time) <= ?
       AND i.planned_date = ?",
    [$nowStr, $nowDate]
);

$startedCount = 0;

if (!empty($toStart)) {
    logMessage("📋 " . count($toStart) . " intervention(s) à démarrer");
}

foreach ($toStart as $intervention) {
    try {
        // ✅ Vérifier que l'intervention n'est pas déjà en cours
        $check = $db->fetch(
            "SELECT id, status FROM interventions WHERE id = ?",
            [$intervention['id']]
        );
        
        if (!$check || $check['status'] !== 'planifiee') {
            continue;
        }
        
        // ============================================
        // 1A. DÉMARRER L'INTERVENTION
        // ============================================
        $db->query(
            "UPDATE interventions SET 
                status = 'en_cours', 
                actual_start = NOW(),
                updated_at = NOW() 
             WHERE id = ?",
            [$intervention['id']]
        );
        
        // ✅ Mettre à jour le ticket
        $ticketModel->update($intervention['ticket_id'], [
            'status' => 'en_cours'
        ]);
        
        // ✅ Ajouter à l'historique
        $db->insert(
            "INSERT INTO intervention_history (intervention_id, user_id, action, details, created_at) 
             VALUES (?, NULL, 'Démarrée', ?, NOW())",
            [
                $intervention['id'],
                "Intervention démarrée à {$nowStr} (technicien: {$intervention['technician_name']})"
            ]
        );
        
        // ============================================
        // 1B. NOTIFICATIONS IN-APP (SANS #)
        // ============================================
        $link = "index.php?page=planning";
        $ticketNumber = $intervention['ticket_number'];
        
        // ✅ Notifier le technicien - SANS #
        $notificationModel->createNotification(
            $intervention['technician_id'],
            "▶️ Intervention {$ticketNumber} démarrée",  // ✅ SANS #
            $link,
            'planning'
        );
        
        // ✅ Notifier le créateur du ticket - SANS #
        if (!empty($intervention['created_by'])) {
            $notificationModel->createNotification(
                $intervention['created_by'],
                "▶️ Intervention {$ticketNumber} démarrée",  // ✅ SANS #
                $link,
                'planning'
            );
        }
        
        // ✅ Notifier le coordinateur - SANS #
        $coordinateurs = $db->fetchAll("SELECT id FROM users WHERE role = 'coordinateur'");
        foreach ($coordinateurs as $coord) {
            $notificationModel->createNotification(
                $coord['id'],
                "▶️ Intervention {$ticketNumber} démarrée",  // ✅ SANS #
                $link,
                'planning'
            );
        }
        
        // ✅ Notifier l'admin - SANS #
        $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            $notificationModel->createNotification(
                $admin['id'],
                "▶️ Intervention {$ticketNumber} démarrée",  // ✅ SANS #
                $link,
                'planning'
            );
        }
        
        // ============================================
        // 1C. ENVOI D'EMAILS (SANS #)
        // ============================================
        try {
            // Email au technicien
            $subject = "▶️ Intervention {$ticketNumber} démarrée";
            $message = $mailer->getTemplate($subject, "
                <div style='padding:20px;'>
                    <h2 style='color:#2563eb;'>▶️ Intervention démarrée</h2>
                    <p>Bonjour <strong>{$intervention['technician_name']}</strong>,</p>
                    <p>L'intervention sur le ticket <strong>{$ticketNumber}</strong> a été démarrée.</p>
                    <div style='background:#f8fafc;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #2563eb;'>
                        <p><strong>Ticket :</strong> {$ticketNumber}</p>
                        <p><strong>Titre :</strong> {$intervention['ticket_title']}</p>
                        <p><strong>Début :</strong> {$nowStr}</p>
                        <p><strong>Durée prévue :</strong> {$intervention['duration']} min</p>
                        <p><strong>Fin prévue :</strong> " . date('H:i', strtotime($nowStr) + ($intervention['duration'] * 60)) . "</p>
                        <p><strong>Technicien :</strong> {$intervention['technician_name']}</p>
                    </div>
                    <p style='text-align:center;margin:25px 0;'>
                        <a href='" . APP_URL . "/index.php?page=planning' style='display:inline-block;background:#2563eb;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>
                            Voir le planning
                        </a>
                    </p>
                </div>
            ");
            $mailer->send($intervention['technician_email'], $subject, $message, $intervention['technician_name']);
            
        } catch (Exception $e) {
            error_log("❌ Erreur envoi email démarrage: " . $e->getMessage());
        }
        
        $startedCount++;
        logMessage("✅ Intervention {$intervention['ticket_number']} démarrée");
        
    } catch (Exception $e) {
        logMessage("❌ Erreur démarrage intervention #{$intervention['id']}: " . $e->getMessage(), 'ERROR');
    }
}

// ============================================
// 2. TERMINER LES INTERVENTIONS EN COURS
// ============================================

// Récupérer les interventions en cours dont la date/heure + durée est <= maintenant
$toComplete = $db->fetchAll(
    "SELECT i.*, 
            t.ticket_number, 
            t.title as ticket_title,
            t.created_by,
            u.full_name as technician_name,
            u.email as technician_email
     FROM interventions i
     INNER JOIN tickets t ON i.ticket_id = t.id
     INNER JOIN users u ON i.technician_id = u.id
     WHERE i.status = 'en_cours' 
       AND DATE_ADD(CONCAT(i.planned_date, ' ', i.planned_time), INTERVAL i.duration MINUTE) <= ?",
    [$nowStr]
);

$completedCount = 0;

if (!empty($toComplete)) {
    logMessage("📋 " . count($toComplete) . " intervention(s) à terminer");
}

foreach ($toComplete as $intervention) {
    try {
        // ✅ Vérifier que l'intervention n'est pas déjà terminée
        $check = $db->fetch(
            "SELECT id, status FROM interventions WHERE id = ?",
            [$intervention['id']]
        );
        
        if (!$check || $check['status'] !== 'en_cours') {
            continue;
        }
        
        // ✅ Calculer la durée réelle
        $actualDuration = $intervention['duration'];
        if (!empty($intervention['actual_start'])) {
            $start = new DateTime($intervention['actual_start']);
            $end = new DateTime($nowStr);
            $actualDuration = $end->diff($start)->i;
            if ($actualDuration < 1) {
                $actualDuration = $intervention['duration'];
            }
        }
        
        // ============================================
        // 2A. TERMINER L'INTERVENTION
        // ============================================
        $db->query(
            "UPDATE interventions SET 
                status = 'realisee',
                actual_duration = ?,
                updated_at = NOW() 
             WHERE id = ?",
            [$actualDuration, $intervention['id']]
        );
        
        // ✅ Mettre à jour le ticket
        $ticketModel->update($intervention['ticket_id'], [
            'status' => 'resolu',
            'resolved_at' => date('Y-m-d H:i:s')
        ]);
        
        // ✅ Ajouter à l'historique
        $db->insert(
            "INSERT INTO intervention_history (intervention_id, user_id, action, details, created_at) 
             VALUES (?, NULL, 'Terminée', ?, NOW())",
            [
                $intervention['id'],
                "Intervention terminée à {$nowStr} (durée réelle: {$actualDuration} min)"
            ]
        );
        
        // ============================================
        // 2B. NOTIFICATIONS IN-APP (SANS #)
        // ============================================
        $link = "index.php?page=planning";
        $ticketLink = "index.php?page=tickets&action=show&id=" . $intervention['ticket_id'];
        $ticketNumber = $intervention['ticket_number'];
        
        // ✅ Notifier le technicien - SANS #
        $notificationModel->createNotification(
            $intervention['technician_id'],
            "✅ Intervention {$ticketNumber} terminée",  // ✅ SANS #
            $link,
            'planning'
        );
        
        // ✅ Notifier le créateur du ticket - SANS #
        if (!empty($intervention['created_by'])) {
            $notificationModel->createNotification(
                $intervention['created_by'],
                "✅ Intervention {$ticketNumber} terminée - Ticket résolu",  // ✅ SANS #
                $ticketLink,
                'planning'
            );
        }
        
        // ✅ Notifier le coordinateur - SANS #
        $coordinateurs = $db->fetchAll("SELECT id FROM users WHERE role = 'coordinateur'");
        foreach ($coordinateurs as $coord) {
            $notificationModel->createNotification(
                $coord['id'],
                "✅ Intervention {$ticketNumber} terminée",  // ✅ SANS #
                $link,
                'planning'
            );
        }
        
        // ✅ Notifier l'admin - SANS #
        $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            $notificationModel->createNotification(
                $admin['id'],
                "✅ Intervention {$ticketNumber} terminée",  // ✅ SANS #
                $link,
                'planning'
            );
        }
        
        // ============================================
        // 2C. ENVOI D'EMAILS (SANS #)
        // ============================================
        try {
            // Email au technicien
            $subject = "✅ Intervention {$ticketNumber} terminée";
            $message = $mailer->getTemplate($subject, "
                <div style='padding:20px;'>
                    <h2 style='color:#10B981;'>✅ Intervention terminée</h2>
                    <p>Bonjour <strong>{$intervention['technician_name']}</strong>,</p>
                    <p>L'intervention sur le ticket <strong>{$ticketNumber}</strong> a été terminée.</p>
                    <div style='background:#f8fafc;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #10B981;'>
                        <p><strong>Ticket :</strong> {$ticketNumber}</p>
                        <p><strong>Titre :</strong> {$intervention['ticket_title']}</p>
                        <p><strong>Fin :</strong> {$nowStr}</p>
                        <p><strong>Durée prévue :</strong> {$intervention['duration']} min</p>
                        <p><strong>Durée réelle :</strong> {$actualDuration} min</p>
                    </div>
                    <p style='text-align:center;margin:25px 0;'>
                        <a href='" . APP_URL . "/index.php?page=planning' style='display:inline-block;background:#2563eb;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>
                            Voir le planning
                        </a>
                    </p>
                </div>
            ");
            $mailer->send($intervention['technician_email'], $subject, $message, $intervention['technician_name']);
            
            // Email au créateur
            if (!empty($intervention['created_by'])) {
                $creator = $db->fetch("SELECT email, full_name FROM users WHERE id = ?", [$intervention['created_by']]);
                if ($creator) {
                    $subjectCreator = "✅ Ticket {$ticketNumber} résolu";
                    $messageCreator = $mailer->getTemplate($subjectCreator, "
                        <div style='padding:20px;'>
                            <h2 style='color:#10B981;'>✅ Ticket résolu</h2>
                            <p>Bonjour <strong>{$creator['full_name']}</strong>,</p>
                            <p>Votre ticket <strong>{$ticketNumber}</strong> a été résolu.</p>
                            <div style='background:#f8fafc;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #10B981;'>
                                <p><strong>Ticket :</strong> {$ticketNumber}</p>
                                <p><strong>Titre :</strong> {$intervention['ticket_title']}</p>
                                <p><strong>Résolu le :</strong> {$nowStr}</p>
                                <p><strong>Technicien :</strong> {$intervention['technician_name']}</p>
                            </div>
                            <p style='text-align:center;margin:25px 0;'>
                                <a href='" . APP_URL . "/index.php?page=tickets&action=show&id=" . $intervention['ticket_id'] . "' style='display:inline-block;background:#2563eb;color:white;padding:10px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>
                                    Voir le ticket
                                </a>
                            </p>
                        </div>
                    ");
                    $mailer->send($creator['email'], $subjectCreator, $messageCreator, $creator['full_name']);
                }
            }
            
        } catch (Exception $e) {
            error_log("❌ Erreur envoi email terminaison: " . $e->getMessage());
        }
        
        $completedCount++;
        logMessage("✅ Intervention {$ticketNumber} terminée");
        
    } catch (Exception $e) {
        logMessage("❌ Erreur terminaison intervention #{$intervention['id']}: " . $e->getMessage(), 'ERROR');
    }
}

// ============================================
// 3. NETTOYER LES INTERVENTIONS ANCIENNES
// ============================================

// Interventions planifiées depuis plus de 7 jours sans être démarrées
$toCleanup = $db->fetchAll(
    "SELECT i.*, t.ticket_number
     FROM interventions i
     INNER JOIN tickets t ON i.ticket_id = t.id
     WHERE i.status = 'planifiee' 
       AND CONCAT(i.planned_date, ' ', i.planned_time) < DATE_SUB(?, INTERVAL 7 DAY)",
    [$nowStr]
);

$cleanedCount = 0;

if (!empty($toCleanup)) {
    logMessage("📋 " . count($toCleanup) . " intervention(s) ancienne(s) à annuler");
}

foreach ($toCleanup as $intervention) {
    try {
        // Annuler l'intervention
        $db->query(
            "UPDATE interventions SET status = 'annulee', updated_at = NOW() WHERE id = ?",
            [$intervention['id']]
        );
        
        // Mettre à jour le ticket
        $ticketModel->update($intervention['ticket_id'], [
            'status' => 'nouveau',
            'assigned_to' => null
        ]);
        
        // Ajouter à l'historique
        $db->insert(
            "INSERT INTO intervention_history (intervention_id, user_id, action, details, created_at) 
             VALUES (?, NULL, 'Annulée', ?, NOW())",
            [
                $intervention['id'],
                "Intervention annulée car planifiée depuis plus de 7 jours"
            ]
        );
        
        // Notification (SANS #)
        $ticketNumber = $intervention['ticket_number'];
        $notificationModel->createNotification(
            $intervention['technician_id'],
            "🗑️ Intervention {$ticketNumber} annulée (trop ancienne)",  // ✅ SANS #
            "index.php?page=planning",
            'planning'
        );
        
        $cleanedCount++;
        logMessage("🗑️ Intervention {$ticketNumber} annulée automatiquement");
        
    } catch (Exception $e) {
        logMessage("❌ Erreur nettoyage intervention #{$intervention['id']}: " . $e->getMessage(), 'ERROR');
    }
}

// ============================================
// 4. LOG FINAL
// ============================================

$logMessage = "✅ Exécuté: {$startedCount} démarrées, {$completedCount} terminées, {$cleanedCount} annulées";
logMessage($logMessage);

// ============================================
// 5. SUPPRIMER LE VERROUILLAGE
// ============================================

unlink($lockFile);

// ============================================
// 6. RÉPONSE
// ============================================

if ($isCli) {
    echo "✅ Planning automation exécuté : $startedCount démarrées, $completedCount terminées, $cleanedCount annulées\n";
    exit(0);
} else {
    echo json_encode([
        'success' => true,
        'started' => $startedCount,
        'completed' => $completedCount,
        'cleaned' => $cleanedCount,
        'time' => $nowStr
    ]);
    exit;
}
?>
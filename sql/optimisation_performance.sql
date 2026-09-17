-- ============================================
-- OPTIMISATION DES PERFORMANCES
-- Index pour les requêtes les plus fréquentes
-- ============================================
--
-- À exécuter une seule fois :
--   mysql -u UTILISATEUR -p NOM_DE_LA_BASE < sql/optimisation_performance.sql
--
-- Chaque index est créé via une procédure qui vérifie d'abord son
-- absence : le script peut donc être relancé sans erreur.

-- ============================================
-- TABLE email_queue
-- ============================================
-- Créée seulement si elle n'existe pas déjà : elle est indispensable
-- au nouvel envoi d'emails en arrière-plan (cron/email_sender.php).

CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT DEFAULT NULL,
    `ticket_number` VARCHAR(50) DEFAULT NULL,
    `recipient_email` VARCHAR(100) DEFAULT NULL,
    `recipient_name` VARCHAR(100) DEFAULT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `error` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS ajouter_index_si_absent $$
CREATE PROCEDURE ajouter_index_si_absent(
    IN nom_table VARCHAR(64),
    IN nom_index VARCHAR(64),
    IN definition VARCHAR(255)
)
BEGIN
    DECLARE deja_present INT DEFAULT 0;

    SELECT COUNT(*) INTO deja_present
    FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name   = nom_table
      AND index_name   = nom_index;

    IF deja_present = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', nom_table, '` ADD INDEX `', nom_index, '` ', definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- ============================================
-- TICKETS
-- ============================================

-- Contrôle anti-doublon à la création :
--   WHERE created_by = ? AND category = ? AND description = ?
--     AND created_at >= (NOW() - INTERVAL 20 SECOND)
-- Sans cet index, MySQL parcourt tous les tickets de l'auteur.
CALL ajouter_index_si_absent('tickets', 'idx_tickets_doublon', '(created_by, category, created_at)');

-- Listes filtrées par statut et tableau de bord
CALL ajouter_index_si_absent('tickets', 'idx_tickets_statut_date', '(status, created_at)');
CALL ajouter_index_si_absent('tickets', 'idx_tickets_assigne_statut', '(assigned_to, status)');
CALL ajouter_index_si_absent('tickets', 'idx_tickets_auteur_statut', '(created_by, status)');

-- Statistiques par catégorie et statut (DashboardController)
CALL ajouter_index_si_absent('tickets', 'idx_tickets_categorie_statut', '(category, status)');

-- ============================================
-- FILES D'ATTENTE
-- ============================================

-- Requête du cron : WHERE status = 'pending' ORDER BY created_at ASC
CALL ajouter_index_si_absent('email_queue', 'idx_email_queue_traitement', '(status, created_at)');

-- ============================================
-- NOTIFICATIONS
-- ============================================

-- Compteur de notifications non lues, appelé en continu par le front
CALL ajouter_index_si_absent('notifications', 'idx_notifications_user_lu', '(user_id, is_read)');

-- ============================================
-- INTERVENTIONS
-- ============================================

-- Sélection du cron de planification
CALL ajouter_index_si_absent('interventions', 'idx_interventions_statut_date', '(status, planned_date)');

-- Recherche d'une intervention par ticket (PlanningController::create)
CALL ajouter_index_si_absent('interventions', 'idx_interventions_ticket_statut', '(ticket_id, status)');

DROP PROCEDURE IF EXISTS ajouter_index_si_absent;

-- ============================================
-- WHATSAPP (table optionnelle)
-- ============================================
-- Si la table whatsapp_queue existe, décommentez la ligne suivante :
-- ALTER TABLE `whatsapp_queue` ADD INDEX `idx_whatsapp_traitement` (`status`, `created_at`);

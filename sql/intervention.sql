-- ============================================
-- RECRÉER LA TABLE INTERVENTIONS AVEC TOUTES LES COLONNES
-- ============================================

-- 1. Sauvegarder les données existantes (si nécessaire)
CREATE TABLE interventions_backup AS SELECT * FROM interventions;

-- 2. Supprimer la table existante
DROP TABLE IF EXISTS `interventions`;

-- 3. Recréer la table avec toutes les colonnes
CREATE TABLE `interventions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `technician_id` INT NOT NULL,
    `planned_date` DATE NOT NULL,
    `planned_time` TIME NOT NULL,
    `actual_start` DATETIME DEFAULT NULL,
    `duration` INT DEFAULT 60,
    `actual_duration` INT DEFAULT NULL,
    `actual_end` DATETIME DEFAULT NULL,
    `status` ENUM('planifiee', 'en_cours', 'en_attente', 'realisee', 'annulee') DEFAULT 'planifiee',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_interventions_ticket` (`ticket_id`),
    INDEX `idx_interventions_technician` (`technician_id`),
    INDEX `idx_interventions_date` (`planned_date`),
    CONSTRAINT `fk_interventions_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_interventions_technician` FOREIGN KEY (`technician_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Restaurer les données (si sauvegardées)
-- INSERT INTO interventions (id, ticket_id, technician_id, planned_date, planned_time, duration, status, notes, created_at, updated_at)
-- SELECT id, ticket_id, technician_id, planned_date, planned_time, duration, status, notes, created_at, updated_at FROM interventions_backup;
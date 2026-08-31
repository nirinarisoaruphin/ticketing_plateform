-- ============================================
-- 1. CRÉER LA BASE DE DONNÉES
-- ============================================

CREATE DATABASE IF NOT EXISTS `ticketing_plateform` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ticketing_plateform`;

-- ============================================
-- 2. TABLE `users`
-- ============================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM(
        'admin',
        'coordinateur',
        'responsable_support_technique',
        'responsable_sav',
        'responsable_travaux',
        'commercial',
        'charge_etude_electricite',
        'charge_etude_courant_faible',
        'charge_etude_climatisation'
    ) DEFAULT 'commercial',
    `must_change_password` TINYINT(1) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TABLE `tickets`
-- ============================================

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `category` ENUM(
        'support_technique',
        'bureau_etude',
        'sav',
        'travaux'
    ) DEFAULT 'support_technique',
    `type_demande` ENUM(
        'etude',
        'visite',
        'visite_etude',
        'visite_etude_installation',
        'panne',
        'installation',
        'maintenance',
        'sav',
        'travaux',
        'demande_info',
        'autre'
    ) DEFAULT 'etude',
    `priority` ENUM('basse', 'moyenne', 'haute', 'critique') DEFAULT 'moyenne',
    `status` ENUM('nouveau', 'assigne', 'en_cours', 'en_attente', 'resolu', 'cloture') DEFAULT 'nouveau',
    `validation_status` ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    `validation_comment` TEXT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `assigned_to` INT DEFAULT NULL,
    `commercial_dedie` VARCHAR(100) DEFAULT NULL,
    `client_name` VARCHAR(100) DEFAULT NULL,
    `adresse_client` TEXT DEFAULT NULL,
    `interlocuteur` VARCHAR(100) DEFAULT NULL,
    `contact_technique` VARCHAR(100) DEFAULT NULL,
    `lieu_visite` VARCHAR(255) DEFAULT NULL,
    `visite_date` DATE DEFAULT NULL,
    `visite_heure` TIME DEFAULT NULL,
    `moyen_transport` VARCHAR(50) DEFAULT NULL,
    `elements_complement` TEXT DEFAULT NULL,
    `attachment` VARCHAR(255) DEFAULT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `validated_by` INT DEFAULT NULL,
    `validated_at` DATETIME DEFAULT NULL,
    `refused_by` INT DEFAULT NULL,
    `refused_at` DATETIME DEFAULT NULL,
    `return_message` TEXT DEFAULT NULL,
    `returned_by` INT DEFAULT NULL,
    `returned_at` DATETIME DEFAULT NULL,
    `reformulation_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tickets_created_by` (`created_by`),
    INDEX `idx_tickets_assigned_to` (`assigned_to`),
    INDEX `idx_tickets_status` (`status`),
    INDEX `idx_tickets_category` (`category`),
    INDEX `idx_tickets_priority` (`priority`),
    INDEX `idx_tickets_ticket_number` (`ticket_number`),
    CONSTRAINT `fk_tickets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tickets_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. TABLE `ticket_assignments` (Assignations multiples)
-- ============================================

DROP TABLE IF EXISTS `ticket_assignments`;
CREATE TABLE `ticket_assignments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `assigned_by` INT DEFAULT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `is_active` TINYINT(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ticket_user` (`ticket_id`, `user_id`),
    INDEX `idx_assignments_ticket` (`ticket_id`),
    INDEX `idx_assignments_user` (`user_id`),
    CONSTRAINT `fk_assignments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assignments_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TABLE `comments`
-- ============================================

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `action_type` VARCHAR(50) DEFAULT NULL,
    `notify_roles` VARCHAR(255) DEFAULT NULL,
    `is_action` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_comments_ticket` (`ticket_id`),
    INDEX `idx_comments_user` (`user_id`),
    INDEX `idx_comments_created_at` (`created_at`),
    CONSTRAINT `fk_comments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. TABLE `notifications`
-- ============================================

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `ticket_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `type` ENUM(
        'ticket',
        'comment',
        'status',
        'action',
        'message',
        'validation',
        'assignation',
        'planning',
        'general'
    ) DEFAULT 'general',
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notifications_user` (`user_id`),
    INDEX `idx_notifications_read` (`is_read`),
    INDEX `idx_notifications_created_at` (`created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notifications_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. TABLE `interventions` (Planning)
-- ============================================

DROP TABLE IF EXISTS `interventions`;
CREATE TABLE `interventions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `technician_id` INT NOT NULL,
    `planned_date` DATE NOT NULL,
    `planned_time` TIME NOT NULL,
    `duration` INT DEFAULT 60,
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

-- ============================================
-- 8. TABLE `intervention_history`
-- ============================================

DROP TABLE IF EXISTS `intervention_history`;
CREATE TABLE `intervention_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `intervention_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_history_intervention` (`intervention_id`),
    CONSTRAINT `fk_history_intervention` FOREIGN KEY (`intervention_id`) REFERENCES `interventions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. TABLE `logs`
-- ============================================

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_logs_user` (`user_id`),
    INDEX `idx_logs_action` (`action`),
    INDEX `idx_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TABLE `email_queue`
-- ============================================

DROP TABLE IF EXISTS `email_queue`;
CREATE TABLE `email_queue` (
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
    PRIMARY KEY (`id`),
    INDEX `idx_queue_status` (`status`),
    INDEX `idx_queue_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. TABLE `login_attempts`
-- ============================================

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(100) NOT NULL,
    `success` TINYINT(1) DEFAULT 0,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_attempts_email` (`email`),
    INDEX `idx_attempts_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. DONNÉES INITIALES
-- ============================================

-- ============================================
-- 12.1 Création des utilisateurs par défaut
-- ============================================

-- Mot de passe : admin123 (hashé)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `role`, `must_change_password`, `active`, `created_at`) VALUES
-- 👑 ADMIN PRINCIPAL = RUPHIN
('ruphin', 'ruphin@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ruphin - Administrateur', '+261 34 00 000 08', 'admin', 0, 1, NOW()),

-- 📋 COORDINATEURS
('mikajy', 'mikajy@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mikajy - Coordinateur', '+261 34 00 000 01', 'coordinateur', 0, 1, NOW()),

-- 🔬 SUPPORT TECHNIQUE
('mahery', 'mahery@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mahery - Responsable Support Technique', '+261 34 00 000 02', 'responsable_support_technique', 0, 1, NOW()),

-- ⚡ CHARGÉS D'ÉTUDE
('faniry', 'faniry@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Faniry - Chargé d\'Étude Electricité', '+261 34 00 000 03', 'charge_etude_electricite', 0, 1, NOW()),
('anthony', 'anthony@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anthony - Chargé d\'Étude Courant Faible', '+261 34 00 000 04', 'charge_etude_courant_faible', 0, 1, NOW()),
('onintsoa', 'onintsoa@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Onintsoa - Chargé d\'Étude Climatisation', '+261 34 00 000 05', 'charge_etude_climatisation', 0, 1, NOW()),

-- 🔧 RESPONSABLES
('dina', 'dina@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dina - Responsable SAV', '+261 34 00 000 06', 'responsable_sav', 0, 1, NOW()),
('andry', 'andry@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andry - Responsable Travaux', '+261 34 00 000 07', 'responsable_travaux', 0, 1, NOW()),

-- 💼 COMMERCIAUX
('commercial1', 'commercial1@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Commercial 1', '+261 34 00 000 10', 'commercial', 0, 1, NOW()),
('commercial2', 'commercial2@spider.mg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Commercial 2', '+261 34 00 000 11', 'commercial', 0, 1, NOW());

-- ============================================
-- 12.2 Exemple de ticket (optionnel)
-- ============================================

INSERT INTO `tickets` (
    `ticket_number`, 
    `title`, 
    `description`, 
    `category`, 
    `type_demande`, 
    `priority`, 
    `status`, 
    `created_by`, 
    `assigned_to`, 
    `commercial_dedie`, 
    `client_name`, 
    `adresse_client`, 
    `created_at`
) VALUES (
    'TK-ST2026-0001', 
    'Exemple de ticket Support Technique', 
    'Ceci est un exemple de ticket de test.', 
    'support_technique', 
    'etude', 
    'moyenne', 
    'nouveau', 
    1,  -- ruphin (admin)
    2,  -- mikajy (coordinateur)
    'Administrateur', 
    'Client Exemple', 
    'Adresse du client exemple', 
    NOW()
);

-- ============================================
-- 12.3 Exemple de commentaire
-- ============================================

INSERT INTO `comments` (`ticket_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 'Ticket créé avec succès. En attente de traitement.', NOW());

-- ============================================
-- 13. VÉRIFICATIONS FINALES
-- ============================================

-- Vérifier les utilisateurs
SELECT id, username, email, full_name, role FROM users;

-- Vérifier les tickets
SELECT id, ticket_number, title, status FROM tickets;

-- Vérifier les notifications
SELECT COUNT(*) as total_notifications FROM notifications;

-- Vérifier les commentaires
SELECT COUNT(*) as total_comments FROM comments;

-- ============================================
-- FIN DU SCRIPT
-- ============================================
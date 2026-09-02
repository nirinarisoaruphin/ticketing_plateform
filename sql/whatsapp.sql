-- Créer la table whatsapp_queue
CREATE TABLE IF NOT EXISTS `whatsapp_queue` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT DEFAULT NULL,
    `user_id` INT DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `error` TEXT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_whatsapp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer la table whatsapp_images
CREATE TABLE IF NOT EXISTS `whatsapp_images` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `ticket_id` INT DEFAULT NULL,
    `action_type` VARCHAR(50) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_whatsapp_images_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Ajouter la colonne image_url à whatsapp_queue
ALTER TABLE `whatsapp_queue` 
ADD COLUMN `image_url` VARCHAR(255) DEFAULT NULL AFTER `message`;
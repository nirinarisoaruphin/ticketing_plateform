-- sql/password_otp.sql
-- Table pour la vérification par code (OTP) lors du changement de mot de passe
-- par un administrateur, depuis la page "Modifier l'utilisateur".
--
-- Fonctionnement :
-- 1) L'admin saisit le nouveau mot de passe et clique sur "Envoyer le code".
-- 2) Un code à 6 chiffres est généré, le nouveau mot de passe (déjà haché) et le
--    code sont stockés ici avec une expiration de 10 minutes.
-- 3) Le code est envoyé par email à l'administrateur connecté (vérification "step-up").
-- 4) L'admin saisit le code reçu -> si valide et non expiré, le mot de passe est appliqué.

CREATE TABLE IF NOT EXISTS `user_password_otps` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL COMMENT 'Utilisateur dont le mot de passe va changer',
    `requested_by` INT NOT NULL COMMENT 'Admin qui a demandé le changement',
    `code` VARCHAR(6) NOT NULL,
    `new_password_hash` VARCHAR(255) NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_otp_user` (`user_id`),
    INDEX `idx_otp_requested_by` (`requested_by`),
    CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_otp_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

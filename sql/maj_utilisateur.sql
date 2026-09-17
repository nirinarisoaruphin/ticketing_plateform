-- Désactiver l'obligation de changer le mot de passe pour tous les utilisateurs
UPDATE `users` SET `must_change_password` = 0;

-- Vérifier que c'est bien appliqué
SELECT id, username, email, must_change_password FROM users;


-- Ajouter la colonne manquante
-- Ajouter la colonne user_agent à la table login_attempts
ALTER TABLE `login_attempts` 
ADD COLUMN `user_agent` VARCHAR(255) DEFAULT NULL AFTER `ip_address`;

-- Vérifier la structure de la table
DESCRIBE `login_attempts`;

-- Ajouter la colonne last_activity_at à la table users
ALTER TABLE `users` 
ADD COLUMN `last_activity_at` DATETIME DEFAULT NULL AFTER `updated_at`;

-- Vérifier la structure de la table
DESCRIBE `users`;
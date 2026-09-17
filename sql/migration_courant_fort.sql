-- ============================================
-- Migration : renommer le rôle "charge_etude_electricite"
-- en "charge_etude_courant_fort" (Faniry)
-- ============================================

-- 1. Élargir temporairement l'ENUM pour accepter l'ancienne ET la nouvelle valeur
ALTER TABLE `users` MODIFY COLUMN `role` ENUM(
    'admin',
    'coordinateur',
    'responsable_support_technique',
    'responsable_sav',
    'responsable_travaux',
    'commercial',
    'charge_etude_electricite',
    'charge_etude_courant_fort',
    'charge_etude_courant_faible',
    'charge_etude_climatisation'
) DEFAULT 'commercial';

-- 2. Mettre à jour les comptes existants (ex: Faniry)
UPDATE `users`
SET `role` = 'charge_etude_courant_fort',
    `full_name` = REPLACE(`full_name`, 'Electricité', 'Courant Fort')
WHERE `role` = 'charge_etude_electricite';

-- 3. Restreindre l'ENUM à la valeur finale uniquement
ALTER TABLE `users` MODIFY COLUMN `role` ENUM(
    'admin',
    'coordinateur',
    'responsable_support_technique',
    'responsable_sav',
    'responsable_travaux',
    'commercial',
    'charge_etude_courant_fort',
    'charge_etude_courant_faible',
    'charge_etude_climatisation'
) DEFAULT 'commercial';

-- 4. Vérification
SELECT id, username, full_name, role FROM `users` WHERE role = 'charge_etude_courant_fort';

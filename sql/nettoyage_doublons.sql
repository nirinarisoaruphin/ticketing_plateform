-- ============================================
-- NETTOYAGE DES TICKETS EN DOUBLON (même ticket_number)
-- À EXÉCUTER AVANT sql/migration_ticket_sequences.sql
-- ============================================

-- 1) Vérifier les doublons existants (à lancer d'abord pour contrôle visuel)
SELECT ticket_number, COUNT(*) AS nb, GROUP_CONCAT(id ORDER BY id) AS ids
FROM tickets
GROUP BY ticket_number
HAVING COUNT(*) > 1;

-- 2) Renommer les doublons (on garde le PREMIER id de chaque groupe intact,
--    on renomme les suivants pour éviter toute perte de ticket).
--    ⚠️ Faites une sauvegarde de la base avant d'exécuter ceci.
UPDATE tickets t
JOIN (
    SELECT id, ticket_number,
           ROW_NUMBER() OVER (PARTITION BY ticket_number ORDER BY id) AS rn
    FROM tickets
) ranked ON ranked.id = t.id
SET t.ticket_number = CONCAT(t.ticket_number, '-DUP', ranked.rn)
WHERE ranked.rn > 1;

-- 3) Vérifier qu'il n'y a plus de doublons
SELECT ticket_number, COUNT(*) AS nb
FROM tickets
GROUP BY ticket_number
HAVING COUNT(*) > 1;

-- Une fois ce script exécuté sans résultat à l'étape 3,
-- vous pouvez exécuter sql/migration_ticket_sequences.sql en toute sécurité.

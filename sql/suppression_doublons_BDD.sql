-- ============================================
-- SUPPRESSION COMPLÈTE DES DOUBLONS
-- ============================================

-- 1. Afficher les doublons avant suppression
SELECT 'DOUBLONS AVANT SUPPRESSION' as etape;
SELECT t1.id, t1.ticket_number, t1.title, t1.created_at
FROM tickets t1
INNER JOIN (
    SELECT ticket_number, MIN(id) as min_id
    FROM tickets
    GROUP BY ticket_number
    HAVING COUNT(*) > 1
) t2 ON t1.ticket_number = t2.ticket_number AND t1.id > t2.min_id
ORDER BY t1.ticket_number, t1.id;

-- 2. Supprimer les doublons (garder le premier)
DELETE t1 FROM tickets t1
INNER JOIN tickets t2 
WHERE t1.id > t2.id 
AND t1.ticket_number = t2.ticket_number;

-- 3. Vérifier qu'il n'y a plus de doublons
SELECT 'DOUBLONS APRES SUPPRESSION' as etape;
SELECT ticket_number, COUNT(*) as count 
FROM tickets 
GROUP BY ticket_number 
HAVING COUNT(*) > 1;

-- 4. Afficher le nombre total de tickets
SELECT 'TOTAL TICKETS' as etape;
SELECT COUNT(*) as total_tickets FROM tickets;

-- 5. Ajouter une contrainte UNIQUE pour éviter les futurs doublons
ALTER TABLE `tickets` ADD UNIQUE INDEX `unique_ticket_number` (`ticket_number`);
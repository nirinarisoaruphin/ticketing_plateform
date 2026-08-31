-- 1. Vérifier si la contrainte existe déjà
SHOW INDEX FROM tickets WHERE Column_name = 'ticket_number';

-- 2. Supprimer les doublons existants pour la catégorie SAV
DELETE t1 FROM tickets t1
INNER JOIN tickets t2 
WHERE t1.id > t2.id 
AND t1.ticket_number = t2.ticket_number;

-- 3. Ajouter la contrainte UNIQUE sur ticket_number
ALTER TABLE `tickets` ADD UNIQUE INDEX `unique_ticket_number` (`ticket_number`);

-- 4. Vérifier que la contrainte est bien ajoutée
SHOW INDEX FROM tickets WHERE Column_name = 'ticket_number';
-- ============================================
-- DIAGNOSTIC : à exécuter et à me renvoyer le résultat
-- ============================================

-- 1) La table ticket_sequences a-t-elle bien sa clé primaire composite ?
SHOW CREATE TABLE ticket_sequences;

-- 2) La table tickets a-t-elle bien la contrainte UNIQUE sur ticket_number ?
SHOW CREATE TABLE tickets;

-- 3) Combien de lignes existent dans ticket_sequences pour Travaux/2026 ?
--    (on doit en trouver UNE SEULE si la clé primaire est correcte)
SELECT * FROM ticket_sequences WHERE category_prefix = 'TK-TVX' AND year = 2026;

-- 4) Les deux tickets "TK-TVX2026-0000" sont-ils bien 2 lignes distinctes
--    (id différent) dans la table tickets ?
SELECT id, ticket_number, title, created_at
FROM tickets
WHERE ticket_number = 'TK-TVX2026-0000';
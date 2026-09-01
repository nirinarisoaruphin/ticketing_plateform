-- ============================================
-- MIGRATION : Compteur atomique pour les numéros de ticket
-- Corrige les doublons de ticket_number causés par une
-- génération basée sur COUNT(*) (non fiable en concurrence)
-- ============================================

CREATE TABLE IF NOT EXISTS `ticket_sequences` (
    `category_prefix` VARCHAR(20) NOT NULL,
    `year` INT NOT NULL,
    `next_number` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`category_prefix`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ⚠️ IMPORTANT : s'assurer que la contrainte UNIQUE existe bien
-- sur ticket_number (sinon MySQL n'empêche pas les doublons).
-- Si la commande ci-dessous échoue avec "Duplicate entry", voir
-- le script de nettoyage des doublons existants avant de la relancer.
ALTER TABLE `tickets` ADD UNIQUE KEY `uniq_ticket_number` (`ticket_number`);

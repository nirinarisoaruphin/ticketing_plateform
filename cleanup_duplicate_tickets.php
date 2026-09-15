<?php
// cleanup_duplicate_tickets.php - Nettoyage unique des tickets en doublon
// À exécuter UNE SEULE FOIS dans le navigateur, puis supprimer ce fichier.
//
// Pourquoi ça arrive : la contrainte UNIQUE sur tickets.ticket_number
// (voir includes/functions.php -> ensureTicketSequenceSchema) ne peut
// pas s'appliquer tant que des doublons existent déjà en base. Ce
// script supprime les doublons (garde le plus ancien de chaque groupe)
// puis la contrainte UNIQUE s'appliquera automatiquement à la
// prochaine création de ticket, empêchant tout futur doublon.

date_default_timezone_set('Indian/Antananarivo');
require_once __DIR__ . '/config/database.php';

echo "<h1>Nettoyage des tickets en doublon</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;margin:10px 0;} td,th{border:1px solid #ccc;padding:6px 10px;text-align:left;} .ok{color:#00A02B;font-weight:bold;} .warn{color:#B5860B;font-weight:bold;}</style>";

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();

    // 1. Repérer les groupes de doublons (même ticket_number)
    $duplicateGroups = $db->fetchAll(
        "SELECT ticket_number, COUNT(*) as nb, GROUP_CONCAT(id ORDER BY id) as ids
         FROM tickets
         GROUP BY ticket_number
         HAVING COUNT(*) > 1"
    );

    if (empty($duplicateGroups)) {
        echo "<p class='ok'>Aucun doublon trouvé. La base est déjà propre.</p>";
    } else {
        echo "<p class='warn'>" . count($duplicateGroups) . " numéro(s) de ticket en doublon détecté(s) :</p>";
        echo "<table><tr><th>Numéro de ticket</th><th>Nombre</th><th>IDs</th></tr>";
        foreach ($duplicateGroups as $g) {
            echo "<tr><td>{$g['ticket_number']}</td><td>{$g['nb']}</td><td>{$g['ids']}</td></tr>";
        }
        echo "</table>";

        // 2. Supprimer les doublons : on garde uniquement le plus ancien id
        //    de chaque groupe (celui qui a le id le plus petit).
        $deleted = $pdo->exec(
            "DELETE t1 FROM tickets t1
             INNER JOIN tickets t2
             WHERE t1.id > t2.id
             AND t1.ticket_number = t2.ticket_number"
        );

        echo "<p class='ok'>{$deleted} ticket(s) en double supprimé(s) (le plus ancien de chaque groupe a été conservé).</p>";
    }

    // 3. Vérifier / ajouter la contrainte UNIQUE définitivement
    $indexExists = $pdo->query(
        "SHOW INDEX FROM tickets WHERE Key_name = 'uniq_ticket_number'"
    )->fetch();

    if (!$indexExists) {
        $pdo->exec("ALTER TABLE tickets ADD UNIQUE KEY uniq_ticket_number (ticket_number)");
        echo "<p class='ok'>Contrainte UNIQUE ajoutée sur ticket_number : plus aucun doublon ne pourra être créé, même en cas de double-clic ou de bug futur.</p>";
    } else {
        echo "<p class='ok'>La contrainte UNIQUE sur ticket_number est déjà active.</p>";
    }

    // 4. Vérification finale
    $remaining = $db->fetchAll(
        "SELECT ticket_number, COUNT(*) as nb FROM tickets GROUP BY ticket_number HAVING COUNT(*) > 1"
    );
    if (empty($remaining)) {
        echo "<h2 class='ok'>✔ Terminé : aucun doublon restant.</h2>";
    } else {
        echo "<h2 class='warn'>Attention : il reste des doublons non résolus, contactez le support.</h2>";
    }

    echo "<p><strong>Vous pouvez maintenant supprimer ce fichier (cleanup_duplicate_tickets.php) du serveur.</strong></p>";
    echo "<p><a href='index.php?page=tickets'>&larr; Retour à la liste des tickets</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

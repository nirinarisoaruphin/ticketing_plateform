<?php
// install.php - Script d'installation automatique

// Vérifier que le script est exécuté en local
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('Ce script ne peut être exécuté qu\'en environnement local.');
}

echo "<h1>Installation de la Plateforme de Ticketing</h1>";

// Charger la configuration
require_once __DIR__ . '/config/database.php';

try {
    // Vérifier la connexion à MySQL
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Connexion à MySQL réussie</p>";
    
    // Lire et exécuter le script SQL
    $sql = file_get_contents(__DIR__ . '/sql/database.sql');
    
    // Séparer les requêtes
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->query($query);
        }
    }
    
    echo "<p style='color:green'>✓ Base de données créée avec succès</p>";
    
    // Créer le dossier uploads
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0777, true);
        echo "<p style='color:green'>✓ Dossier uploads créé</p>";
    }
    
    // Créer le dossier logs
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
        echo "<p style='color:green'>✓ Dossier logs créé</p>";
    }
    
    echo "<hr>";
    echo "<h2>Installation terminée avec succès !</h2>";
    echo "<p>Vous pouvez maintenant accéder à l'application : <a href='index.php'>Accéder à l'application</a></p>";
    echo "<p><strong>Identifiants par défaut :</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin :</strong> admin@ticketing.com / admin123</li>";
    echo "<li><strong>Technicien 1 :</strong> tech1@ticketing.com / admin123</li>";
    echo "<li><strong>Technicien 2 :</strong> tech2@ticketing.com / admin123</li>";
    echo "<li><strong>Responsable :</strong> resp@ticketing.com / admin123</li>";
    echo "</ul>";
    echo "<p style='color:red;font-weight:bold;'>⚠️ Pensez à supprimer ce fichier (install.php) après installation !</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erreur : " . $e->getMessage() . "</p>";
}
?>
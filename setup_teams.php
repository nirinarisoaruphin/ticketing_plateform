<?php
// setup_teams.php - Configuration des équipes (SANS TECHNICIENS)
require_once __DIR__ . '/config/database.php';

echo "<h1>🔄 Configuration des équipes</h1>";

try {
    $db = Database::getInstance();
    
    // ============================================
    // 1. MODIFIER LA STRUCTURE DE LA TABLE
    // ============================================
    echo "<p>1. Mise à jour de la structure...</p>";
    
    $db->query("
        ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin',
            'coordinateur',
            'commercial',
            'responsable_support_technique',
            'responsable_sav',
            'responsable_travaux',
            'charge_etude_electricite',
            'charge_etude_courant_faible',
            'charge_etude_climatisation'
        ) DEFAULT 'commercial'
    ");
    echo "<p style='color:green'>✅ Structure mise à jour</p>";
    
    // ============================================
    // 2. VIDER LA TABLE
    // ============================================
    $db->query("TRUNCATE TABLE users");
    $db->query("ALTER TABLE users AUTO_INCREMENT = 1");
    echo "<p style='color:green'>✅ Table vidée</p>";
    
    // ============================================
    // 3. HASH DU MOT DE PASSE
    // ============================================
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // ============================================
    // 4. LISTE DES UTILISATEURS (SANS TECHNICIENS)
    // ============================================
    $users = [
        // 👑 ADMIN
        ['admin', 'admin@spider.mg', $hashedPassword, 'Administrateur', 'admin'],
        
        // 💼 COMMERCIAUX
        ['commercial1', 'commercial1@spider.mg', $hashedPassword, 'Commercial 1', 'commercial'],
        ['commercial2', 'commercial2@spider.mg', $hashedPassword, 'Commercial 2', 'commercial'],
        
        // 🔬 RESPONSABLE SUPPORT TECHNIQUE
        ['mahery', 'mahery@spider.mg', $hashedPassword, 'Mahery - Responsable Support Technique', 'responsable_support_technique'],
        
        // ⚡ CHARGÉ D'ÉTUDE ELECTRICITÉ
        ['faniry', 'faniry@spider.mg', $hashedPassword, 'Faniry - Chargé d\'Étude Electricité', 'charge_etude_electricite'],
        
        // 📡 CHARGÉ D'ÉTUDE COURANT FAIBLE
        ['anthony', 'anthony@spider.mg', $hashedPassword, 'Anthony - Chargé d\'Étude Courant Faible', 'charge_etude_courant_faible'],
        
        // ❄️ CHARGÉ D'ÉTUDE CLIMATISATION
        ['onintsoa', 'onintsoa@spider.mg', $hashedPassword, 'Onintsoa - Chargé d\'Étude Climatisation', 'charge_etude_climatisation'],
        
        // 🔧 RESPONSABLE SAV
        ['dina', 'dina@spider.mg', $hashedPassword, 'Dina - Responsable SAV', 'responsable_sav'],
        
        // 🏗️ RESPONSABLE TRAVAUX
        ['andry', 'andry@spider.mg', $hashedPassword, 'Andry - Responsable Travaux', 'responsable_travaux'],
        
        // 📋 COORDINATEUR
        ['ruphin', 'ruphin@spider.mg', $hashedPassword, 'Ruphin - Coordinateur', 'coordinateur']
    ];
    
    // ============================================
    // 5. INSERTION
    // ============================================
    $count = 0;
    foreach ($users as $user) {
        $db->query(
            "INSERT INTO users (username, email, password, full_name, role) 
             VALUES (?, ?, ?, ?, ?)",
            $user
        );
        $count++;
    }
    
    echo "<p style='color:green'>✅ $count utilisateurs créés</p>";
    
    // ============================================
    // 6. AFFICHAGE
    // ============================================
    $allUsers = $db->fetchAll("SELECT id, username, email, full_name, role FROM users ORDER BY id");
    
    echo "<hr>";
    echo "<h2>📋 Liste des utilisateurs</h2>";
    
    $roleLabels = [
        'admin' => '👑 Administrateur',
        'coordinateur' => '📋 Coordinateur',
        'commercial' => '💼 Commercial',
        'responsable_support_technique' => '🔬 Responsable Support Technique',
        'responsable_sav' => '🔧 Responsable SAV',
        'responsable_travaux' => '🏗️ Responsable Travaux',
        'charge_etude_electricite' => '⚡ Chargé d\'Étude Electricité',
        'charge_etude_courant_faible' => '📡 Chargé d\'Étude Courant Faible',
        'charge_etude_climatisation' => '❄️ Chargé d\'Étude Climatisation'
    ];
    
    $roleColors = [
        'admin' => '#ef4444',
        'coordinateur' => '#8b5cf6',
        'commercial' => '#3b82f6',
        'responsable_support_technique' => '#4f46e5',
        'responsable_sav' => '#ec4899',
        'responsable_travaux' => '#f59e0b',
        'charge_etude_electricite' => '#f97316',
        'charge_etude_courant_faible' => '#06b6d4',
        'charge_etude_climatisation' => '#10b981'
    ];
    
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Email</th><th>Nom</th><th>Rôle</th>";
    echo "</tr>";
    
    foreach ($allUsers as $u) {
        $label = $roleLabels[$u['role']] ?? $u['role'];
        $color = $roleColors[$u['role']] ?? '#ccc';
        
        echo "<tr>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td><strong>" . $u['username'] . "</strong></td>";
        echo "<td>" . $u['email'] . "</td>";
        echo "<td>" . $u['full_name'] . "</td>";
        echo "<td style='background:{$color};color:white;text-align:center;padding:4px 8px;border-radius:4px;'>" . $label . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>🔑 Identifiants de connexion</h3>";
    echo "<ul>";
    echo "<li><strong>Email :</strong> admin@spider.mg</li>";
    echo "<li><strong>Mot de passe :</strong> admin123</li>";
    echo "</ul>";
    echo "<p style='color:orange;'>⚠️ Tous les utilisateurs ont le même mot de passe : <strong>admin123</strong></p>";
    
    echo "<hr>";
    echo "<p><a href='index.php?page=login' style='font-size:18px;color:blue;'>➡️ Aller à la page de connexion</a></p>";
    echo "<p style='color:red;font-weight:bold;'>⚠️ Pensez à supprimer ce fichier (setup_teams.php) après utilisation !</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>
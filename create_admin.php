<?php
// create_admin.php - Script pour créer ou réinitialiser le compte admin
require_once __DIR__ . '/config/database.php';

echo "<h1>Réinitialisation du compte Admin</h1>";

try {
    $db = Database::getInstance();
    
    // Vérifier si l'admin existe déjà par email OU par username
    $existingAdmin = $db->fetch(
        "SELECT * FROM users WHERE email = 'admin@ticketing.com' OR username = 'admin'"
    );
    
    if ($existingAdmin) {
        // Mettre à jour le mot de passe
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Mettre à jour l'utilisateur existant
        $db->query(
            "UPDATE users SET 
                password = ?, 
                must_change_password = 0,
                email = 'admin@ticketing.com',
                full_name = 'Administrateur',
                role = 'admin'
            WHERE id = ?",
            [$hashedPassword, $existingAdmin['id']]
        );
        
        echo "<p style='color:green'>✅ Compte admin mis à jour avec succès !</p>";
        echo "<p><strong>ID :</strong> " . $existingAdmin['id'] . "</p>";
        echo "<p><strong>Username :</strong> " . htmlspecialchars($existingAdmin['username']) . "</p>";
        echo "<p><strong>Email :</strong> admin@ticketing.com</p>";
        echo "<p><strong>Mot de passe :</strong> admin123</p>";
        
        // Afficher tous les utilisateurs pour vérification
        $allUsers = $db->fetchAll("SELECT id, username, email, role FROM users");
        echo "<hr>";
        echo "<h3>Liste des utilisateurs :</h3>";
        echo "<ul>";
        foreach ($allUsers as $u) {
            echo "<li>#" . $u['id'] . " - " . htmlspecialchars($u['username']) . " (" . htmlspecialchars($u['email']) . ") - Rôle: " . $u['role'] . "</li>";
        }
        echo "</ul>";
        
    } else {
        // Créer un nouvel admin avec un nom d'utilisateur unique
        $username = 'admin';
        $counter = 1;
        
        // Vérifier si le username admin existe déjà
        while ($db->fetch("SELECT * FROM users WHERE username = ?", [$username])) {
            $username = 'admin' . $counter;
            $counter++;
        }
        
        $data = [
            'username' => $username,
            'email' => 'admin@ticketing.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'Administrateur',
            'role' => 'admin',
            'phone' => '',
            'must_change_password' => 0
        ];
        
        $db->insert(
            "INSERT INTO users (username, email, password, full_name, role, phone, must_change_password) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$data['username'], $data['email'], $data['password'], $data['full_name'], $data['role'], $data['phone'], $data['must_change_password']]
        );
        
        echo "<p style='color:green'>✅ Compte admin créé avec succès !</p>";
        echo "<p><strong>Username :</strong> " . $data['username'] . "</p>";
        echo "<p><strong>Email :</strong> admin@ticketing.com</p>";
        echo "<p><strong>Mot de passe :</strong> admin123</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='index.php' style='color:blue;font-weight:bold;'>➡️ Accéder à l'application</a></p>";
    echo "<p style='color:red;font-weight:bold;'>⚠️ Pensez à supprimer ce fichier après utilisation !</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
    echo "<pre>" . print_r($e, true) . "</pre>";
}
?>
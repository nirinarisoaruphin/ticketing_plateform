<?php
// models/User.php - Version sans restrictions
require_once __DIR__ . '/Database.php';

class User extends Model {
    protected $table = 'users';
    
    public function findByEmail($email) {
        return $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    public function findByUsername($username) {
        return $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }
    
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        
        if ($user['password'] === $password) {
            $this->update($user['id'], [
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);
            return $user;
        }
        
        return false;
    }
    
    /**
     * ✅ Créer un utilisateur - AUCUNE RESTRICTION SUR LE MOT DE PASSE
     */
    public function createUser($data) {
        // ✅ HASHER LE MOT DE PASSE (quel que soit son contenu)
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }
    
    public function getTechnicians() {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE role IN (
                'responsable_support_technique', 
                'responsable_sav', 
                'responsable_travaux', 
                'charge_etude_electricite', 
                'charge_etude_courant_faible', 
                'charge_etude_climatisation', 
                'coordinateur', 
                'admin'
            ) ORDER BY full_name"
        );
    }
    
    public function getResponsables() {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE role IN (
                'responsable_support_technique', 
                'responsable_sav', 
                'responsable_travaux', 
                'coordinateur', 
                'admin'
            ) ORDER BY full_name"
        );
    }
    
    public function getChargeEtude() {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE role IN (
                'charge_etude_electricite', 
                'charge_etude_courant_faible', 
                'charge_etude_climatisation'
            ) ORDER BY full_name"
        );
    }
    
    public function getUsersForAssignment() {
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE role IN (
                'charge_etude_electricite', 
                'charge_etude_courant_faible', 
                'charge_etude_climatisation',
                'responsable_support_technique'
            ) ORDER BY full_name"
        );
    }
    
    public function update($id, $data) {
        $fields = array_keys($data);
        $set = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        return $this->db->query($sql, $params);
    }
    
    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
}
?>
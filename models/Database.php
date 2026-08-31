<?php
// models/Database.php
require_once __DIR__ . '/../config/database.php';

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findAll() {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY id DESC");
    }
    
    public function findById($id) {
        return $this->db->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        return $this->db->insert($sql, array_values($data));
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
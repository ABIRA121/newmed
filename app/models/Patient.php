<?php
/**
 * Patient model for patient-specific operations
 */

class Patient {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get patient profile by user ID
    public function getProfile($userId) {
        $sql = "SELECT p.*, u.email, u.full_name, u.phone, u.created_at 
                FROM patients p 
                JOIN users u ON p.user_id = u.id 
                WHERE u.id = ? AND u.is_active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    // Create or update patient profile
    public function saveProfile($userId, $data) {
        // Check if profile exists
        $sql = "SELECT id FROM patients WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing profile
            $setParts = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }
            
            $params[] = $userId;
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE patients SET $setClause WHERE user_id = ?";
        } else {
            // Create new profile
            $data['user_id'] = $userId;
            $columns = implode(', ', array_keys($data));
            $placeholders = str_repeat('?, ', count($data) - 1) . '?';
            
            $sql = "INSERT INTO patients ($columns) VALUES ($placeholders)";
            $params = array_values($data);
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Get all patients with user info
    public function getAllPatients() {
        $sql = "SELECT p.*, u.email, u.full_name, u.phone, u.created_at, u.last_login 
                FROM patients p 
                JOIN users u ON p.user_id = u.id 
                WHERE u.role = 'patient' AND u.is_active = TRUE 
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Search patients
    public function searchPatients($searchTerm) {
        $sql = "SELECT p.*, u.email, u.full_name, u.phone 
                FROM patients p 
                JOIN users u ON p.user_id = u.id 
                WHERE (u.full_name LIKE ? OR u.email LIKE ? OR p.emergency_contact LIKE ?) 
                AND u.is_active = TRUE 
                ORDER BY u.full_name";
        
        $searchParam = "%$searchTerm%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
        return $stmt->fetchAll();
    }
}
?>
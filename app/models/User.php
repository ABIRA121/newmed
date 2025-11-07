<?php
/**
 * User model for authentication and user management
 */

class User {
    private $db;
    public $id;
    public $role;
    public $email;
    public $password_hash; // Added missing property
    public $full_name;
    public $phone;
    public $created_at;
    public $last_login;
    public $is_active;
    public $password_change_required;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Find user by ID
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 1) {
            // Instead of FETCH_INTO, use fetch to populate properties manually
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->populateFromArray($userData);
            return $this;
        }
        return false;
    }

    // Find user by email
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 1) {
            // Instead of FETCH_INTO, use fetch to populate properties manually
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->populateFromArray($userData);
            return $this;
        }
        return false;
    }

    // Populate object properties from array
    private function populateFromArray($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Verify password
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    // Create new user
    public function create($data) {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        $sql = "INSERT INTO users (role, email, password_hash, full_name, phone) 
                VALUES (:role, :email, :password_hash, :full_name, :phone)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Update last login
    public function updateLastLogin() {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$this->id]);
    }

    // Check if email exists
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    // Get all users by role
    public function getUsersByRole($role) {
        $sql = "SELECT * FROM users WHERE role = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    // Update user
    public function updateUser($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['password']);
        
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE users SET $setClause WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Check login attempts
    public function checkLoginAttempts($email, $ipAddress) {
        $sql = "SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE (email = ? OR ip_address = ?) 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL " . LOGIN_LOCKOUT_TIME . " SECOND)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $ipAddress]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }

    // Record login attempt
    public function recordLoginAttempt($email, $ipAddress) {
        $sql = "INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$email, $ipAddress]);
    }

    // Get user by ID without active check (for admin operations)
    public function findByIdForAdmin($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 1) {
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->populateFromArray($userData);
            return $this;
        }
        return false;
    }

    // Get all users (for admin)
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Generate random password
    public function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
?>
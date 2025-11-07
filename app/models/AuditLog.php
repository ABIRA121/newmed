<?php
/**
 * AuditLog model for tracking user actions
 */

class AuditLog {
    /** @var PDO $db Database connection */
    private $db;

    public function __construct() {
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }

    /**
     * Log an action to the audit log
     * 
     * @param int|null $userId The user ID (null for anonymous actions)
     * @param string $action The action performed
     * @param string|null $ipAddress The IP address
     * @param string|null $userAgent The user agent string
     * @return bool Success status
     */
    public function log($userId, $action, $ipAddress = null, $userAgent = null) {
        // Allow NULL user IDs for system/anonymous actions
        $logUserId = $userId !== null ? $userId : null;
        
        $sql = "INSERT INTO audit_logs (user_id, action, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$logUserId, $action, $ipAddress, $userAgent]);
    }

    /**
     * Get logs with pagination
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param array $filters Search filters
     * @return array Log entries
     */
    public function getLogs($page = 1, $perPage = 50, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT al.*, u.full_name, u.email, u.role 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action LIKE ?";
            $params[] = "%{$filters['action']}%";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get total count for pagination
     * 
     * @param array $filters Search filters
     * @return int Total count
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM audit_logs al WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action LIKE ?";
            $params[] = "%{$filters['action']}%";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }

    /**
     * Get recent activities
     * 
     * @param int $limit Number of recent activities to return
     * @return array Recent activities
     */
    public function getRecentActivities($limit = 10) {
        $sql = "SELECT al.*, u.full_name, u.role 
                FROM audit_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Clean old audit logs (for maintenance)
     * 
     * @param int $daysOld Delete logs older than this many days
     * @return int Number of deleted logs
     */
    public function cleanOldLogs($daysOld = 365) {
        $sql = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    }
}
?>
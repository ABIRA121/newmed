<?php
/**
 * Appointment model for managing medical appointments
 */

class Appointment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create new appointment
    public function create($data) {
        $sql = "INSERT INTO appointments (patient_id, staff_id, scheduled_at, status, notes) 
                VALUES (:patient_id, :staff_id, :scheduled_at, :status, :notes)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    // Get appointment by ID
    public function findById($id) {
        $sql = "SELECT a.*, 
                p_user.full_name as patient_name, 
                s_user.full_name as staff_name,
                p_user.email as patient_email,
                s_user.email as staff_email
                FROM appointments a
                JOIN users p_user ON a.patient_id = p_user.id
                JOIN users s_user ON a.staff_id = s_user.id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Get appointments for patient
    public function getPatientAppointments($patientId, $status = null) {
        $sql = "SELECT a.*, s_user.full_name as staff_name, s_user.phone as staff_phone
                FROM appointments a
                JOIN users s_user ON a.staff_id = s_user.id
                WHERE a.patient_id = ?";
        
        $params = [$patientId];
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY a.scheduled_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Get appointments for staff
    public function getStaffAppointments($staffId, $status = null) {
        $sql = "SELECT a.*, p_user.full_name as patient_name, p_user.phone as patient_phone,
                pat.dob, pat.gender
                FROM appointments a
                JOIN users p_user ON a.patient_id = p_user.id
                LEFT JOIN patients pat ON p_user.id = pat.user_id
                WHERE a.staff_id = ?";
        
        $params = [$staffId];
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY a.scheduled_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Get all appointments (for admin)
    public function getAllAppointments($filters = []) {
        $sql = "SELECT a.*, 
                p_user.full_name as patient_name, 
                s_user.full_name as staff_name,
                p_user.email as patient_email
                FROM appointments a
                JOIN users p_user ON a.patient_id = p_user.id
                JOIN users s_user ON a.staff_id = s_user.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['staff_id'])) {
            $sql .= " AND a.staff_id = ?";
            $params[] = $filters['staff_id'];
        }
        
        if (!empty($filters['patient_id'])) {
            $sql .= " AND a.patient_id = ?";
            $params[] = $filters['patient_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(a.scheduled_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(a.scheduled_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY a.scheduled_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Update appointment status
    public function updateStatus($id, $status, $notes = null) {
        $sql = "UPDATE appointments SET status = ?, notes = COALESCE(?, notes) WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $notes, $id]);
    }

    // Reschedule appointment
    public function reschedule($id, $newDateTime) {
        $sql = "UPDATE appointments SET scheduled_at = ?, status = 'confirmed' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newDateTime, $id]);
    }

    // Check for scheduling conflicts
    public function hasConflict($staffId, $scheduledAt, $excludeAppointmentId = null) {
        $sql = "SELECT id FROM appointments 
                WHERE staff_id = ? 
                AND scheduled_at = ? 
                AND status IN ('pending', 'confirmed')";
        
        $params = [$staffId, $scheduledAt];
        
        if ($excludeAppointmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    // Get upcoming appointments
    public function getUpcomingAppointments($limit = 20) {
        $sql = "SELECT a.*, 
                p_user.full_name as patient_name,
                s_user.full_name as staff_name
                FROM appointments a
                JOIN users p_user ON a.patient_id = p_user.id
                JOIN users s_user ON a.staff_id = s_user.id
                WHERE a.scheduled_at >= NOW()
                AND a.status IN ('pending', 'confirmed')
                ORDER BY a.scheduled_at ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // Get appointment statistics
    public function getStats($period = 'month') {
        $dateFormat = $period === 'day' ? '%Y-%m-%d' : '%Y-%m';
        
        $sql = "SELECT 
                DATE_FORMAT(scheduled_at, '$dateFormat') as period,
                status,
                COUNT(*) as count
                FROM appointments 
                WHERE scheduled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY period, status
                ORDER BY period DESC, status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
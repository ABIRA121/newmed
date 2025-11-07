<?php
/**
 * Authentication and authorization system
 */

class Auth {
    private $userModel;
    private $session;
    private $auditLog;

    public function __construct() {
        $this->userModel = new User();
        $this->session = new Session();
        $this->auditLog = new AuditLog();
    }

    // Attempt login
    public function login($email, $password, $ipAddress = null, $userAgent = null) {
        // Check for too many login attempts
        if ($this->userModel->checkLoginAttempts($email, $ipAddress)) {
            $this->auditLog->log(null, 'login_attempt_blocked', $ipAddress, $userAgent);
            throw new Exception('Too many login attempts. Please try again later.');
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $this->userModel->recordLoginAttempt($email, $ipAddress);
            $this->auditLog->log(null, 'login_failed_invalid_email', $ipAddress, $userAgent);
            throw new Exception('Invalid email or password.');
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            $this->userModel->recordLoginAttempt($email, $ipAddress);
            $this->auditLog->log($user->id, 'login_failed_wrong_password', $ipAddress, $userAgent);
            throw new Exception('Invalid email or password.');
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->auditLog->log($user->id, 'login_failed_inactive', $ipAddress, $userAgent);
            throw new Exception('Account is deactivated. Please contact administrator.');
        }

        // Login successful
        $this->session->login($user);
        $user->updateLastLogin();
        
        // Clear login attempts for this email/IP
        $this->clearLoginAttempts($email, $ipAddress);
        
        $this->auditLog->log($user->id, 'login_success', $ipAddress, $userAgent);
        return true;
    }

    // Logout
    public function logout() {
        $userId = $this->session->get('user_id');
        $this->auditLog->log($userId, 'logout');
        $this->session->logout();
    }

    // Check if user is authenticated
    public function isAuthenticated() {
        return $this->session->isLoggedIn();
    }

    // Check if user has specific role
    public function hasRole($role) {
        return $this->session->getUserRole() === $role;
    }

    // Require authentication
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->session->setFlash('error', 'Please login to access this page.');
            header('Location: login.php');
            exit;
        }
    }

    // Require specific role
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            $this->session->setFlash('error', 'Access denied. Insufficient permissions.');
            header('Location: dashboard.php');
            exit;
        }
    }

    // Require one of multiple roles
    public function requireAnyRole($roles) {
        $this->requireAuth();
        
        $userRole = $this->session->getUserRole();
        if (!in_array($userRole, $roles)) {
            $this->session->setFlash('error', 'Access denied. Insufficient permissions.');
            header('Location: dashboard.php');
            exit;
        }
    }

    // Get current user
    public function getCurrentUser() {
        $userId = $this->session->get('user_id');
        if ($userId) {
            return $this->userModel->findById($userId);
        }
        return null;
    }

    // Clear login attempts
    private function clearLoginAttempts($email, $ipAddress) {
        $db = Database::getInstance()->getConnection();
        $sql = "DELETE FROM login_attempts WHERE email = ? OR ip_address = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email, $ipAddress]);
    }
}
?>
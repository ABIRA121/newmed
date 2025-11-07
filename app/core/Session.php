<?php
/**
 * Session management with security features
 */

class Session {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Enhanced session configuration
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            // Session name to avoid conflicts
            session_name('MEDPORTAL_SESSION');
            
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
            
            session_start();
        }
        
        // Initialize session array if not set
        if (!isset($_SESSION['initialized'])) {
            $_SESSION['initialized'] = true;
            $_SESSION['created'] = time();
        }
        
        // Regenerate session ID periodically for security
        $this->regenerateIfNeeded();
    }

    private function regenerateIfNeeded() {
        if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
            error_log("Session regenerated");
        }
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function remove($key) {
        unset($_SESSION[$key]);
    }

    public function destroy() {
        session_destroy();
        session_unset();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    public function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }

    public function getFlash($type) {
        $message = $_SESSION['flash'][$type] ?? '';
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    public function hasFlash($type) {
        return !empty($_SESSION['flash'][$type]);
    }

    // Check if user is logged in and session is valid
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            error_log("Session expired for user: " . $_SESSION['user_id']);
            $this->destroy();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }

    // Get current user role
    public function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }

    // Check if user has specific role
    public function hasRole($role) {
        return $this->getUserRole() === $role;
    }

    // Login user
    public function login($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->full_name;
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        $_SESSION['login_time'] = time();
        
        error_log("User logged in: " . $user->email . " (ID: " . $user->id . ")");
    }

    // Logout user
    public function logout() {
        error_log("User logging out: " . ($_SESSION['user_email'] ?? 'unknown'));
        $this->destroy();
    }
}
?>
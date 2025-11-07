<?php
/**
 * CSRF protection implementation - Simplified version
 */

class CSRF {
    private $session;

    public function __construct() {
        $this->session = new Session();
    }

    // Generate and store CSRF token
    public function generateToken() {
        // Clear any existing token first
        $this->session->remove('csrf_token');
        $this->session->remove('csrf_token_time');
        
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        $this->session->set('csrf_token_time', time());
        
        error_log("CSRF: Generated new token: " . substr($token, 0, 10) . "...");
        return $token;
    }

    // Validate CSRF token
    public function validateToken($token) {
        $storedToken = $this->session->get('csrf_token');
        $tokenTime = $this->session->get('csrf_token_time');
        
        error_log("CSRF Validation:");
        error_log("  - Stored token: " . ($storedToken ? "exists" : "missing"));
        error_log("  - Provided token: " . ($token ? "exists" : "missing"));
        error_log("  - Token time: " . ($tokenTime ? date('Y-m-d H:i:s', $tokenTime) : "missing"));
        
        if (!$storedToken || !$tokenTime) {
            error_log("CSRF FAIL: Missing stored token or time");
            return false;
        }

        // Token expires after 1 hour
        if (time() - $tokenTime > 3600) {
            error_log("CSRF FAIL: Token expired");
            $this->session->remove('csrf_token');
            $this->session->remove('csrf_token_time');
            return false;
        }

        // Compare tokens
        if (!hash_equals($storedToken, $token)) {
            error_log("CSRF FAIL: Token mismatch");
            error_log("  Stored: " . substr($storedToken, 0, 10) . "...");
            error_log("  Provided: " . substr($token, 0, 10) . "...");
            return false;
        }

        error_log("CSRF SUCCESS: Token validated");
        
        // Don't remove the token immediately - allow multiple submissions during same session
        // Instead, we'll rely on the expiration time
        return true;
    }

    // Get token for form
    public function getToken() {
        $token = $this->session->get('csrf_token');
        if (!$token) {
            $token = $this->generateToken();
        }
        return $token;
    }

    // Add CSRF token to form
    public function tokenField() {
        $token = $this->getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // Clear CSRF token (for logout)
    public function clearToken() {
        $this->session->remove('csrf_token');
        $this->session->remove('csrf_token_time');
    }
}
?>
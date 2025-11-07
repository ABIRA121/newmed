<?php
// Start output buffering to ensure no output before headers
ob_start();

require_once '../config/config.php';
require_once '../app/models/Database.php';
require_once '../app/models/User.php';
require_once '../app/models/AuditLog.php';
require_once '../app/core/Session.php';
require_once '../app/core/Auth.php';
require_once '../app/core/CSRF.php';
require_once '../app/utils/security_utils.php';

// Initialize with error handling
try {
    $session = new Session();
    $auth = new Auth();
    $csrf = new CSRF();
} catch (Exception $e) {
    error_log("Initialization error: " . $e->getMessage());
    die("System initialization error. Please try again.");
}

// Redirect if already logged in
if ($auth->isAuthenticated()) {
    $user = $auth->getCurrentUser();
    if ($user) {
        header('Location: ' . getDashboardUrl($user->role));
        exit;
    }
}

$error = '';
$prefillEmail = $_POST['email'] ?? '';
$selectedRole = $_POST['role'] ?? '';
$csrfToken = $csrf->getToken();

// Handle role selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_role'])) {
    $selectedRole = $_POST['role'] ?? '';
    if ($selectedRole === 'staff') {
        header('Location: staff_login.php');
        exit;
    }
}

// Handle actual login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginResult = SecurityUtils::handleLoginPost($auth, $csrf);
    $error = $loginResult['error'];
    $prefillEmail = $loginResult['email'];
    $selectedRole = $_POST['role'] ?? '';

    if ($loginResult['success']) {
        $user = $auth->getCurrentUser();
        if ($user) {
            // Verify role matches selection
            if ($selectedRole && $user->role !== $selectedRole) {
                $error = 'Selected role does not match your account. Please select the correct role.';
            } else {
                header('Location: ' . getDashboardUrl($user->role));
                exit;
            }
        }
    }

    if ($loginResult['regenerate_csrf']) {
        $csrfToken = $csrf->generateToken();
    }
}

// Helper function to get dashboard URL
function getDashboardUrl($role) {
    switch ($role) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'accountant':
            return 'staff/accountant/dashboard.php';
        case 'doctor':
            return 'staff/doctor/dashboard.php';
        case 'pharmacy':
            return 'staff/pharmacy/dashboard.php';
        case 'nurse':
            return 'staff/nurse/dashboard.php';
        case 'patient':
            return 'patient/dashboard.php';
        default:
            return 'dashboard.php';
    }
}

// Clear output buffer
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedPortal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-disable-utilities="true">
    <header class="page-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <button type="button" class="header-action" data-nav-back>
                    <span class="icon">↩</span>
                    <span>Back</span>
                </button>
                <a href="index.php" class="header-action" data-nav-home>
                    <span class="icon">🏠</span>
                    <span>Home</span>
                </a>
                <button type="button" class="header-action theme-toggle" data-theme-toggle>
                    Toggle Theme
                </button>
            </div>
        </div>
    </header>
    <main style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        <div style="max-width: 500px; margin: 0 auto;">
            <h1 style="text-align: center; margin-bottom: 2rem;">Login to MedPortal</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Role Selection Form -->
            <?php if (!$selectedRole || $selectedRole === 'staff'): ?>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2 class="card-title">Select Your Role</h2>
                    </div>
                    <form method="POST" action="login.php" class="card-body" style="display: grid; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div>
                            <label class="form-label">I am logging in as:</label>
                            <div style="display: grid; gap: 0.75rem; margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; transition: border-color 0.3s;">
                                    <input type="radio" name="role" value="patient" <?= $selectedRole === 'patient' ? 'checked' : '' ?> required>
                                    <span><strong>Patient</strong> - Access my medical records and appointments</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; transition: border-color 0.3s;">
                                    <input type="radio" name="role" value="staff" <?= $selectedRole === 'staff' ? 'checked' : '' ?> required>
                                    <span><strong>Staff</strong> - Doctor, Nurse, Pharmacy, or Accountant</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; transition: border-color 0.3s;">
                                    <input type="radio" name="role" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?> required>
                                    <span><strong>Administrator</strong> - System management</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="select_role" class="btn btn-primary">Continue</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Login Form (shown after role selection) -->
            <?php if ($selectedRole && $selectedRole !== 'staff'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Login as <?= ucfirst($selectedRole) ?></h2>
                    </div>
                    <form method="POST" action="login.php" class="card-body" style="display: grid; gap: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="role" value="<?= htmlspecialchars($selectedRole) ?>">
                        
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($prefillEmail) ?>">
                        </div>
                        
                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                            <a href="login.php" class="btn btn-secondary">Change Role</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register as Patient</a>
            </p>
        </div>
    </main>

    <footer style="padding: 2rem 0; text-align: center; margin-top: 3rem; border-top: 1px solid var(--border-color);">
        <p>&copy; <?= date('Y') ?> MedPortal - Medical Management System</p>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>

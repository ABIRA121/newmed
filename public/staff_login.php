<?php
ob_start();

require_once '../config/config.php';
require_once '../app/models/Database.php';
require_once '../app/models/User.php';
require_once '../app/models/AuditLog.php';
require_once '../app/core/Session.php';
require_once '../app/core/Auth.php';
require_once '../app/core/CSRF.php';
require_once '../app/utils/security_utils.php';

try {
    $session = new Session();
    $auth = new Auth();
    $csrf = new CSRF();
} catch (Exception $e) {
    error_log("Initialization error: " . $e->getMessage());
    die("System initialization error. Please try again.");
}

if ($auth->isAuthenticated()) {
    $user = $auth->getCurrentUser();
    if ($user && in_array($user->role, ['accountant', 'doctor', 'pharmacy', 'nurse'])) {
        header('Location: ' . getStaffDashboardUrl($user->role));
        exit;
    }
}

$error = '';
$prefillEmail = $_POST['email'] ?? '';
$selectedStaffRole = $_POST['staff_role'] ?? '';
$csrfToken = $csrf->getToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginResult = SecurityUtils::handleLoginPost($auth, $csrf);
    $error = $loginResult['error'];
    $prefillEmail = $loginResult['email'];
    $selectedStaffRole = $_POST['staff_role'] ?? '';

    if ($loginResult['success']) {
        $user = $auth->getCurrentUser();
        if ($user) {
            if ($selectedStaffRole && $user->role !== $selectedStaffRole) {
                $error = 'Selected staff role does not match your account. Please select the correct role.';
            } elseif (!in_array($user->role, ['accountant', 'doctor', 'pharmacy', 'nurse'])) {
                $error = 'Your account is not authorized for staff access.';
            } else {
                header('Location: ' . getStaffDashboardUrl($user->role));
                exit;
            }
        }
    }

    if ($loginResult['regenerate_csrf']) {
        $csrfToken = $csrf->generateToken();
    }
}

function getStaffDashboardUrl($role) {
    switch ($role) {
        case 'accountant':
            return 'staff/accountant/dashboard.php';
        case 'doctor':
            return 'staff/doctor/dashboard.php';
        case 'pharmacy':
            return 'staff/pharmacy/dashboard.php';
        case 'nurse':
            return 'staff/nurse/dashboard.php';
        default:
            return 'login.php';
    }
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - MedPortal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-disable-utilities="true">
    <header class="page-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="login.php" class="header-action">
                    <span class="icon">↩</span>
                    <span>Back to Login</span>
                </a>
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
            <h1 style="text-align: center; margin-bottom: 2rem;">Staff Login</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Select Your Staff Role</h2>
                </div>
                <form method="POST" action="staff_login.php" class="card-body" style="display: grid; gap: 1rem;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    
                    <div>
                        <label class="form-label">I am a:</label>
                        <div style="display: grid; gap: 0.75rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer;">
                                <input type="radio" name="staff_role" value="doctor" <?= $selectedStaffRole === 'doctor' ? 'checked' : '' ?> required>
                                <span><strong>Doctor</strong> - Consultations, prescriptions, examinations</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer;">
                                <input type="radio" name="staff_role" value="nurse" <?= $selectedStaffRole === 'nurse' ? 'checked' : '' ?> required>
                                <span><strong>Nurse</strong> - Patient care and assistance</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer;">
                                <input type="radio" name="staff_role" value="pharmacy" <?= $selectedStaffRole === 'pharmacy' ? 'checked' : '' ?> required>
                                <span><strong>Pharmacy</strong> - Medication dispensation</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem; cursor: pointer;">
                                <input type="radio" name="staff_role" value="accountant" <?= $selectedStaffRole === 'accountant' ? 'checked' : '' ?> required>
                                <span><strong>Accountant</strong> - Billing and receipts</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?= htmlspecialchars($prefillEmail) ?>">
                    </div>
                    
                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </form>
            </div>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--primary-color);">← Back to main login</a>
            </p>
        </div>
    </main>

    <footer style="padding: 2rem 0; text-align: center; margin-top: 3rem; border-top: 1px solid var(--border-color);">
        <p>&copy; <?= date('Y') ?> MedPortal - Medical Management System</p>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>


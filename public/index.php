<?php
// Include configuration first
require_once '../config/config.php';

// Then include the autoloader
require_once '../app/autoload.php';

// Manually include critical classes that might be needed immediately
require_once '../app/models/Database.php';
require_once '../app/models/User.php';
require_once '../app/models/AuditLog.php';
require_once '../app/core/Session.php';
require_once '../app/core/Auth.php';

// Initialize session and auth
try {
    $session = new Session();
    $auth = new Auth();
} catch (Exception $e) {
    error_log("Initialization error: " . $e->getMessage());
    // Show a simple error page
    die("System initialization error. Please check the logs.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedPortal - Medical Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-disable-utilities="true">
    <?php 
    // Check if header file exists, if not show basic header
    $headerFile = '../app/views/layouts/header.php';
    if (file_exists($headerFile)) {
        try {
            include $headerFile;
        } catch (Exception $e) {
            error_log("Header include error: " . $e->getMessage());
            echo '<header style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
                        <a href="index.php" style="font-size: 1.5rem; font-weight: bold; color: #007bff; text-decoration: none;">MedPortal</a>
                        <nav>
                            <a href="login.php" style="margin-left: 1rem; text-decoration: none; color: #333;">Login</a>
                            <a href="register.php" style="margin-left: 1rem; text-decoration: none; color: #333;">Register</a>
                        </nav>
                    </div>
                  </header>';
        }
    } else {
        echo '<header style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
                    <a href="index.php" style="font-size: 1.5rem; font-weight: bold; color: #007bff; text-decoration: none;">MedPortal</a>
                    <nav>
                        <a href="login.php" style="margin-left: 1rem; text-decoration: none; color: #333;">Login</a>
                        <a href="register.php" style="margin-left: 1rem; text-decoration: none; color: #333;">Register</a>
                    </nav>
                </div>
              </header>';
    }
    ?>
    
    <main class="main-content">
        <section class="hero">
            <div class="container">
                <h1>Welcome to MedPortal</h1>
                <p>Your comprehensive medical management solution</p>
                <div class="hero-buttons">
                    <?php if (!$auth->isAuthenticated()): ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                        <a href="register.php" class="btn btn-secondary">Patient Registration</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Our Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>For Patients</h3>
                        <p>Book appointments, view medical history, and manage your healthcare online</p>
                    </div>
                    <div class="feature-card">
                        <h3>For Staff</h3>
                        <p>Manage appointments, update patient records, and streamline workflows</p>
                    </div>
                    <div class="feature-card">
                        <h3>For Administrators</h3>
                        <p>Oversee system operations, manage users, and generate reports</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php 
    // Check if footer file exists
    $footerFile = '../app/views/layouts/footer.php';
    if (file_exists($footerFile)) {
        include $footerFile;
    } else {
        echo '<footer style="padding: 2rem 0; text-align: center; margin-top: 3rem; border-top: 1px solid #dee2e6;">
                <p>&copy; ' . date('Y') . ' MedPortal - Medical Management System</p>
              </footer>';
    }
    ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
<?php
// Include configuration and autoloader
require_once '../config/config.php';
require_once '../app/autoload.php';

$session = new Session();
$auth = new Auth();

// Require authentication
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MedPortal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="font-size: 1.5rem; font-weight: bold; color: #007bff; text-decoration: none;">MedPortal</a>
            <nav>
                <span>Welcome, <?= htmlspecialchars($currentUser->full_name) ?> (<?= $currentUser->role ?>)</span>
                <a href="logout.php" style="margin-left: 1rem;">Logout</a>
            </nav>
        </div>
    </header>
    
    <main style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        <h1>Dashboard</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
            <div style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 1.5rem; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold;">1</div>
                <div>Appointments</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 1.5rem; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold;">1</div>
                <div>Messages</div>
            </div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>Quick Actions</h2>
            <div style="margin-top: 1rem;">
                <?php if ($auth->hasRole('admin')): ?>
                    <a href="admin/users.php" style="display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Manage Users</a>
                    <a href="admin/logs.php" style="display: inline-block; padding: 0.5rem 1rem; background: #6c757d; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">View Logs</a>
                <?php elseif ($auth->hasRole('doctor')): ?>
                    <a href="staff/doctor/dashboard.php" style="display: inline-block; padding: 0.5rem 1rem; background: #0d6efd; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Doctor Portal</a>
                <?php elseif ($auth->hasRole('accountant')): ?>
                    <a href="staff/accountant/dashboard.php" style="display: inline-block; padding: 0.5rem 1rem; background: #6610f2; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Accountant Portal</a>
                <?php elseif ($auth->hasRole('pharmacy')): ?>
                    <a href="staff/pharmacy/dashboard.php" style="display: inline-block; padding: 0.5rem 1rem; background: #20c997; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Pharmacy Portal</a>
                <?php elseif ($auth->hasRole('nurse')): ?>
                    <a href="staff/nurse/dashboard.php" style="display: inline-block; padding: 0.5rem 1rem; background: #fd7e14; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Nurse Portal</a>
                <?php elseif ($auth->hasRole('patient')): ?>
                    <a href="patient/appointments.php" style="display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">My Appointments</a>
                    <a href="patient/profile.php" style="display: inline-block; padding: 0.5rem 1rem; background: #28a745; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">My Profile</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
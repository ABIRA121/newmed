<?php
require_once '../../config/config.php';
require_once '../../app/models/Database.php';
require_once '../../app/models/User.php';
require_once '../../app/models/Patient.php';
require_once '../../app/models/AuditLog.php';
require_once '../../app/core/Session.php';
require_once '../../app/core/Auth.php';

$session = new Session();
$auth = new Auth();

// Require patient role
$auth->requireRole('patient');

$currentUser = $auth->getCurrentUser();
$patientModel = new Patient();
$patientProfile = $patientModel->getProfile($currentUser->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MedPortal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header style="padding: 1rem; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <a href="../index.php" style="font-size: 1.5rem; font-weight: bold; color: #007bff; text-decoration: none;">MedPortal</a>
            <nav>
                <span>Welcome, <?= htmlspecialchars($currentUser->full_name) ?> (Patient)</span>
                <a href="../logout.php" style="margin-left: 1rem; text-decoration: none; color: #333;">Logout</a>
            </nav>
        </div>
    </header>
    
    <main style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        <h1>Patient Dashboard</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
            <div style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 1.5rem; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold;">0</div>
                <div>Upcoming Appointments</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 1.5rem; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold;">0</div>
                <div>Past Appointments</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #6f42c1, #563d7c); color: white; padding: 1.5rem; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: bold;">0</div>
                <div>Prescriptions</div>
            </div>
        </div>
        
        <div style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
            <h2>Quick Actions</h2>
            <div style="margin-top: 1rem;">
                <a href="appointments.php" style="display: inline-block; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Book Appointment</a>
                <a href="profile.php" style="display: inline-block; padding: 0.5rem 1rem; background: #28a745; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">My Profile</a>
                <a href="medical-history.php" style="display: inline-block; padding: 0.5rem 1rem; background: #6c757d; color: white; text-decoration: none; border-radius: 0.25rem; margin-right: 0.5rem;">Medical History</a>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>Upcoming Appointments</h3>
                <p>No upcoming appointments.</p>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3>Medical Summary</h3>
                <?php if ($patientProfile): ?>
                    <p><strong>Date of Birth:</strong> <?= htmlspecialchars($patientProfile['dob']) ?></p>
                    <p><strong>Gender:</strong> <?= htmlspecialchars($patientProfile['gender']) ?></p>
                    <?php if ($patientProfile['medical_notes']): ?>
                        <p><strong>Medical Notes:</strong> <?= htmlspecialchars(substr($patientProfile['medical_notes'], 0, 100)) ?>...</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Complete your profile to see medical summary.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer style="padding: 2rem 0; text-align: center; margin-top: 3rem; border-top: 1px solid #dee2e6;">
        <p>&copy; <?= date('Y') ?> MedPortal - Medical Management System</p>
    </footer>
</body>
</html>
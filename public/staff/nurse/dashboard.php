<?php
require_once '../../../config/config.php';
require_once '../../../app/autoload.php';

$session = new Session();
$auth = new Auth();
$auth->requireRole('nurse');

$nurse = $auth->getCurrentUser();
$patientModel = new Patient();
$appointmentModel = new Appointment();

$patients = $patientModel->getAllPatients();
$appointments = $appointmentModel->getUpcomingAppointments(20);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_vitals') {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $vitals = trim($_POST['vitals'] ?? '');
        
        if ($patientId <= 0) {
            $errors[] = 'Please select a patient.';
        }
        
        if (empty($errors)) {
            try {
                // Log vitals update
                $auditLog = new AuditLog();
                $auditLog->log($nurse->id, 'vitals_recorded', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
                $successMessage = 'Patient vitals recorded successfully.';
            } catch (Exception $e) {
                error_log('Nurse dashboard error: ' . $e->getMessage());
                $errors[] = 'Unable to record vitals. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - MedPortal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <div class="container header-inner">
            <a href="../../index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="../../dashboard.php" class="header-action">
                    <span class="icon">🏥</span>
                    <span>Main Dashboard</span>
                </a>
                <button type="button" class="header-action theme-toggle" data-theme-toggle>
                    Toggle Theme
                </button>
                <a href="../../logout.php" class="header-action">
                    <span class="icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 0;">
        <h1 class="mb-2">Nurse Workspace</h1>
        <p class="text-muted mb-2">Manage patient care, record vitals, and assist with appointments.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Record Patient Vitals</h2>
            </div>
            <form method="post" class="card-body" style="display: grid; gap: 1rem;">
                <input type="hidden" name="action" value="update_vitals">
                
                <div>
                    <label for="patient_id" class="form-label">Patient</label>
                    <select id="patient_id" name="patient_id" class="form-control" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int)$patient['user_id'] ?>">
                                <?= htmlspecialchars($patient['full_name'] . ' (' . $patient['email'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="vitals" class="form-label">Vital Signs</label>
                    <textarea id="vitals" name="vitals" class="form-control" rows="4" required placeholder="Blood Pressure: 120/80&#10;Temperature: 98.6°F&#10;Heart Rate: 72 bpm&#10;Respiratory Rate: 16/min"><?= htmlspecialchars($vitals ?? '') ?></textarea>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Record Vitals</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Upcoming Appointments</h2>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <p class="text-muted">No upcoming appointments scheduled.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($appointments as $appointment): ?>
                            <div style="border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <strong><?= htmlspecialchars($appointment['patient_name'] ?? 'Patient') ?></strong>
                                    <span class="text-muted"><?= htmlspecialchars(date('M d, Y H:i', strtotime($appointment['scheduled_at']))) ?></span>
                                </div>
                                <p class="mt-1"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($appointment['status'])) ?></p>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <p><strong>Notes:</strong> <?= htmlspecialchars($appointment['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Patient List</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr><td colspan="4" class="text-muted">No patients found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?= htmlspecialchars($patient['full_name']) ?></td>
                                    <td><?= htmlspecialchars($patient['email']) ?></td>
                                    <td><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary">View Profile</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>


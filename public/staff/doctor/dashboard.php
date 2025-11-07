<?php
require_once '../../config/config.php';
require_once '../../app/autoload.php';

$session = new Session();
$auth = new Auth();
$auth->requireRole('doctor');

$doctor = $auth->getCurrentUser();
$patientModel = new Patient();
$prescriptionModel = new Prescription();

$patients = $patientModel->getAllPatients();
$prescriptions = $prescriptionModel->getByDoctor((int)$doctor->id);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $consultationNotes = trim($_POST['consultation_notes'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatmentPlan = trim($_POST['treatment_plan'] ?? '');
    $medicationsRaw = trim($_POST['medications'] ?? '');

    if ($patientId <= 0) {
        $errors[] = 'Please select a patient.';
    }

    if ($medicationsRaw === '') {
        $errors[] = 'Please provide at least one medication or treatment item.';
    }

    if (empty($errors)) {
        try {
            $medications = implode(';', array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $medicationsRaw))));

            $prescriptionModel->create([
                'patient_id' => $patientId,
                'doctor_id' => (int)$doctor->id,
                'consultation_notes' => $consultationNotes,
                'diagnosis' => $diagnosis,
                'treatment_plan' => $treatmentPlan,
                'medications' => $medications,
            ]);

            $successMessage = 'Prescription recorded successfully.';
            $prescriptions = $prescriptionModel->getByDoctor((int)$doctor->id);
        } catch (Throwable $e) {
            error_log('Doctor dashboard error: ' . $e->getMessage());
            $errors[] = 'Unable to save the prescription. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MedPortal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <div class="container header-inner">
            <a href="../../index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="../../dashboard.php" class="header-action">
                    <span class="icon">🏠</span>
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
        <h1 class="mb-2">Doctor Workspace</h1>
        <p class="text-muted mb-2">Log consultations, issue prescriptions, and review patient treatment history.</p>

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
                <h2 class="card-title">Record New Consultation &amp; Prescription</h2>
            </div>
            <form method="post" class="card-body" style="display: grid; gap: 1rem;">
                <div>
                    <label for="patient_id" class="form-label">Patient</label>
                    <select id="patient_id" name="patient_id" class="form-control" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int)$patient['user_id'] ?>" <?= isset($patientId) && (int)$patientId === (int)$patient['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($patient['full_name'] . ' (' . $patient['email'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="consultation_notes" class="form-label">Consultation Notes</label>
                    <textarea id="consultation_notes" name="consultation_notes" class="form-control" rows="3" placeholder="Symptoms, history, vital signs..."><?= htmlspecialchars($consultationNotes ?? '') ?></textarea>
                </div>

                <div>
                    <label for="diagnosis" class="form-label">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" class="form-control" rows="2" placeholder="Diagnosis summary..."><?= htmlspecialchars($diagnosis ?? '') ?></textarea>
                </div>

                <div>
                    <label for="treatment_plan" class="form-label">Treatment Plan</label>
                    <textarea id="treatment_plan" name="treatment_plan" class="form-control" rows="2" placeholder="Recommended care, follow-up..."><?= htmlspecialchars($treatmentPlan ?? '') ?></textarea>
                </div>

                <div>
                    <label for="medications" class="form-label">Medications &amp; Dosage <span class="text-muted" style="font-weight: normal;">(one per line)</span></label>
                    <textarea id="medications" name="medications" class="form-control" rows="3" required placeholder="Amoxicillin 500mg - twice daily&#10;Ibuprofen 400mg - as needed"><?= htmlspecialchars($medicationsRaw ?? '') ?></textarea>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Save Prescription</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Prescriptions</h2>
            </div>
            <div class="card-body" style="display: grid; gap: 1rem;">
                <?php if (empty($prescriptions)): ?>
                    <p class="text-muted">No prescriptions recorded yet.</p>
                <?php else: ?>
                    <?php foreach ($prescriptions as $record): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?= htmlspecialchars($record['patient_name']) ?></strong>
                                <span class="text-muted"><?= htmlspecialchars(date('M d, Y H:i', strtotime($record['created_at']))) ?></span>
                            </div>
                            <?php if (!empty($record['diagnosis'])): ?>
                                <p class="mt-1"><strong>Diagnosis:</strong> <?= nl2br(htmlspecialchars($record['diagnosis'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($record['treatment_plan'])): ?>
                                <p><strong>Treatment Plan:</strong> <?= nl2br(htmlspecialchars($record['treatment_plan'])) ?></p>
                            <?php endif; ?>
                            <p><strong>Medications:</strong><br><?= nl2br(htmlspecialchars(str_replace(';', "\n", $record['medications']))) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>


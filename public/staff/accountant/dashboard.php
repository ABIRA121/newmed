<?php
require_once '../../config/config.php';
require_once '../../app/autoload.php';

$session = new Session();
$auth = new Auth();
$auth->requireRole('accountant');

$accountant = $auth->getCurrentUser();
$patientModel = new Patient();
$prescriptionModel = new Prescription();
$billModel = new PatientBill();

$patients = $patientModel->getAllPatients();
$unbilledPrescriptions = $prescriptionModel->getUnbilled();
$bills = $billModel->getByAccountant((int)$accountant->id);

$errors = [];
$successMessage = '';
$receiptPreview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create_bill';

    if ($action === 'mark_paid') {
        $billId = (int)($_POST['bill_id'] ?? 0);
        if ($billId <= 0) {
            $errors[] = 'Invalid bill selected.';
        } else {
            try {
                $bill = $billModel->findById($billId);
                if (!$bill || (int)$bill['accountant_id'] !== (int)$accountant->id) {
                    $errors[] = 'You can only clear bills that you created.';
                } else {
                    $billModel->markAsPaid($billId);
                    $successMessage = 'Bill marked as paid. Pharmacy can now dispense medication.';
                    $receiptPreview = $billModel->findById($billId);
                }
            } catch (Throwable $e) {
                error_log('Accountant mark paid error: ' . $e->getMessage());
                $errors[] = 'Unable to update the bill status.';
            }
        }
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $prescriptionId = isset($_POST['prescription_id']) && $_POST['prescription_id'] !== '' ? (int)$_POST['prescription_id'] : null;
        $services = trim($_POST['services'] ?? '');
        $medicationDetails = trim($_POST['medication_details'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $medicationTotal = (float)($_POST['medication_total'] ?? 0);
        $markAsPaid = isset($_POST['mark_paid']);

        if ($patientId <= 0) {
            $errors[] = 'Please select a patient.';
        }

        if ($services === '') {
            $errors[] = 'Please describe the services provided.';
        }

        if ($medicationDetails === '') {
            $errors[] = 'Please list the medications dispensed.';
        }

        if ($subtotal < 0 || $medicationTotal < 0) {
            $errors[] = 'Amounts must be zero or greater.';
        }

        if (empty($errors)) {
            $totalAmount = $subtotal + $medicationTotal;
            $status = $markAsPaid ? 'paid' : 'pending';

            try {
                $billId = $billModel->create([
                    'patient_id' => $patientId,
                    'accountant_id' => (int)$accountant->id,
                    'prescription_id' => $prescriptionId,
                    'services' => $services,
                    'medication_details' => $medicationDetails,
                    'subtotal' => $subtotal,
                    'medication_total' => $medicationTotal,
                    'total_amount' => $totalAmount,
                    'status' => $status,
                ]);

                $successMessage = $status === 'paid'
                    ? 'Receipt created and cleared for pharmacy pickup.'
                    : 'Bill created. Mark as paid when cleared.';

                $receiptPreview = $billModel->findById($billId);
                $unbilledPrescriptions = $prescriptionModel->getUnbilled();
                $bills = $billModel->getByAccountant((int)$accountant->id);
            } catch (Throwable $e) {
                error_log('Accountant create bill error: ' . $e->getMessage());
                $errors[] = 'Unable to save the bill. Please try again.';
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
    <title>Accountant Dashboard - MedPortal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <div class="container header-inner">
            <a href="../../index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="../../dashboard.php" class="header-action">
                    <span class="icon">📊</span>
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
        <h1 class="mb-2">Accountant Workspace</h1>
        <p class="text-muted mb-2">Generate invoices, clear medication payments, and issue receipts for patients.</p>

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
                <h2 class="card-title">Generate Patient Bill</h2>
            </div>
            <form method="post" class="card-body" style="display: grid; gap: 1rem;">
                <input type="hidden" name="action" value="create_bill">
                <div>
                    <label for="patient_id" class="form-label">Patient</label>
                    <select id="patient_id" name="patient_id" class="form-control" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int)$patient['user_id'] ?>" <?= isset($patientId) && (int)$patientId === (int)$patient['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="prescription_id" class="form-label">Linked Prescription (optional)</label>
                    <select id="prescription_id" name="prescription_id" class="form-control">
                        <option value="">No linked prescription</option>
                        <?php foreach ($unbilledPrescriptions as $prescription): ?>
                            <option value="<?= (int)$prescription['id'] ?>" <?= isset($prescriptionId) && (int)$prescriptionId === (int)$prescription['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prescription['patient_name']) ?> — <?= htmlspecialchars(date('M d, Y H:i', strtotime($prescription['created_at']))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="services" class="form-label">Services Rendered</label>
                    <textarea id="services" name="services" class="form-control" rows="3" required placeholder="Consultation fee: 50.00&#10;Laboratory tests: 35.00"><?= htmlspecialchars($services ?? '') ?></textarea>
                </div>

                <div>
                    <label for="medication_details" class="form-label">Medication Charges</label>
                    <textarea id="medication_details" name="medication_details" class="form-control" rows="3" required placeholder="Cetirizine (30 tablets) - 15.00&#10;Fluticasone spray - 25.00"><?= htmlspecialchars($medicationDetails ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label for="subtotal" class="form-label">Services Subtotal</label>
                        <input type="number" step="0.01" min="0" id="subtotal" name="subtotal" class="form-control" value="<?= htmlspecialchars($subtotal ?? '') ?>" required>
                    </div>
                    <div>
                        <label for="medication_total" class="form-label">Medication Total</label>
                        <input type="number" step="0.01" min="0" id="medication_total" name="medication_total" class="form-control" value="<?= htmlspecialchars($medicationTotal ?? '') ?>" required>
                    </div>
                </div>

                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="mark_paid" <?= !empty($markAsPaid) ? 'checked' : '' ?>>
                    Mark as paid (release to pharmacy)
                </label>

                <div>
                    <button type="submit" class="btn btn-primary">Generate Bill</button>
                </div>
            </form>
        </div>

        <?php if ($receiptPreview): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Receipt Preview</h2>
                </div>
                <div class="card-body">
                    <p><strong>Receipt #:</strong> <?= htmlspecialchars($receiptPreview['receipt_number']) ?></p>
                    <p><strong>Patient:</strong> <?= htmlspecialchars($receiptPreview['patient_name']) ?></p>
                    <p><strong>Accountant:</strong> <?= htmlspecialchars($receiptPreview['accountant_name']) ?></p>
                    <p><strong>Services:</strong><br><?= nl2br(htmlspecialchars($receiptPreview['services'])) ?></p>
                    <p><strong>Medications:</strong><br><?= nl2br(htmlspecialchars($receiptPreview['medication_details'])) ?></p>
                    <p><strong>Subtotal:</strong> $<?= number_format((float)$receiptPreview['subtotal'], 2) ?></p>
                    <p><strong>Medication Total:</strong> $<?= number_format((float)$receiptPreview['medication_total'], 2) ?></p>
                    <p><strong>Total Amount:</strong> $<?= number_format((float)$receiptPreview['total_amount'], 2) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars(strtoupper($receiptPreview['status'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Billing History</h2>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Patient</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bills)): ?>
                            <tr><td colspan="6" class="text-muted">No bills recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bills as $bill): ?>
                                <tr>
                                    <td><?= htmlspecialchars($bill['receipt_number']) ?></td>
                                    <td><?= htmlspecialchars($bill['patient_name']) ?></td>
                                    <td>$<?= number_format((float)$bill['total_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($bill['status'])) ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($bill['created_at']))) ?></td>
                                    <td>
                                        <?php if ($bill['status'] !== 'paid'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="bill_id" value="<?= (int)$bill['id'] ?>">
                                                <button type="submit" class="btn btn-success">Mark as Paid</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Cleared</span>
                                        <?php endif; ?>
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


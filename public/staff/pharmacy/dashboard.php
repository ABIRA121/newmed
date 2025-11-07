<?php
require_once '../../config/config.php';
require_once '../../app/autoload.php';

$session = new Session();
$auth = new Auth();
$auth->requireRole('pharmacy');

$pharmacist = $auth->getCurrentUser();
$billModel = new PatientBill();
$dispensationModel = new Dispensation();

$pendingBills = $billModel->getPaidBillsAwaitingDispensation();
$history = $dispensationModel->getByPharmacy((int)$pharmacist->id);

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billId = (int)($_POST['bill_id'] ?? 0);
    $itemsDispensed = trim($_POST['dispensed_items'] ?? '');

    if ($billId <= 0) {
        $errors[] = 'Please choose a cleared bill before dispensing.';
    }

    if ($itemsDispensed === '') {
        $errors[] = 'List the medication dispensed to complete the record.';
    }

    if (empty($errors)) {
        try {
            $bill = $billModel->findById($billId);
            if (!$bill) {
                $errors[] = 'Selected bill could not be found.';
            } elseif ($bill['status'] !== 'paid') {
                $errors[] = 'This bill is not cleared by accounts yet.';
            } elseif ($dispensationModel->hasDispensation($billId)) {
                $errors[] = 'Medication has already been dispensed for this receipt.';
            } else {
                $dispensationModel->create([
                    'bill_id' => $billId,
                    'pharmacy_id' => (int)$pharmacist->id,
                    'dispensed_items' => $itemsDispensed,
                ]);

                $successMessage = 'Dispensation recorded successfully.';
                $pendingBills = $billModel->getPaidBillsAwaitingDispensation();
                $history = $dispensationModel->getByPharmacy((int)$pharmacist->id);
            }
        } catch (Throwable $e) {
            error_log('Pharmacy dashboard error: ' . $e->getMessage());
            $errors[] = 'Unable to record the dispensation right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard - MedPortal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="page-header">
        <div class="container header-inner">
            <a href="../../index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="../../dashboard.php" class="header-action">
                    <span class="icon">🧾</span>
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
        <h1 class="mb-2">Pharmacy Workspace</h1>
        <p class="text-muted mb-2">Dispense medication only after finance clearance. Record each transaction for accountability.</p>

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
                <h2 class="card-title">Dispense Cleared Medication</h2>
            </div>
            <form method="post" class="card-body" style="display: grid; gap: 1rem;">
                <div>
                    <label for="bill_id" class="form-label">Cleared Receipts</label>
                    <select id="bill_id" name="bill_id" class="form-control" required>
                        <option value="">Select cleared receipt</option>
                        <?php foreach ($pendingBills as $bill): ?>
                            <option value="<?= (int)$bill['id'] ?>" <?= isset($billId) && (int)$billId === (int)$bill['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bill['receipt_number']) ?> — <?= htmlspecialchars($bill['patient_name']) ?> ($<?= number_format((float)$bill['total_amount'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="dispensed_items" class="form-label">Dispensed Medication</label>
                    <textarea id="dispensed_items" name="dispensed_items" class="form-control" rows="3" required placeholder="List medications and quantities provided to the patient."><?= htmlspecialchars($itemsDispensed ?? '') ?></textarea>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Record Dispensation</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Dispensation History</h2>
            </div>
            <div class="card-body" style="display: grid; gap: 1rem;">
                <?php if (empty($history)): ?>
                    <p class="text-muted">No dispensation records yet.</p>
                <?php else: ?>
                    <?php foreach ($history as $record): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?= htmlspecialchars($record['patient_name']) ?></strong>
                                <span class="text-muted"><?= htmlspecialchars(date('M d, Y H:i', strtotime($record['created_at']))) ?></span>
                            </div>
                            <p class="mt-1"><strong>Receipt #:</strong> <?= htmlspecialchars($record['receipt_number']) ?></p>
                            <p><strong>Total Paid:</strong> $<?= number_format((float)$record['total_amount'], 2) ?></p>
                            <p><strong>Items Dispensed:</strong><br><?= nl2br(htmlspecialchars($record['dispensed_items'])) ?></p>
                            <?php if (!empty($record['confirmed_at'])): ?>
                                <p class="text-muted">Confirmed at <?= htmlspecialchars(date('M d, Y H:i', strtotime($record['confirmed_at']))) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
</body>
</html>


<?php
require_once __DIR__ . '/../layouts/header.php';
$_sp = Database::getConnection()->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$_addr  = $_sp['company_address'] ?? 'La Mesa, Quezon City';
$_phone = $_sp['contact_number']  ?? '';
?>

<div class="mb-3 d-print-none d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>/billing" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to Billing List</a>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/billing?action=download-pdf&id=<?= $bill['id'] ?>" class="btn btn-danger btn-sm px-3">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-printer-fill me-1"></i> Print Statement
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mx-auto p-4 p-md-5" style="max-width: 800px;">
    <!-- Bill Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo" class="rounded-circle shadow-sm" style="width: 56px; height: 56px; object-fit: cover;">
            <div>
                <h4 class="fw-bold mb-0 text-dark"><?= APP_NAME ?></h4>
                <p class="text-muted small mb-0"><?= htmlspecialchars($_addr) ?><?= $_phone ? ' | ' . htmlspecialchars($_phone) : '' ?></p>
                <div class="fw-semibold text-primary small">STATEMENT OF WATER ACCOUNT</div>
            </div>
        </div>
        <div class="text-end">
            <div class="badge bg-light text-dark border font-monospace fs-6 px-3 py-2 mb-1"><?= htmlspecialchars($bill['bill_number']) ?></div>
            <div class="text-muted small">Period: <strong><?= format_date($bill['billing_period'], 'F Y') ?></strong></div>
        </div>
    </div>

    <!-- Customer & Meter Summary -->
    <div class="row g-3 mb-4 p-3 bg-light rounded border">
        <div class="col-6">
            <div class="text-muted fs-8 text-uppercase fw-bold">Customer Information</div>
            <div class="fw-bold fs-6 text-dark mt-1"><?= htmlspecialchars($bill['customer_name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($bill['address']) ?>, <?= htmlspecialchars($bill['barangay_name']) ?></div>
            <div class="small">Account #: <strong class="font-monospace text-primary"><?= htmlspecialchars($bill['account_number']) ?></strong> | Type: <strong><?= htmlspecialchars($bill['account_type']) ?></strong></div>
        </div>
        <div class="col-6 text-end border-start ps-3">
            <div class="text-muted fs-8 text-uppercase fw-bold">Meter Details</div>
            <div class="small mt-1">Meter Serial #: <strong class="font-monospace"><?= htmlspecialchars($bill['meter_number']) ?></strong></div>
            <div class="small">Previous Reading: <strong><?= number_format($bill['prev_reading_val']) ?></strong></div>
            <div class="small">Current Reading: <strong><?= number_format($bill['curr_reading_val']) ?></strong></div>
            <div class="small">Total Consumption: <strong class="text-primary fs-6"><?= number_format($bill['consumption_val']) ?> m³</strong></div>
        </div>
    </div>

    <!-- Charges Breakdown Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Billing Line Description</th>
                    <th class="text-end">Rate Applied</th>
                    <th class="text-end">Amount (PHP)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div>Base Minimum Charge (First 10 m³)</div>
                        <small class="text-muted">Minimum base allowance for <?= htmlspecialchars($bill['account_type']) ?></small>
                    </td>
                    <td class="text-end"><?= format_money($bill['applied_min_rate']) ?></td>
                    <td class="text-end fw-semibold"><?= format_money($bill['applied_min_rate']) ?></td>
                </tr>
                <?php if ($bill['consumption_val'] > 10): ?>
                <tr>
                    <td>
                        <div>Excess Consumption Charge</div>
                        <small class="text-muted"><?= $bill['consumption_val'] - 10 ?> m³ &times; <?= format_money($bill['applied_rate_per_m3']) ?> per m³</small>
                    </td>
                    <td class="text-end"><?= format_money($bill['applied_rate_per_m3']) ?> / m³</td>
                    <td class="text-end fw-semibold"><?= format_money(($bill['consumption_val'] - 10) * $bill['applied_rate_per_m3']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($bill['previous_unpaid'] > 0): ?>
                <tr>
                    <td class="text-danger">Arrears / Previous Unpaid Balance</td>
                    <td class="text-end">&mdash;</td>
                    <td class="text-end text-danger fw-semibold"><?= format_money($bill['previous_unpaid']) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="2" class="fw-bold text-end fs-6">TOTAL AMOUNT DUE:</td>
                    <td class="text-end fw-bold text-primary fs-5"><?= format_money($bill['total_amount']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Footer Payment Reminder & Verification -->
    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <div>
            <div class="text-muted small">Please pay on or before: <strong class="text-danger fs-6"><?= format_date($bill['due_date']) ?></strong></div>
            <small class="text-muted">Overdue accounts are subject to 10% penalty charge after due date.</small>
        </div>
        <div class="text-end">
            <span class="badge-status badge-<?= strtolower(str_replace(' ', '', $bill['status'])) ?> fs-6 px-3 py-2"><?= htmlspecialchars($bill['status']) ?></span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

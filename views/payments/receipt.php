<?php
require_once __DIR__ . '/../layouts/header.php';
// Pull live district settings
$_settingsPdo = Database::getConnection();
$_s = $_settingsPdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$_districtAddress = $_s['company_address'] ?? 'La Mesa, Quezon City';
$_districtPhone   = $_s['contact_number']  ?? '';
?>

<div class="mb-3 d-print-none d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>/payments" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to Cashier Terminal</a>
    <button onclick="window.print()" class="btn btn-success btn-sm px-4"><i class="bi bi-printer-fill me-1"></i> Print Official Receipt</button>
</div>

<div class="card border-0 shadow-sm mx-auto p-4 p-md-5" style="max-width: 600px;">
    <!-- Receipt Header -->
    <div class="text-center border-bottom pb-4 mb-4">
        <img src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo" class="rounded-circle mb-2 shadow-sm" style="width: 54px; height: 54px; object-fit: cover;">
        <h5 class="fw-bold mb-0 text-dark"><?= APP_NAME ?></h5>
        <p class="text-muted small mb-1"><?= htmlspecialchars($_districtAddress) ?><?= $_districtPhone ? ' | ' . htmlspecialchars($_districtPhone) : '' ?></p>
        <span class="badge bg-success text-white px-3 py-1 font-monospace fs-6">OFFICIAL RECEIPT</span>
    </div>

    <!-- OR Metadata -->
    <div class="row g-2 mb-4 fs-7">
        <div class="col-6">
            <div class="text-muted">OR Number:</div>
            <div class="fw-bold font-monospace fs-6 text-primary"><?= htmlspecialchars($payment['or_number']) ?></div>
        </div>
        <div class="col-6 text-end">
            <div class="text-muted">Date & Time:</div>
            <div class="fw-semibold"><?= format_date($payment['payment_date'], 'M d, Y h:i A') ?></div>
        </div>
        <div class="col-6">
            <div class="text-muted">Account Number:</div>
            <div class="fw-bold font-monospace"><?= htmlspecialchars($payment['account_number']) ?></div>
        </div>
        <div class="col-6 text-end">
            <div class="text-muted">Payor Name:</div>
            <div class="fw-bold"><?= htmlspecialchars($payment['customer_name']) ?></div>
        </div>
    </div>

    <!-- Applied Bills Breakdown -->
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light fs-8">
                <tr>
                    <th>Bill Number</th>
                    <th>Period</th>
                    <th class="text-end">Applied Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appliedBills)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Advance Account Balance Deposit</td></tr>
                <?php else: ?>
                    <?php foreach ($appliedBills as $ab): ?>
                        <tr>
                            <td class="font-monospace text-primary fw-semibold"><?= htmlspecialchars($ab['bill_number']) ?></td>
                            <td><?= format_date($ab['billing_period'], 'M Y') ?></td>
                            <td class="text-end fw-bold"><?= format_money($ab['amount_applied']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="2" class="fw-bold text-end">TOTAL AMOUNT PAID:</td>
                    <td class="text-end fw-bold text-success fs-5"><?= format_money($payment['amount_paid']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payment Details -->
    <div class="bg-light p-3 rounded mb-4 fs-7">
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Payment Method:</span>
            <span class="fw-bold"><?= htmlspecialchars($payment['payment_method']) ?></span>
        </div>
        <?php if (!empty($payment['reference_number'])): ?>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Reference Number:</span>
                <span class="font-monospace fw-semibold"><?= htmlspecialchars($payment['reference_number']) ?></span>
            </div>
        <?php endif; ?>
        <div class="d-flex justify-content-between">
            <span class="text-muted">Assisting Cashier:</span>
            <span class="fw-semibold"><?= htmlspecialchars($payment['cashier_name']) ?></span>
        </div>
    </div>

    <div class="text-center text-muted small border-top pt-3">
        Thank you for paying your water bill on time!<br>
        <em>This serves as an official receipt of La Mesa Water District.</em>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

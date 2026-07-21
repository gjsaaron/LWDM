<?php
require_once __DIR__ . '/../layouts/header.php';
$totalUnpaid = array_sum(array_column($unpaidBills, 'total_amount'))
             - array_sum(array_column($unpaidBills, 'amount_paid'));
?>

<div class="mb-3 d-print-none d-flex justify-content-between align-items-center">
    <a href="<?= BASE_URL ?>/delinquents" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i> Back to Delinquents
    </a>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer-fill me-1"></i> Print Notice
    </button>
</div>

<!-- Print Header (only visible when printing) -->
<div class="card border-0 shadow-sm mx-auto p-4 p-md-5" style="max-width: 780px;">

    <!-- Notice Header -->
    <div class="text-center border-bottom pb-4 mb-4">
        <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
            <img src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo" class="rounded-circle shadow-sm" style="width: 52px; height: 52px; object-fit: cover;">
            <div class="text-start">
                <h4 class="fw-bold mb-0"><?= APP_NAME ?></h4>
                <small class="text-muted">Water District Management System</small>
            </div>
        </div>
        <div class="fw-bold fs-5 text-danger border border-danger rounded-pill d-inline-block px-4 py-1 mt-2">
            NOTICE OF DISCONNECTION
        </div>
        <div class="text-muted small mt-2">Date Issued: <?= date('F d, Y') ?></div>
    </div>

    <!-- Addressee & Account Info -->
    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="text-muted small fw-bold text-uppercase mb-1">Addressed To:</div>
            <div class="fw-bold fs-6"><?= htmlspecialchars($account['customer_name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($account['address']) ?>, <?= htmlspecialchars($account['barangay_name']) ?></div>
            <?php if ($account['contact_number']): ?>
            <div class="small text-muted">Contact: <?= htmlspecialchars($account['contact_number']) ?></div>
            <?php endif; ?>
        </div>
        <div class="col-6 text-end border-start ps-3">
            <div class="text-muted small fw-bold text-uppercase mb-1">Account Details:</div>
            <div class="small">Account No.: <strong class="font-monospace text-primary"><?= htmlspecialchars($account['account_number']) ?></strong></div>
            <div class="small">Customer Code: <strong class="font-monospace"><?= htmlspecialchars($account['customer_code']) ?></strong></div>
            <div class="small">Meter No.: <strong class="font-monospace"><?= htmlspecialchars($account['meter_number'] ?? 'N/A') ?></strong></div>
            <div class="small">Account Type: <strong><?= htmlspecialchars($account['account_type']) ?></strong></div>
        </div>
    </div>

    <!-- Notice Body Text -->
    <div class="mb-4 p-3 bg-danger-subtle border border-danger rounded small lh-lg">
        <p class="mb-2">Dear <strong><?= htmlspecialchars($account['customer_name']) ?></strong>,</p>
        <p class="mb-2">
            Our records indicate that your water account has <strong><?= count($unpaidBills) ?> outstanding unpaid bill(s)</strong>
            with a total balance of <strong class="text-danger"><?= format_money($totalUnpaid) ?></strong>.
            Despite previous notifications, payment has not been received.
        </p>
        <p class="mb-2">
            Please be informed that in accordance with our district's billing policies, your water service connection
            is <strong>scheduled for disconnection</strong> unless full payment or a formal payment arrangement is made
            within <strong>5 working days</strong> from the date of this notice.
        </p>
        <p class="mb-0">
            To settle your account or arrange a payment plan, please visit our office or contact us at your earliest convenience.
            <strong>Please bring this notice and a valid ID when visiting our office.</strong>
        </p>
    </div>

    <!-- Unpaid Bills Table -->
    <h6 class="fw-bold mb-2">Outstanding Billing Details:</h6>
    <table class="table table-bordered align-middle mb-4 small">
        <thead class="table-light">
            <tr>
                <th>Bill Number</th>
                <th>Billing Period</th>
                <th>Due Date</th>
                <th>Status</th>
                <th class="text-end">Amount Due</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($unpaidBills as $b): ?>
            <?php $bal = (float)$b['total_amount'] - (float)$b['amount_paid']; ?>
            <tr class="<?= $b['status'] === 'Overdue' ? 'table-danger' : '' ?>">
                <td class="font-monospace fw-bold"><?= htmlspecialchars($b['bill_number']) ?></td>
                <td><?= format_date($b['billing_period'], 'M Y') ?></td>
                <td><?= format_date($b['due_date']) ?></td>
                <td><span class="badge-status badge-<?= strtolower(str_replace(' ','-',$b['status'])) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                <td class="text-end fw-bold"><?= format_money($bal) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-light fw-bold">
                <td colspan="4" class="text-end">TOTAL AMOUNT TO SETTLE:</td>
                <td class="text-end text-danger"><?= format_money($totalUnpaid) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <div class="row mt-5">
        <div class="col-6">
            <div class="border-top pt-2 text-center">
                <div class="fw-bold small">Billing Officer Signature</div>
                <div class="text-muted small">La Mesa Water District</div>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 text-center">
                <div class="fw-bold small">Received By / Date</div>
                <div class="text-muted small">(Customer Acknowledgment)</div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

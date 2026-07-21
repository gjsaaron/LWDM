<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delinquent Accounts</h4>
        <p class="text-muted small mb-0">Track customer accounts with unpaid overdue balances</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/delinquents" class="row g-2">
            <div class="col-md-9">
                <select name="barangay" class="form-select">
                    <option value="">Filter by Barangay (All)</option>
                    <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['name']) ?>" <?= $barangay === $b['name'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Account No</th>
                    <th>Customer Name</th>
                    <th>Barangay</th>
                    <th>Type</th>
                    <th>Unpaid Months</th>
                    <th>Total Balance</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($delinquents)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No delinquent accounts found.</td></tr>
                <?php else: ?>
                    <?php foreach ($delinquents as $d): ?>
                        <tr>
                            <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($d['account_number']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/customers?action=view&id=<?= $d['customer_id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                    <?= htmlspecialchars($d['customer_name']) ?>
                                </a>
                                <div class="small text-muted"><?= htmlspecialchars($d['contact_number'] ?: 'No contact') ?></div>
                            </td>
                            <td><?= htmlspecialchars($d['barangay_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($d['account_type']) ?></span></td>
                            <td><span class="badge bg-danger"><?= $d['unpaid_months'] ?> Month(s)</span></td>
                            <td class="fw-bold text-danger fs-6"><?= format_money($d['current_balance']) ?></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/payments?search=<?= urlencode($d['account_number']) ?>" class="btn btn-sm btn-success me-1">
                                    <i class="bi bi-cash-stack me-1"></i> Pay
                                </a>
                                <a href="<?= BASE_URL ?>/delinquents/notice?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" target="_blank" title="Print Disconnection Notice">
                                    <i class="bi bi-printer-fill"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

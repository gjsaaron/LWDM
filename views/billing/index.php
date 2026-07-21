<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Billing Module</h4>
        <p class="text-muted small mb-0">Generate monthly water consumption bills and manage billing adjustments</p>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= BASE_URL ?>/billing?action=apply-penalties" method="POST" onsubmit="return confirm('Apply overdue penalties to all past-due unpaid bills?');">
            <?= CSRF::inputField() ?>
            <button type="submit" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Apply Overdue Penalties
            </button>
        </form>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#batchBillingModal">
            <i class="bi bi-play-circle-fill me-1"></i> Run Batch Billing
        </button>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <?php unset($_SESSION['flash_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <?php unset($_SESSION['flash_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Recent Batch Runs Banner -->
<?php if (!empty($jobs)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Recent Batch Billing Runs</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Billing Period</th>
                    <th>Processed / Total</th>
                    <th>Failures</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td class="fw-semibold"><?= format_date($j['billing_period'], 'F Y') ?></td>
                        <td><?= number_format($j['processed_accounts']) ?> / <?= number_format($j['total_accounts']) ?> accounts</td>
                        <td>
                            <?php if ($j['failed_accounts'] > 0): ?>
                                <span class="badge bg-danger"><?= $j['failed_accounts'] ?> failed</span>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-success"><?= htmlspecialchars($j['status']) ?></span></td>
                        <td class="text-muted small"><?= format_date($j['created_at'], 'M d, Y h:i A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/billing" class="row g-2">
            <div class="col-md-7">
                <input type="text" name="search" class="form-control" placeholder="Search bill #, account #, customer name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Payment Statuses</option>
                    <option value="Unpaid" <?= $status === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partially Paid" <?= $status === 'Partially Paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="Overdue" <?= $status === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Bills Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Bill Number</th>
                    <th>Account No</th>
                    <th>Customer Name</th>
                    <th>Period</th>
                    <th>Consumption</th>
                    <th>Subtotal</th>
                    <th>Total Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr><td colspan="10" class="text-center py-4 text-muted">No billing records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bills as $b): ?>
                        <tr>
                            <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($b['bill_number']) ?></td>
                            <td><code><?= htmlspecialchars($b['account_number']) ?></code></td>
                            <td>
                                <a href="<?= BASE_URL ?>/customers?action=view&id=<?= $b['customer_id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                    <?= htmlspecialchars($b['customer_name']) ?>
                                </a>
                            </td>
                            <td><?= format_date($b['billing_period'], 'M Y') ?></td>
                            <td class="fw-bold"><?= number_format($b['consumption_val']) ?> m³</td>
                            <td><?= format_money($b['subtotal']) ?></td>
                            <td class="fw-bold text-dark"><?= format_money($b['total_amount']) ?></td>
                            <td><?= format_date($b['due_date']) ?></td>
                            <td><span class="badge-status badge-<?= strtolower(str_replace(' ', '', $b['status'])) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/billing?action=view&id=<?= $b['id'] ?>" class="btn btn-sm btn-light border text-primary" title="View Statement">
                                    <i class="bi bi-file-earmark-text-fill me-1"></i> Statement
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalCount ?> bills)</span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/billing?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Previous</a>
                </li>
                <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/billing?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Batch Billing -->
<div class="modal fade" id="batchBillingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/billing?action=generate" method="POST">
                <?= CSRF::inputField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-play-circle me-2 text-primary"></i>Execute Batch Billing Run</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info text-sm py-2">
                        <i class="bi bi-info-circle-fill me-1"></i> Generates monthly water bills for all active customer accounts with unbilled meter readings.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Billing Period Month *</label>
                        <input type="date" name="billing_period" class="form-control" value="<?= date('Y-m-01') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Bill Due Date *</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-15', strtotime('+1 month')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Start Batch Generation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

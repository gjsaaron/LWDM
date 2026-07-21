<?php
require_once __DIR__ . '/../layouts/header.php';

$actionColors = [
    'User Login'               => 'bg-success-subtle text-success border-success-subtle',
    'User Logout'              => 'bg-secondary-subtle text-secondary border-secondary-subtle',
    'Register Customer'        => 'bg-primary-subtle text-primary border-primary-subtle',
    'Accept Payment'           => 'bg-success-subtle text-success border-success-subtle',
    'Batch Billing Run'        => 'bg-warning-subtle text-warning border-warning-subtle',
    'Apply Overdue Penalties'  => 'bg-danger-subtle text-danger border-danger-subtle',
    'Reset Employee Password'  => 'bg-danger-subtle text-danger border-danger-subtle',
    'Delete Employee'          => 'bg-danger-subtle text-danger border-danger-subtle',
    'Database Backup'          => 'bg-info-subtle text-info border-info-subtle',
];
function logBadgeClass(string $action, array $map): string {
    return $map[$action] ?? 'bg-primary-subtle text-primary border-primary-subtle';
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">System Audit Logs</h4>
        <p class="text-muted small mb-0">
            Security trail of all mutating employee actions — <?= number_format($totalCount) ?> total entries
        </p>
    </div>
    <span class="text-muted small">Page <?= $page ?> / <?= $totalPages ?></span>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/audit-logs" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by employee, record, or IP address..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="action_filter" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actionTypes as $at): ?>
                        <option value="<?= htmlspecialchars($at) ?>"
                            <?= $action === $at ? 'selected' : '' ?>>
                            <?= htmlspecialchars($at) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
            </div>
            <div class="col-md-1">
                <a href="<?= BASE_URL ?>/audit-logs" class="btn btn-outline-secondary w-100" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Employee</th>
                    <th>Action</th>
                    <th>Affected Record</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">No audit logs found matching your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="font-monospace text-muted small text-nowrap">
                                <?= format_date($l['created_at'], 'M d, Y') ?>
                                <span class="text-muted opacity-75"> <?= format_date($l['created_at'], 'H:i:s') ?></span>
                            </td>
                            <td class="fw-semibold text-dark small"><?= htmlspecialchars($l['employee_name'] ?: '—') ?></td>
                            <td>
                                <span class="badge border <?= logBadgeClass($l['action'], $actionColors) ?> small">
                                    <?= htmlspecialchars($l['action']) ?>
                                </span>
                            </td>
                            <td><code class="small"><?= htmlspecialchars($l['affected_record'] ?: 'N/A') ?></code></td>
                            <td><span class="text-muted font-monospace small"><?= htmlspecialchars($l['ip_address'] ?: '—') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $limit, $totalCount)) ?> of <?= number_format($totalCount) ?> entries
            </span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/audit-logs?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($action) ?>">Previous</a>
                </li>
                <?php
                $start = max(1, $page - 2);
                $end   = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>/audit-logs?page=<?= $i ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($action) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/audit-logs?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($action) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

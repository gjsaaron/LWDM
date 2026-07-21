<?php
$currentRole = $_SESSION['user_role'] ?? '';
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive(string $path, string $currentUri): string {
    return str_contains($currentUri, $path) ? 'active' : '';
}
?>
<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center gap-2">
        <img src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
        <div>
            <h6 class="fw-bold mb-0 text-white"><?= APP_NAME ?></h6>
            <small class="text-muted" style="font-size: 0.75rem;">WDMS Portal</small>
        </div>
    </div>

    <ul class="list-unstyled components mb-0">
        <li class="<?= isActive('/dashboard', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>

        <?php if (in_array($currentRole, ['Administrator', 'Billing Staff', 'Cashier', 'Manager'], true)): ?>
        <li class="<?= isActive('/customers', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/customers"><i class="bi bi-people-fill"></i> Customers</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['Administrator', 'Billing Staff'], true)): ?>
        <li class="<?= isActive('/meters', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/meters"><i class="bi bi-speedometer"></i> Meter Readings</a>
        </li>
        <li class="<?= isActive('/billing', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/billing"><i class="bi bi-file-earmark-text-fill"></i> Billing Module</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['Administrator', 'Cashier'], true)): ?>
        <li class="<?= isActive('/payments', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/payments"><i class="bi bi-cash-stack"></i> Cashier Terminal</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['Administrator', 'Billing Staff', 'Manager'], true)): ?>
        <li class="<?= isActive('/delinquents', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/delinquents"><i class="bi bi-exclamation-triangle-fill"></i> Delinquents</a>
        </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['Administrator', 'Manager'], true)): ?>
        <li class="<?= isActive('/reports', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/reports"><i class="bi bi-graph-up-arrow"></i> Reports</a>
        </li>
        <?php endif; ?>

        <?php if ($currentRole === 'Administrator'): ?>
        <li class="sidebar-heading px-3 pt-3 pb-1 text-uppercase text-muted fs-8 fw-semibold">Administration</li>
        <li class="<?= isActive('/employees', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/employees"><i class="bi bi-person-badge-fill"></i> Employees</a>
        </li>
        <li class="<?= isActive('/audit-logs', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/audit-logs"><i class="bi bi-journal-text"></i> Audit Logs</a>
        </li>
        <li class="<?= isActive('/settings', $currentUri) ?>">
            <a href="<?= BASE_URL ?>/settings"><i class="bi bi-gear-fill"></i> Settings</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

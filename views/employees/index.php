<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Employee Management</h4>
        <p class="text-muted small mb-0">Manage system users, access roles, status, and authentication credentials</p>
    </div>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newEmployeeModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New Employee
    </button>
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

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Emp Number</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($emp['employee_number']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($emp['full_name']) ?></td>
                        <td><code><?= htmlspecialchars($emp['username']) ?></code></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['role']) ?></span></td>
                        <td><span class="badge-status badge-<?= strtolower($emp['status']) ?>"><?= htmlspecialchars($emp['status']) ?></span></td>
                        <td class="text-muted small"><?= format_date($emp['last_login'], 'M d, Y h:i A') ?></td>
                        <td class="text-end">
                            <!-- Toggle Status Form -->
                            <form action="<?= BASE_URL ?>/employees?action=toggle" method="POST" class="d-inline">
                                <?= CSRF::inputField() ?>
                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Toggle Status">
                                    <i class="bi bi-power"></i>
                                </button>
                            </form>

                            <!-- Reset Password Modal Trigger -->
                            <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#resetModal<?= $emp['id'] ?>" title="Reset Password">
                                <i class="bi bi-key-fill"></i>
                            </button>

                            <!-- Delete Form -->
                            <?php if ($emp['id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                            <form action="<?= BASE_URL ?>/employees?action=delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this employee?');">
                                <?= CSRF::inputField() ?>
                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                            <!-- Reset Password Modal -->
                            <div class="modal fade text-start" id="resetModal<?= $emp['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <form action="<?= BASE_URL ?>/employees?action=reset-password" method="POST">
                                            <?= CSRF::inputField() ?>
                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold">Reset Password: <?= htmlspecialchars($emp['username']) ?></h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-medium">New Password</label>
                                                    <input type="password" name="new_password" class="form-control form-control-sm" required minlength="8">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-warning">Reset Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Employee -->
<div class="modal fade" id="newEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/employees" method="POST">
                <?= CSRF::inputField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Add System User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Password * (min 8 chars)</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="Administrator">Administrator</option>
                            <option value="Billing Staff">Billing Staff</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

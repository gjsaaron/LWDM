<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Profile</h4>
        <p class="text-muted small mb-0">View your account details and change your password</p>
    </div>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
        <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($employee['role'] ?? '') ?>
    </span>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?><?php unset($_SESSION['flash_success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?><?php unset($_SESSION['flash_error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Account Info Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4">
            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle"
                 style="width:80px;height:80px;font-size:2rem;">
                <i class="bi bi-person-fill text-primary"></i>
            </div>
            <h5 class="fw-bold mb-0"><?= htmlspecialchars($employee['full_name'] ?? '') ?></h5>
            <p class="text-muted small mb-3"><code><?= htmlspecialchars($employee['username'] ?? '') ?></code></p>
            <div class="d-flex flex-column gap-2 text-start small px-2">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Employee No.</span>
                    <strong class="font-monospace"><?= htmlspecialchars($employee['employee_number'] ?? '') ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Role</span>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($employee['role'] ?? '') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Status</span>
                    <span class="badge-status badge-<?= strtolower($employee['status'] ?? 'active') ?>"><?= htmlspecialchars($employee['status'] ?? '') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Last Login</span>
                    <span><?= format_date($employee['last_login'] ?? '', 'M d, Y') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password + Activity -->
    <div class="col-lg-8">
        <!-- Change Password -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-lock-fill text-primary me-2"></i>Change Password</h6>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/profile" method="POST" class="row g-3">
                    <?= CSRF::inputField() ?>
                    <div class="col-md-12">
                        <label class="form-label fw-medium">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">New Password</label>
                        <input type="password" name="new_password" class="form-control" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-custom px-4">
                            <i class="bi bi-save-fill me-1"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity History -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity text-success me-2"></i>Recent Activity</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Record</th>
                            <th>IP Address</th>
                            <th class="text-end">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activityLogs)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No recent activity.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activityLogs as $log): ?>
                            <tr>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td class="text-muted small font-monospace"><?= htmlspecialchars($log['affected_record'] ?? '') ?></td>
                                <td class="text-muted small font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                                <td class="text-end text-muted small"><?= format_date($log['created_at'], 'M d, h:i A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

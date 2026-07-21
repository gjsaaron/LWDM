<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">System Settings & Water Rates</h4>
        <p class="text-muted small mb-0">Configure district parameters, consumption tier rates, and system utilities</p>
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

<form action="<?= BASE_URL ?>/settings" method="POST">
    <?= CSRF::inputField() ?>

    <div class="row g-4">

        <!-- ── Water Rate Bands ─────────────────────────────────────────────── -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-tags-fill me-2"></i>Water Consumption Rates</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Account Type</th>
                                <th>Min. Consumption</th>
                                <th>Min. Base Charge (₱)</th>
                                <th>Rate per Extra m³ (₱)</th>
                                <th>Overdue Penalty (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($waterRates as $wr): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($wr['account_type']) ?></td>
                                    <td><?= $wr['min_consumption'] ?> m³</td>
                                    <td>
                                        <input type="number" step="0.01" name="rates[<?= $wr['id'] ?>][min_rate]"
                                               class="form-control form-control-sm" style="max-width: 110px;"
                                               value="<?= number_format($wr['min_rate'], 2, '.', '') ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="rates[<?= $wr['id'] ?>][rate_per_m3]"
                                               class="form-control form-control-sm" style="max-width: 110px;"
                                               value="<?= number_format($wr['rate_per_m3'], 2, '.', '') ?>">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm" style="max-width: 100px;">
                                            <input type="number" step="0.1" name="rates[<?= $wr['id'] ?>][penalty_rate]"
                                                   class="form-control form-control-sm"
                                                   value="<?= number_format($wr['penalty_rate'], 1, '.', '') ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── General District Config ─────────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>District Configuration</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">District Name</label>
                        <input type="text" name="company_name" class="form-control"
                               value="<?= htmlspecialchars($settings['company_name'] ?? 'La Mesa Water District') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Office Address</label>
                        <input type="text" name="company_address" class="form-control"
                               value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Contact / Hotline</label>
                        <input type="text" name="contact_number" class="form-control"
                               value="<?= htmlspecialchars($settings['contact_number'] ?? '') ?>">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Billing Due Days</label>
                            <input type="number" name="billing_due_days" class="form-control"
                                   value="<?= htmlspecialchars($settings['billing_due_days'] ?? '15') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Penalty % (Overdue)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="penalty_percentage" class="form-control"
                                       value="<?= htmlspecialchars($settings['penalty_percentage'] ?? '10') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex gap-3">
        <button type="submit" class="btn btn-primary-custom px-5 fw-semibold">
            <i class="bi bi-save-fill me-1"></i>Save All Settings
        </button>
    </div>
</form>

<!-- ── Database Utilities ─────────────────────────────────────────────────── -->
<hr class="my-5">
<h5 class="fw-bold mb-3"><i class="bi bi-database-gear me-2 text-danger"></i>Database Utilities</h5>
<div class="row g-4 mb-4">
    <!-- Backup -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-download me-2 text-danger"></i>Database Backup</h6>
                <p class="text-muted small mb-3">Download a full SQL dump of <code>lamesa_water_db</code> including all tables and data.</p>
                <form action="<?= BASE_URL ?>/settings/backup" method="POST">
                    <?= CSRF::inputField() ?>
                    <button type="submit" class="btn btn-danger fw-semibold px-4">
                        <i class="bi bi-floppy-fill me-1"></i>Download .sql Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-upload me-2 text-warning"></i>Database Restore</h6>
                <p class="text-muted small mb-3">
                    Upload a previously downloaded <code>.sql</code> backup to restore the database.
                    <strong class="text-danger">This will overwrite existing data.</strong>
                </p>
                <form action="<?= BASE_URL ?>/settings/restore" method="POST" enctype="multipart/form-data"
                      onsubmit="return confirm('⚠️ WARNING: Restoring will overwrite ALL current data. Are you absolutely sure?');">
                    <?= CSRF::inputField() ?>
                    <div class="mb-3">
                        <input type="file" name="sql_file" class="form-control form-control-sm" accept=".sql" required>
                        <div class="form-text text-muted">Only .sql files generated by this system. Max 50 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-warning fw-semibold px-4 no-spin">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Billing Formula Reference -->
<div class="card border-0 shadow-sm bg-light">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Billing Formula Reference</h6>
        <pre class="mb-0 small text-muted lh-lg">
Consumption (m³) = Current Reading − Previous Reading

If Consumption ≤ Min Consumption (10 m³):
    Subtotal = Minimum Base Charge

Else:
    Subtotal = Min Base + ((Consumption − 10) × Rate/m³)

Total Due = Subtotal + Previous Unpaid Balance
Overdue Penalty = Subtotal × Penalty % (applied after due date)
        </pre>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

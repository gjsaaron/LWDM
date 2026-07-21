<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Management</h4>
        <p class="text-muted small mb-0">Manage water district customer accounts, meter assignments, and connection statuses</p>
    </div>
    <?php if (in_array($_SESSION['user_role'], ['Administrator', 'Billing Staff'], true)): ?>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
            <i class="bi bi-person-plus-fill me-1"></i> Register New Customer
        </button>
    <?php endif; ?>
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

<!-- Filter & Search Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/customers" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search code, account #, name, meter #..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="barangay" class="form-select">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['name']) ?>" <?= $barangay === $b['name'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Account Types</option>
                    <option value="Residential" <?= $type === 'Residential' ? 'selected' : '' ?>>Residential</option>
                    <option value="Commercial" <?= $type === 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                    <option value="Government" <?= $type === 'Government' ? 'selected' : '' ?>>Government</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                <a href="<?= BASE_URL ?>/customers" class="btn btn-light border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Customer Data Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>Account No</th>
                    <th>Full Name</th>
                    <th>Barangay & Address</th>
                    <th>Type</th>
                    <th>Meter No</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No customer accounts found matching your query.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= htmlspecialchars($c['customer_code']) ?></td>
                            <td><code><?= htmlspecialchars($c['account_number']) ?></code></td>
                            <td>
                                <a href="<?= BASE_URL ?>/customers?action=view&id=<?= $c['id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                                </a>
                                <div class="text-muted small"><?= htmlspecialchars($c['contact_number'] ?: 'No contact') ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($c['barangay_name']) ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width: 180px;"><?= htmlspecialchars($c['address']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['account_type']) ?></span></td>
                            <td><code><?= htmlspecialchars($c['meter_number'] ?: 'Unassigned') ?></code></td>
                            <td class="fw-bold <?= $c['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= format_money($c['current_balance']) ?>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= strtolower($c['status']) ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/customers?action=view&id=<?= $c['id'] ?>" class="btn btn-sm btn-light border text-primary me-1">
                                    <i class="bi bi-eye-fill"></i> Profile
                                </a>
                                <?php if (in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Billing Staff'], true)): ?>
                                <form action="<?= BASE_URL ?>/customers/toggle" method="POST" class="d-inline" onsubmit="return confirm('Change status for this customer?');">
                                    <?= CSRF::inputField() ?>
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Toggle Status (Active/Disconnected)">
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
            <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalCount ?> accounts)</span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/customers?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&barangay=<?= urlencode($barangay) ?>&type=<?= urlencode($type) ?>">Previous</a>
                </li>
                <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/customers?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&barangay=<?= urlencode($barangay) ?>&type=<?= urlencode($type) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: New Customer -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/customers?action=create" method="POST">
                <?= CSRF::inputField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Register New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="user@example.com">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-medium">Street Address *</label>
                            <input type="text" name="address" class="form-control" placeholder="House #, Street name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Barangay *</label>
                            <select name="barangay_id" class="form-select" required>
                                <option value="">Select Barangay</option>
                                <?php foreach ($barangays as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Account Type *</label>
                            <select name="account_type" class="form-select" required>
                                <option value="Residential">Residential</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Government">Government</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Initial Meter Serial No. *</label>
                            <input type="text" name="meter_number" class="form-control" placeholder="e.g. MTR-99999" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Register Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

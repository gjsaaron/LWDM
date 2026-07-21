<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="mb-3">
    <a href="<?= BASE_URL ?>/customers" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to Customer List</a>
</div>

<!-- Header Profile Summary Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 64px; height: 64px;">
                    <?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($customer['first_name'] . ' ' . ($customer['middle_name'] ? $customer['middle_name'] . ' ' : '') . $customer['last_name']) ?></h4>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($customer['customer_code']) ?></span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($customer['account_type']) ?></span>
                        <span class="badge-status badge-<?= strtolower($customer['status']) ?>"><?= htmlspecialchars($customer['status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-4 border-start-md ps-md-4">
                <div>
                    <div class="text-muted small">Account Number</div>
                    <div class="fw-bold font-monospace fs-5"><?= htmlspecialchars($customer['account_number']) ?></div>
                </div>
                <div>
                    <div class="text-muted small">Current Total Balance</div>
                    <div class="fw-bold fs-4 <?= $customer['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= format_money($customer['current_balance']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs nav-tabs-custom mb-3 border-bottom" id="profileTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'overview'   ? 'active' : '' ?> fw-semibold" data-bs-toggle="tab" data-bs-target="#overview"  type="button">Overview</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'bills'      ? 'active' : '' ?> fw-semibold" data-bs-toggle="tab" data-bs-target="#bills"     type="button">Billing History (<?= count($billList) ?>)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'payments'   ? 'active' : '' ?> fw-semibold" data-bs-toggle="tab" data-bs-target="#payments"  type="button">Payment History (<?= count($paymentList) ?>)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'readings'   ? 'active' : '' ?> fw-semibold" data-bs-toggle="tab" data-bs-target="#readings"  type="button">Meter Readings (<?= count($readingList) ?>)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $activeTab === 'documents'  ? 'active' : '' ?> fw-semibold" data-bs-toggle="tab" data-bs-target="#documents" type="button">
            <i class="bi bi-folder2-open me-1"></i>Documents (<?= count($documentList) ?>)
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="profileTabsContent">

    <!-- OVERVIEW TAB -->
    <div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="overview" role="tabpanel">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>Personal & Contact Info</h6>
                    </div>
                    <div class="card-body px-4">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted w-40">Contact Number:</td><td class="fw-medium"><?= htmlspecialchars($customer['contact_number'] ?: 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Email Address:</td><td class="fw-medium"><?= htmlspecialchars($customer['email'] ?: 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Barangay:</td><td class="fw-medium"><?= htmlspecialchars($customer['barangay_name']) ?></td></tr>
                            <tr><td class="text-muted">Address:</td><td class="fw-medium"><?= htmlspecialchars($customer['address']) ?></td></tr>
                            <tr><td class="text-muted">Date Connected:</td><td class="fw-medium"><?= format_date($customer['date_connected']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-speedometer2 me-2"></i>Meter Information</h6>
                    </div>
                    <div class="card-body px-4">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted w-40">Active Serial No:</td><td class="fw-bold font-monospace"><?= htmlspecialchars($customer['meter_number'] ?: 'Unassigned') ?></td></tr>
                            <tr><td class="text-muted">Account Status:</td><td><span class="badge-status badge-<?= strtolower($customer['status']) ?>"><?= htmlspecialchars($customer['status']) ?></span></td></tr>
                            <tr><td class="text-muted">Last Bill Issued:</td><td class="fw-medium"><?= !empty($billList) ? format_money($billList[0]['total_amount']) . ' (' . format_date($billList[0]['billing_period'], 'M Y') . ')' : 'No bills yet' ?></td></tr>
                            <tr><td class="text-muted">Last Payment:</td><td class="fw-medium"><?= !empty($paymentList) ? format_money($paymentList[0]['amount_paid']) . ' on ' . format_date($paymentList[0]['payment_date']) : 'None' ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BILLS TAB -->
    <div class="tab-pane fade" id="bills" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Billing Month</th>
                            <th>Prev - Curr Reading</th>
                            <th>Consumption</th>
                            <th>Subtotal</th>
                            <th>Total Due</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($billList)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No billing history recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($billList as $b): ?>
                                <tr>
                                    <td class="fw-bold font-monospace"><?= htmlspecialchars($b['bill_number']) ?></td>
                                    <td><?= format_date($b['billing_period'], 'F Y') ?></td>
                                    <td><?= number_format($b['prev_reading_val']) ?> &rarr; <?= number_format($b['curr_reading_val']) ?></td>
                                    <td class="fw-semibold"><?= number_format($b['consumption_val']) ?> m³</td>
                                    <td><?= format_money($b['subtotal']) ?></td>
                                    <td class="fw-bold text-dark"><?= format_money($b['total_amount']) ?></td>
                                    <td><?= format_date($b['due_date']) ?></td>
                                    <td><span class="badge-status badge-<?= strtolower(str_replace(' ', '', $b['status'])) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/billing?action=view&id=<?= $b['id'] ?>" class="btn btn-sm btn-light border" title="View Printable Bill">
                                            <i class="bi bi-printer-fill text-primary"></i> Bill
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PAYMENTS TAB -->
    <div class="tab-pane fade" id="payments" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>OR Number</th>
                            <th>Date & Time</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Cashier</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paymentList)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No payment transactions recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($paymentList as $p): ?>
                                <tr>
                                    <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($p['or_number']) ?></td>
                                    <td><?= format_date($p['payment_date'], 'M d, Y h:i A') ?></td>
                                    <td class="fw-bold text-success"><?= format_money($p['amount_paid']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                                    <td><?= htmlspecialchars($p['cashier_name']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/payments?action=receipt&id=<?= $p['id'] ?>" class="btn btn-sm btn-light border" title="View Official Receipt">
                                            <i class="bi bi-receipt text-success"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- READINGS TAB -->
    <div class="tab-pane fade" id="readings" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Reading Date</th>
                            <th>Meter No</th>
                            <th>Prev Reading</th>
                            <th>Curr Reading</th>
                            <th>Consumption</th>
                            <th>Meter Reader</th>
                            <th>Anomaly</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($readingList)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No meter reading entries recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($readingList as $r): ?>
                                <tr class="<?= $r['is_anomaly'] ? 'anomaly-row' : '' ?>">
                                    <td><?= format_date($r['reading_date']) ?></td>
                                    <td><code><?= htmlspecialchars($r['meter_number']) ?></code></td>
                                    <td><?= number_format($r['previous_reading']) ?></td>
                                    <td class="fw-bold"><?= number_format($r['current_reading']) ?></td>
                                    <td class="fw-bold text-primary"><?= number_format($r['consumption']) ?> m³</td>
                                    <td><?= htmlspecialchars($r['reader_name']) ?></td>
                                    <td>
                                        <?php if ($r['is_anomaly']): ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> High Usage</span>
                                        <?php else: ?>
                                            <span class="text-muted small">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DOCUMENTS TAB -->
    <div class="tab-pane fade <?= $activeTab === 'documents' ? 'show active' : '' ?>" id="documents" role="tabpanel">
        <div class="row g-4">
            <!-- Upload Form -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-upload me-2"></i>Upload New Document</h6>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <form action="<?= BASE_URL ?>/documents/upload" method="POST" enctype="multipart/form-data">
                            <?= CSRF::inputField() ?>
                            <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label fw-medium small">Document Type *</label>
                                <select name="document_type" class="form-select form-select-sm" required>
                                    <option value="">Select type…</option>
                                    <option>Valid ID</option>
                                    <option>Proof of Address</option>
                                    <option>Application Form</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium small">File * <span class="text-muted">(PDF, JPG, PNG — max 5 MB)</span></label>
                                <input type="file" name="document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-sm w-100">
                                <i class="bi bi-upload me-1"></i>Upload Document
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Documents List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-files me-2"></i>Uploaded Documents</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($documentList)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-folder2-open display-5 d-block mb-2 opacity-25"></i>
                                No documents uploaded yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-custom">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Type</th>
                                            <th>Uploaded</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documentList as $doc): ?>
                                        <?php
                                            $ext  = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                            $icon = match($ext) {
                                                'pdf'  => 'bi-file-earmark-pdf-fill text-danger',
                                                'jpg', 'jpeg', 'png' => 'bi-file-earmark-image-fill text-primary',
                                                default => 'bi-file-earmark-fill text-secondary',
                                            };
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="bi <?= $icon ?> me-2"></i>
                                                <?= htmlspecialchars($doc['file_name']) ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($doc['document_type']) ?></span></td>
                                            <td class="text-muted small"><?= format_date($doc['uploaded_at'], 'M d, Y') ?></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>/documents/download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary me-1" target="_blank" title="View/Download">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <form method="POST" action="<?= BASE_URL ?>/documents/delete" class="d-inline"
                                                      onsubmit="return confirm('Delete this document permanently?')">
                                                    <?= CSRF::inputField() ?>
                                                    <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

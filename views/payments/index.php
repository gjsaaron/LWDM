<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-register me-2 text-primary"></i>Cashier Payment Terminal</h4>
        <p class="text-muted small mb-0">Accept payments, settle bills, and print official receipts</p>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?><?php unset($_SESSION['flash_success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?><?php unset($_SESSION['flash_error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── POS Panel ─────────────────────────────────────────────────────── -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-search me-2 text-primary"></i>Account Lookup</h6>
            </div>
            <div class="card-body">

                <!-- Live Search -->
                <div class="position-relative mb-4" id="searchWrapper">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge"></i></span>
                        <input type="text" id="posSearch" class="form-control border-start-0 shadow-none"
                               placeholder="Type account number, name, or meter number..." autocomplete="off">
                        <span class="input-group-text bg-light" id="searchSpinner" style="display:none;">
                            <span class="spinner-border spinner-border-sm text-primary"></span>
                        </span>
                    </div>
                    <!-- Dropdown Results -->
                    <div id="searchResults" class="dropdown-menu w-100 shadow-sm" style="display:none; max-height: 260px; overflow-y:auto;">
                    </div>
                </div>

                <!-- Customer Info Panel (hidden until selected) -->
                <div id="customerPanel" style="display:none;">
                    <div class="alert alert-light border d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary-subtle rounded-circle p-2"><i class="bi bi-person-fill text-primary fs-4"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-6" id="panelName">—</div>
                            <div class="text-muted small" id="panelMeta">—</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Balance Due</div>
                            <div class="fw-bold text-danger fs-5" id="panelBalance">₱0.00</div>
                        </div>
                    </div>

                    <!-- Unpaid Bills Table -->
                    <h6 class="fw-semibold mb-2">Unpaid Bills (FIFO order)</h6>
                    <div class="table-responsive mb-3" id="billsTable" style="max-height:220px; overflow-y:auto;">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill #</th>
                                    <th>Period</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="billsBody">
                                <tr><td colspan="5" class="text-center text-muted py-3">No unpaid bills.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?= BASE_URL ?>/payments" method="POST" id="paymentForm">
                        <?= CSRF::inputField() ?>
                        <input type="hidden" name="account_id" id="hiddenAccountId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Payment Amount (₱) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="amount_paid" id="amountPaid" class="form-control fw-bold fs-5"
                                           step="0.01" min="0.01" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Payment Method *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="GCash">GCash</option>
                                    <option value="PayMaya">PayMaya</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Reference / Check No.</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Remarks</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                                    <i class="bi bi-check-circle-fill me-2"></i>Process Payment & Print Receipt
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Empty state -->
                <div id="emptyState" class="text-center py-5">
                    <i class="bi bi-person-badge fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">Search for a customer account above to begin payment processing.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Recent Transactions ───────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-success me-2"></i>Recent Transactions</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>OR Number</th>
                            <th>Customer</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No transactions yet today.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $rp): ?>
                                <tr>
                                    <td class="font-monospace small fw-bold text-primary"><?= htmlspecialchars($rp['or_number']) ?></td>
                                    <td class="small fw-semibold"><?= htmlspecialchars($rp['customer_name']) ?></td>
                                    <td class="text-end fw-bold text-success small"><?= format_money($rp['amount_paid']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/payments?action=receipt&id=<?= $rp['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary" target="_blank">
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
    </div>
</div>

<!-- ── Live Search JavaScript ──────────────────────────────────────────────── -->
<script>
const API = '<?= BASE_URL ?>/api';
let debounceTimer = null;
let currentAccount = null;

const posSearch   = document.getElementById('posSearch');
const results     = document.getElementById('searchResults');
const spinner     = document.getElementById('searchSpinner');
const panel       = document.getElementById('customerPanel');
const emptyState  = document.getElementById('emptyState');
const hiddenId    = document.getElementById('hiddenAccountId');
const panelName   = document.getElementById('panelName');
const panelMeta   = document.getElementById('panelMeta');
const panelBalance= document.getElementById('panelBalance');
const billsBody   = document.getElementById('billsBody');

posSearch.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value.trim();
    if (q.length < 2) { results.style.display = 'none'; return; }
    debounceTimer = setTimeout(() => liveSearch(q), 280);
});

function liveSearch(q) {
    spinner.style.display = 'flex';
    fetch(`${API}?action=search-customers&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            spinner.style.display = 'none';
            if (!data.results || data.results.length === 0) {
                results.innerHTML = `<span class="dropdown-item text-muted">No accounts found for "${q}"</span>`;
            } else {
                results.innerHTML = data.results.map(a => `
                    <a href="#" class="dropdown-item py-2 px-3" onclick="selectAccount(${a.id}); return false;">
                        <div class="fw-semibold">${a.customer_name}</div>
                        <small class="text-muted font-monospace">${a.account_number} · Meter: ${a.meter_number || 'N/A'}</small>
                        <span class="float-end badge-status badge-${a.status.toLowerCase()}">${a.status}</span>
                    </a>`).join('<div class="dropdown-divider my-0"></div>');
            }
            results.style.display = 'block';
        })
        .catch(() => { spinner.style.display = 'none'; });
}

function selectAccount(id) {
    results.style.display = 'none';
    fetch(`${API}?action=account-bills&account_id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.account) return;
            const a = data.account;
            currentAccount = a;
            hiddenId.value = a.account_id || a.id;
            posSearch.value = `${a.customer_name} (${a.account_number})`;

            panelName.textContent = a.customer_name;
            panelMeta.textContent = `${a.account_number} · ${a.meter_number || 'N/A'} · ${a.barangay_name}`;
            panelBalance.textContent = '₱' + parseFloat(a.current_balance || 0).toLocaleString('en-PH', {minimumFractionDigits:2});

            if (data.bills.length === 0) {
                billsBody.innerHTML = `<tr><td colspan="5" class="text-center text-success py-3"><i class="bi bi-check-circle me-1"></i>No outstanding bills!</td></tr>`;
            } else {
                billsBody.innerHTML = data.bills.map(b => {
                    const bal = (parseFloat(b.total_amount) - parseFloat(b.amount_paid)).toFixed(2);
                    const isOverdue = b.status === 'Overdue';
                    return `<tr class="${isOverdue ? 'table-danger' : ''}">
                        <td class="font-monospace small fw-bold">${b.bill_number}</td>
                        <td class="small">${b.billing_period?.substring(0,7)}</td>
                        <td class="small">${b.due_date}</td>
                        <td><span class="badge-status badge-${b.status.toLowerCase().replace(' ','-')}">${b.status}</span></td>
                        <td class="text-end fw-bold">₱${parseFloat(bal).toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
                    </tr>`;
                }).join('');
                // Auto-fill total balance in amount field
                document.getElementById('amountPaid').value = parseFloat(a.current_balance || 0).toFixed(2);
            }

            emptyState.style.display = 'none';
            panel.style.display = 'block';
        });
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    if (!document.getElementById('searchWrapper').contains(e.target)) {
        results.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

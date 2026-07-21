<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Meter Readings Module</h4>
        <p class="text-muted small mb-0">Encode monthly water meter readings and monitor abnormal usage patterns</p>
    </div>
    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#encodeReadingModal">
        <i class="bi bi-speedometer2 me-1"></i> Encode Meter Reading
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

<!-- Meter Reading Search Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASE_URL ?>/meters" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search account number, meter number, or customer name..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Meter Reading Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-custom">
            <thead>
                <tr>
                    <th>Reading Date</th>
                    <th>Account No</th>
                    <th>Customer Name</th>
                    <th>Meter Serial</th>
                    <th>Prev Reading</th>
                    <th>Curr Reading</th>
                    <th>Consumption</th>
                    <th>Reader Name</th>
                    <th>Status / Alerts</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($readings)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No meter reading logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($readings as $r): ?>
                        <tr class="<?= $r['is_anomaly'] ? 'anomaly-row' : '' ?>">
                            <td><?= format_date($r['reading_date']) ?></td>
                            <td><code><?= htmlspecialchars($r['account_number']) ?></code></td>
                            <td>
                                <a href="<?= BASE_URL ?>/customers?action=view&id=<?= $r['customer_id'] ?>" class="text-decoration-none fw-semibold text-dark">
                                    <?= htmlspecialchars($r['customer_name']) ?>
                                </a>
                            </td>
                            <td><code><?= htmlspecialchars($r['meter_number']) ?></code></td>
                            <td><?= number_format($r['previous_reading']) ?></td>
                            <td class="fw-bold"><?= number_format($r['current_reading']) ?></td>
                            <td class="fw-bold text-primary"><?= number_format($r['consumption']) ?> m³</td>
                            <td><?= htmlspecialchars($r['reader_name']) ?></td>
                            <td>
                                <?php if ($r['is_anomaly']): ?>
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> High Usage</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i> Normal</span>
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
            <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalCount ?> readings)</span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/meters?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                </li>
                <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>/meters?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Encode Reading -->
<div class="modal fade" id="encodeReadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>/meters" method="POST">
                <?= CSRF::inputField() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-speedometer2 me-2 text-primary"></i>Encode Meter Reading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 position-relative" id="modalSearchWrapper">
                        <label class="form-label fw-medium">Search Active Customer / Meter *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" id="modalSearchInput" class="form-control shadow-none" placeholder="Type account #, name, or meter serial..." autocomplete="off" required>
                            <span class="input-group-text bg-light" id="modalSearchSpinner" style="display:none;">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </span>
                        </div>
                        <div id="modalSearchResults" class="dropdown-menu w-100 shadow-sm" style="display:none; max-height: 220px; overflow-y:auto;">
                        </div>
                        <input type="hidden" name="account_id" id="modalAccountIdInput" required>
                        <input type="hidden" name="meter_id" id="modalMeterIdInput" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium">Previous Reading</label>
                            <input type="number" id="prevReadingDisplay" class="form-control bg-light" readonly value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium">Current Reading *</label>
                            <input type="number" name="current_reading" id="currentReadingInput" class="form-control" min="0" required disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Reading Date *</label>
                        <input type="date" name="reading_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Meter Reader Name *</label>
                        <input type="text" name="reader_name" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Save Reading</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('modalSearchInput');
    const searchSpinner = document.getElementById('modalSearchSpinner');
    const searchResults = document.getElementById('modalSearchResults');
    const accountIdInput = document.getElementById('modalAccountIdInput');
    const meterIdInput = document.getElementById('modalMeterIdInput');
    const prevReadingInput = document.getElementById('prevReadingDisplay');
    const currentReadingInput = document.getElementById('currentReadingInput');
    
    let debounceTimer;
    
    searchInput?.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        
        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            searchSpinner.style.display = 'flex';
            fetch(`<?= BASE_URL ?>/api?action=search-meters&q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    searchSpinner.style.display = 'none';
                    if (!data.results || data.results.length === 0) {
                        searchResults.innerHTML = `<span class="dropdown-item text-muted">No active accounts found for "${q}"</span>`;
                    } else {
                        searchResults.innerHTML = data.results.map(r => `
                            <a href="#" class="dropdown-item py-2 px-3" data-account-id="${r.account_id}" data-meter-id="${r.meter_id}" data-last-reading="${r.last_reading}" data-display-text="${r.customer_name} (${r.account_number})">
                                <div class="fw-semibold">${r.customer_name}</div>
                                <small class="text-muted font-monospace">${r.account_number} · Meter: ${r.meter_number}</small>
                                <span class="float-end badge bg-light text-dark border">Last: ${r.last_reading}</span>
                            </a>
                        `).join('<div class="dropdown-divider my-0"></div>');
                    }
                    searchResults.style.display = 'block';
                })
                .catch(() => {
                    searchSpinner.style.display = 'none';
                });
        }, 300);
    });

    // Handle selection from search results
    searchResults?.addEventListener('click', function(e) {
        const item = e.target.closest('.dropdown-item');
        if (!item) return;
        e.preventDefault();
        
        const accountId = item.getAttribute('data-account-id');
        const meterId = item.getAttribute('data-meter-id');
        const lastReading = parseInt(item.getAttribute('data-last-reading') || 0);
        const displayText = item.getAttribute('data-display-text');
        
        accountIdInput.value = accountId;
        meterIdInput.value = meterId;
        prevReadingInput.value = lastReading;
        
        currentReadingInput.min = lastReading;
        currentReadingInput.disabled = false;
        currentReadingInput.value = '';
        currentReadingInput.focus();
        
        searchInput.value = displayText;
        searchResults.style.display = 'none';
    });

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!document.getElementById('modalSearchWrapper')?.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Reset form fields when modal is closed
    document.getElementById('encodeReadingModal')?.addEventListener('hidden.bs.modal', function () {
        searchInput.value = '';
        accountIdInput.value = '';
        meterIdInput.value = '';
        prevReadingInput.value = '0';
        currentReadingInput.value = '';
        currentReadingInput.disabled = true;
        searchResults.style.display = 'none';
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

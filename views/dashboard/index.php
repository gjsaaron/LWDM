<?php
require_once __DIR__ . '/../layouts/header.php';

// Prepare chart JSON payloads
$monthlyLabels   = json_encode(array_column($monthlyChartData, 'label'));
$monthlyTotals   = json_encode(array_map('floatval', array_column($monthlyChartData, 'total')));

$statusMap   = ['Paid' => 0, 'Unpaid' => 0, 'Partially Paid' => 0, 'Overdue' => 0];
foreach ($billStatusData as $row) {
    if (isset($statusMap[$row['status']])) $statusMap[$row['status']] = (int)$row['cnt'];
}
$statusLabels = json_encode(array_keys($statusMap));
$statusCounts = json_encode(array_values($statusMap));

$growthLabels = json_encode(array_column($customerGrowth, 'label'));
$growthCounts = json_encode(array_map('intval', array_column($customerGrowth, 'cnt')));
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Executive Dashboard</h4>
        <p class="text-muted small mb-0">
            <?= date('l, F j, Y — g:i A') ?> &nbsp;|&nbsp;
            Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Staff') ?></strong>
        </p>
    </div>
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-7">
        <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>
    </span>
</div>

<!-- ── Metric Cards ─────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-3">
        <div class="card-metric">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Customers</div>
                    <div class="fs-2 fw-bold lh-1"><?= number_format($totalCustomers) ?></div>
                    <div class="small text-success mt-2">
                        <i class="bi bi-person-check me-1"></i><?= number_format($activeCustomers) ?> Active
                        &nbsp;<span class="text-muted">·</span>&nbsp;
                        <i class="bi bi-person-plus ms-1 me-1"></i><?= $newThisMonth ?> New this month
                    </div>
                </div>
                <div class="metric-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-metric">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Collections Today</div>
                    <div class="fs-2 fw-bold lh-1 text-success"><?= format_money($todayCollections) ?></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-calendar-month me-1"></i>Monthly: <strong><?= format_money($monthlyCollections) ?></strong>
                    </div>
                </div>
                <div class="metric-icon bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-metric">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Outstanding Balance</div>
                    <div class="fs-2 fw-bold lh-1 text-danger"><?= format_money($outstandingBalance) ?></div>
                    <div class="small text-muted mt-2">
                        <i class="bi bi-file-earmark-x me-1"></i><?= number_format($unpaidBillsCount) ?> Unpaid Bills
                    </div>
                </div>
                <div class="metric-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-metric">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Overdue Accounts</div>
                    <div class="fs-2 fw-bold lh-1 text-warning"><?= number_format($overdueCount) ?></div>
                    <div class="d-flex gap-2 mt-2">
                        <a href="<?= BASE_URL ?>/payments" class="btn btn-sm btn-primary-custom">
                            <i class="bi bi-cash-stack me-1"></i>POS
                        </a>
                        <a href="<?= BASE_URL ?>/delinquents" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Delinquents
                        </a>
                    </div>
                </div>
                <div class="metric-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>

</div>

<!-- ── Charts Row ─────────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">

    <!-- Monthly Collections Bar Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Monthly Collections — Last 6 Months</h6>
                <a href="<?= BASE_URL ?>/reports" class="btn btn-sm btn-light border">Full Report &rarr;</a>
            </div>
            <div class="card-body p-3" style="height: 260px;">
                <canvas id="monthlyCollectionsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bill Status Doughnut -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Bill Status Breakdown</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center p-3" style="height: 260px;">
                <canvas id="billStatusChart"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- ── Customer Growth + Activity Row ─────────────────────────────────────── -->
<div class="row g-4">

    <!-- Customer Growth Line Chart -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow text-success me-2"></i>New Customer Connections</h6>
            </div>
            <div class="card-body p-3" style="height: 230px;">
                <canvas id="customerGrowthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt-cutoff text-success me-2"></i>Recent Collections</h6>
                <a href="<?= BASE_URL ?>/payments" class="btn btn-sm btn-light border">Cashier Terminal &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>OR Number</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payments recorded today.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPayments as $rp): ?>
                                <tr>
                                    <td class="fw-bold font-monospace text-primary small"><?= htmlspecialchars($rp['or_number']) ?></td>
                                    <td class="fw-semibold small"><?= htmlspecialchars($rp['customer_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars($rp['payment_method']) ?></span></td>
                                    <td class="fw-bold text-success"><?= format_money($rp['amount_paid']) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/payments?action=receipt&id=<?= $rp['id'] ?>" class="btn btn-sm btn-light border">
                                            <i class="bi bi-printer-fill text-dark"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Audit Log bottom section -->
            <div class="card-header bg-light border-top border-0 py-2">
                <h6 class="fw-semibold mb-0 text-muted small"><i class="bi bi-activity me-1"></i>Recent Activity Log</h6>
            </div>
            <ul class="list-group list-group-flush border-0">
                <?php foreach (array_slice($recentLogs, 0, 4) as $log): ?>
                    <li class="list-group-item py-2 px-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 fs-8"><?= htmlspecialchars($log['action']) ?></span>
                                <small class="text-muted"><?= htmlspecialchars($log['employee_name'] ?: 'System') ?></small>
                            </div>
                            <small class="text-muted fs-8"><?= format_date($log['created_at'], 'h:i A') ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
const labelColor = isDark ? '#94a3b8' : '#64748b';

// ── Chart 1: Monthly Collections Bar ────────────────────────────────────────
new Chart(document.getElementById('monthlyCollectionsChart'), {
    type: 'bar',
    data: {
        labels: <?= $monthlyLabels ?>,
        datasets: [{
            label: 'Collections (₱)',
            data: <?= $monthlyTotals ?>,
            backgroundColor: 'rgba(2, 132, 199, 0.75)',
            borderColor: '#0284c7',
            borderWidth: 1.5,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                ticks: { color: labelColor, callback: v => '₱' + v.toLocaleString() },
                grid: { color: gridColor }
            },
            x: { ticks: { color: labelColor }, grid: { display: false } }
        }
    }
});

// ── Chart 2: Bill Status Doughnut ────────────────────────────────────────────
new Chart(document.getElementById('billStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= $statusLabels ?>,
        datasets: [{
            data: <?= $statusCounts ?>,
            backgroundColor: ['#10b981','#f59e0b','#3b82f6','#ef4444'],
            borderWidth: 2,
            borderColor: isDark ? '#1e293b' : '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: labelColor, padding: 12, font: { size: 11 } }
            }
        }
    }
});

// ── Chart 3: Customer Growth Line ────────────────────────────────────────────
new Chart(document.getElementById('customerGrowthChart'), {
    type: 'line',
    data: {
        labels: <?= $growthLabels ?>,
        datasets: [{
            label: 'New Connections',
            data: <?= $growthCounts ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.12)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10b981',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                ticks: { color: labelColor },
                grid: { color: gridColor }
            },
            x: { ticks: { color: labelColor }, grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

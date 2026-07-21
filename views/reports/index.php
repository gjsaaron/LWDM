<?php
require_once __DIR__ . '/../layouts/header.php';

$grandTotalDaily   = array_sum(array_column($dailyCollections, 'total_amount'));
$grandTotalMonthly = array_sum(array_column($monthlyCollections, 'total_amount'));
$grandOutstanding  = array_sum(array_column($outstanding, 'current_balance'));
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Reports & Financial Analytics</h4>
        <p class="text-muted small mb-0">Generate, view, and export daily/monthly collections and outstanding balance reports</p>
    </div>
    <small class="text-muted">Generated: <?= date('M d, Y h:i A') ?></small>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-tabs fw-semibold mb-4" id="reportTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-daily" type="button"><i class="bi bi-calendar-day me-1"></i>Daily Collections</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-monthly" type="button"><i class="bi bi-calendar-month me-1"></i>Monthly Collections</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-outstanding" type="button"><i class="bi bi-exclamation-circle-fill me-1 text-danger"></i>Outstanding Accounts</button></li>
</ul>

<div class="tab-content" id="reportTabContent">

    <!-- ── Daily Collections ───────────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-daily">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Daily Collection Summary <small class="text-muted fw-normal">(Last 30 days)</small></h6>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/reports?export=daily" class="btn btn-sm btn-success">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary d-print-none">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom" id="dailyTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th class="text-center">No. of Transactions</th>
                            <th class="text-end">Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dailyCollections as $dc): ?>
                            <tr>
                                <td class="fw-semibold"><?= format_date($dc['pay_date'], 'M d, Y') ?></td>
                                <td class="text-muted"><?= format_date($dc['pay_date'], 'l') ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format($dc['tx_count']) ?></span></td>
                                <td class="text-end fw-bold text-success"><?= format_money($dc['total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end">GRAND TOTAL (30 days):</td>
                            <td class="text-end text-primary fs-6"><?= format_money($grandTotalDaily) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Monthly Collections ─────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-monthly">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Monthly Collection Summary <small class="text-muted fw-normal">(Last 12 months)</small></h6>
                <a href="<?= BASE_URL ?>/reports?export=monthly" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Billing Month</th>
                            <th class="text-center">No. of Transactions</th>
                            <th class="text-end">Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyCollections as $mc): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($mc['label']) ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= number_format($mc['tx_count']) ?></span></td>
                                <td class="text-end fw-bold text-primary"><?= format_money($mc['total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td class="text-end">GRAND TOTAL (12 months):</td>
                            <td></td>
                            <td class="text-end text-primary fs-6"><?= format_money($grandTotalMonthly) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Outstanding Accounts ────────────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-outstanding">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">Outstanding / Unpaid Accounts <small class="text-muted fw-normal">(Top 30 by balance)</small></h6>
                <a href="<?= BASE_URL ?>/reports?export=outstanding" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th>Account No</th>
                            <th>Customer Name</th>
                            <th>Barangay</th>
                            <th>Type</th>
                            <th class="text-center">Unpaid Bills</th>
                            <th class="text-end">Balance Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outstanding as $o): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-primary"><?= htmlspecialchars($o['account_number']) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($o['customer_name']) ?></td>
                                <td><?= htmlspecialchars($o['barangay_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['account_type']) ?></span></td>
                                <td class="text-center"><span class="badge bg-danger"><?= $o['unpaid_count'] ?></span></td>
                                <td class="text-end fw-bold text-danger"><?= format_money($o['current_balance']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="5" class="text-end">TOTAL OUTSTANDING:</td>
                            <td class="text-end text-danger fs-6"><?= format_money($grandOutstanding) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<?php
// app/controllers/BillingController.php

require_once __DIR__ . '/../helpers/BillingCalculator.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class BillingController {
    public function index(): void {
        $pdo = Database::getConnection();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT b.*, ca.account_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.account_type, c.id as customer_id
                FROM billing b
                JOIN customer_accounts ca ON b.account_id = ca.id
                JOIN customers c ON ca.customer_id = c.id
                WHERE b.deleted_at IS NULL";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (b.bill_number LIKE ? OR ca.account_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
            $term = "%$search%";
            $params = array_merge($params, [$term, $term, $term]);
        }

        if (!empty($status)) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM ($sql) as t");
        $cStmt->execute($params);
        $totalCount = (int)$cStmt->fetchColumn();
        $totalPages = max(1, ceil($totalCount / $limit));

        $sql .= " ORDER BY b.id DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bills = $stmt->fetchAll();

        // Recent billing jobs
        $jobs = $pdo->query("SELECT * FROM billing_jobs ORDER BY id DESC LIMIT 5")->fetchAll();

        $pageTitle = "Monthly Billing Module";
        require_once __DIR__ . '/../../views/billing/index.php';
    }

    public function generateBatch(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/billing');
        }

        $billingPeriod = trim($_POST['billing_period'] ?? date('Y-m-01'));
        $dueDate = trim($_POST['due_date'] ?? date('Y-m-15', strtotime('+1 month')));

        $pdo = Database::getConnection();

        // Create billing job record
        $stmt = $pdo->prepare("INSERT INTO billing_jobs (billing_period, status) VALUES (?, 'In Progress')");
        $stmt->execute([$billingPeriod]);
        $jobId = $pdo->lastInsertId();

        // Fetch active accounts with recent unbilled readings
        $accounts = $pdo->query("SELECT ca.id as account_id, ca.current_balance, c.account_type,
                                 (SELECT mr.id FROM meter_readings mr WHERE mr.account_id = ca.id ORDER BY mr.id DESC LIMIT 1) as latest_reading_id,
                                 (SELECT mr.previous_reading FROM meter_readings mr WHERE mr.account_id = ca.id ORDER BY mr.id DESC LIMIT 1) as prev_val,
                                 (SELECT mr.current_reading FROM meter_readings mr WHERE mr.account_id = ca.id ORDER BY mr.id DESC LIMIT 1) as curr_val
                                 FROM customer_accounts ca
                                 JOIN customers c ON ca.customer_id = c.id
                                 WHERE ca.status = 'Active'")->fetchAll();

        $totalAccounts = count($accounts);
        $processed = 0;
        $failed = 0;
        $errors = [];

        $bStmt = $pdo->prepare("INSERT INTO billing (bill_number, account_id, meter_reading_id, billing_period, prev_reading_val, curr_reading_val, consumption_val, applied_min_rate, applied_rate_per_m3, subtotal, tax, penalty_amount, previous_unpaid, total_amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, 'Unpaid')");

        foreach ($accounts as $acct) {
            try {
                if (empty($acct['latest_reading_id'])) {
                    continue;
                }

                // Check if already billed for this period
                $chk = $pdo->prepare("SELECT id FROM billing WHERE account_id = ? AND billing_period = ? AND deleted_at IS NULL LIMIT 1");
                $chk->execute([$acct['account_id'], $billingPeriod]);
                if ($chk->fetch()) {
                    continue; // Skip already billed
                }

                $calc = BillingCalculator::calculate($acct['account_type'], (int)$acct['prev_val'], (int)$acct['curr_val'], (float)$acct['current_balance']);

                $billNo = 'BILL-' . date('Ym', strtotime($billingPeriod)) . '-' . str_pad($acct['account_id'], 5, '0', STR_PAD_LEFT);

                $bStmt->execute([
                    $billNo, $acct['account_id'], $acct['latest_reading_id'], $billingPeriod,
                    $acct['prev_val'], $acct['curr_val'], $calc['consumption'],
                    $calc['applied_min_rate'], $calc['applied_rate_per_m3'],
                    $calc['subtotal'], $calc['tax'], $calc['previous_unpaid'], $calc['total_amount'],
                    $dueDate
                ]);

                // Update account balance
                $pdo->prepare("UPDATE customer_accounts SET current_balance = ?, total_amount_due = ? WHERE id = ?")
                    ->execute([$calc['total_amount'], $calc['total_amount'], $acct['account_id']]);

                $processed++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Account #{$acct['account_id']}: " . $e->getMessage();
            }
        }

        // Update Job completion
        $pdo->prepare("UPDATE billing_jobs SET total_accounts = ?, processed_accounts = ?, failed_accounts = ?, status = 'Completed', error_log = ? WHERE id = ?")
            ->execute([$totalAccounts, $processed, $failed, json_encode($errors), $jobId]);

        // Audit log
        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user['id'], $user['name'], 'Batch Billing Run', "billing_jobs#$jobId ($processed bills generated)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

        $_SESSION['flash_success'] = "Batch billing run completed. $processed bills generated ($failed failed).";
        redirect('/billing');
    }

    public function viewBill(): void {
        $id = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT b.*, ca.account_number, c.customer_code, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.address, c.account_type, b.applied_min_rate, b.applied_rate_per_m3, b.subtotal, b.total_amount, b.due_date, m.meter_number, bg.name as barangay_name
                               FROM billing b
                               JOIN customer_accounts ca ON b.account_id = ca.id
                               JOIN customers c ON ca.customer_id = c.id
                               JOIN barangays bg ON c.barangay_id = bg.id
                               JOIN meter_readings mr ON b.meter_reading_id = mr.id
                               JOIN meters m ON mr.meter_id = m.id
                               WHERE b.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $bill = $stmt->fetch();

        if (!$bill) {
            redirect('/billing');
        }

        $pageTitle = "Water Statement of Account - " . $bill['bill_number'];
        require_once __DIR__ . '/../../views/billing/view.php';
    }

    public function applyPenalties(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request.';
            redirect('/billing');
        }

        $pdo  = Database::getConnection();
        $user = AuthMiddleware::currentUser();

        // Mark unpaid bills past due_date as Overdue
        $overdue = $pdo->query("UPDATE billing
            SET status = 'Overdue'
            WHERE status = 'Unpaid'
              AND due_date < CURDATE()
              AND deleted_at IS NULL");
        $markedOverdue = $overdue->rowCount();

        // Apply penalty_amount on newly overdue bills that have no penalty yet
        $pendingPenalty = $pdo->query("SELECT b.id, b.total_amount, b.account_id,
                                              COALESCE(wr.penalty_rate, 10) AS penalty_rate
                                       FROM billing b
                                       JOIN customer_accounts ca ON b.account_id = ca.id
                                       JOIN customers c ON ca.customer_id = c.id
                                       JOIN water_rates wr ON wr.account_type = c.account_type AND wr.status = 'Active'
                                       WHERE b.status = 'Overdue'
                                         AND b.penalty_amount = 0
                                         AND b.deleted_at IS NULL")->fetchAll();

        $penaltyStmt = $pdo->prepare("UPDATE billing
            SET penalty_amount = ROUND(subtotal * ? / 100, 2),
                total_amount = total_amount + ROUND(subtotal * ? / 100, 2)
            WHERE id = ?");

        $penalized = 0;
        foreach ($pendingPenalty as $bill) {
            $rate = (float)$bill['penalty_rate'];
            $penaltyStmt->execute([$rate, $rate, $bill['id']]);
            $penalized++;
        }

        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Apply Overdue Penalties', "$markedOverdue bills marked overdue, $penalized penalties applied", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = "Penalty run complete: $markedOverdue bills marked Overdue, $penalized penalty charges applied.";
        redirect('/billing');
    }

    public function downloadPdf(): void {
        $id  = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT b.*, ca.account_number, c.customer_code,
                                      CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                                      c.address, c.account_type,
                                      m.meter_number, bg.name AS barangay_name
                               FROM billing b
                               JOIN customer_accounts ca ON b.account_id  = ca.id
                               JOIN customers c          ON ca.customer_id = c.id
                               JOIN barangays bg         ON c.barangay_id  = bg.id
                               JOIN meter_readings mr    ON b.meter_reading_id = mr.id
                               JOIN meters m             ON mr.meter_id = m.id
                               WHERE b.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $bill = $stmt->fetch();

        if (!$bill) {
            redirect('/billing');
        }

        $settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")
                        ->fetchAll(PDO::FETCH_KEY_PAIR);

        require_once __DIR__ . '/../helpers/PdfGenerator.php';
        $html = PdfGenerator::billHtml($bill, $settings);
        PdfGenerator::fromHtml($html, 'SOA-' . $bill['bill_number'] . '.pdf', false);
    }
}

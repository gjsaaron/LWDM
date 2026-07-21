<?php
// app/controllers/DashboardController.php

require_once __DIR__ . '/../../config/database.php';

class DashboardController {
    public function index(): void {
        $pdo = Database::getConnection();

        // ── Summary metric cards ──────────────────────────────────────────────
        $totalCustomers   = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL")->fetchColumn();
        $activeCustomers  = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status='Active' AND deleted_at IS NULL")->fetchColumn();
        $newThisMonth     = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE MONTH(date_connected)=MONTH(CURDATE()) AND YEAR(date_connected)=YEAR(CURDATE()) AND deleted_at IS NULL")->fetchColumn();

        $todayCollections   = (float)($pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE DATE(payment_date)=CURDATE() AND deleted_at IS NULL")->fetchColumn());
        $monthlyCollections = (float)($pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND deleted_at IS NULL")->fetchColumn());

        $outstandingBalance = (float)($pdo->query("SELECT COALESCE(SUM(current_balance),0) FROM customer_accounts WHERE deleted_at IS NULL")->fetchColumn());
        $unpaidBillsCount   = (int)$pdo->query("SELECT COUNT(*) FROM billing WHERE status IN ('Unpaid','Partially Paid','Overdue') AND deleted_at IS NULL")->fetchColumn();
        $overdueCount       = (int)$pdo->query("SELECT COUNT(*) FROM billing WHERE status='Overdue' AND deleted_at IS NULL")->fetchColumn();

        // ── Chart 1: Monthly Collections – last 6 months ─────────────────────
        $monthlyChartData = $pdo->query("
            SELECT DATE_FORMAT(payment_date,'%b %Y') AS label,
                   COALESCE(SUM(amount_paid),0)      AS total
            FROM payments
            WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              AND deleted_at IS NULL
            GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
            ORDER BY MIN(payment_date) ASC
            LIMIT 6
        ")->fetchAll();

        // ── Chart 2: Bill status breakdown ────────────────────────────────────
        $billStatusData = $pdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM billing
            WHERE deleted_at IS NULL
            GROUP BY status
        ")->fetchAll();

        // ── Chart 3: New customers – last 6 months ────────────────────────────
        $customerGrowth = $pdo->query("
            SELECT DATE_FORMAT(date_connected,'%b %Y') AS label,
                   COUNT(*) AS cnt
            FROM customers
            WHERE date_connected >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              AND deleted_at IS NULL
            GROUP BY DATE_FORMAT(date_connected,'%Y-%m')
            ORDER BY MIN(date_connected) ASC
            LIMIT 6
        ")->fetchAll();

        // ── Recent activity feeds ─────────────────────────────────────────────
        $recentPayments = $pdo->query("
            SELECT p.*, ca.account_number,
                   CONCAT(c.first_name,' ',c.last_name) AS customer_name
            FROM payments p
            JOIN customer_accounts ca ON p.account_id = ca.id
            JOIN customers c ON ca.customer_id = c.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.id DESC LIMIT 6
        ")->fetchAll();

        $recentLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 8")->fetchAll();

        $pageTitle = "Executive Dashboard";
        require_once __DIR__ . '/../../views/dashboard/index.php';
    }
}

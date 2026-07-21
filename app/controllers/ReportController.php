<?php
// app/controllers/ReportController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class ReportController {
    public function index(): void {
        $pdo = Database::getConnection();

        $type = trim($_GET['type'] ?? 'daily');

        // Daily collections (last 30 days)
        $dailyCollections = $pdo->query("
            SELECT DATE(payment_date) AS pay_date,
                   COUNT(*) AS tx_count,
                   SUM(amount_paid) AS total_amount
            FROM payments
            WHERE deleted_at IS NULL
            GROUP BY DATE(payment_date)
            ORDER BY pay_date DESC
            LIMIT 30
        ")->fetchAll();

        // Monthly collections (last 12 months)
        $monthlyCollections = $pdo->query("
            SELECT DATE_FORMAT(payment_date,'%Y-%m') AS pay_month,
                   DATE_FORMAT(MIN(payment_date),'%M %Y') AS label,
                   COUNT(*) AS tx_count,
                   SUM(amount_paid) AS total_amount
            FROM payments
            WHERE deleted_at IS NULL
            GROUP BY pay_month
            ORDER BY pay_month DESC
            LIMIT 12
        ")->fetchAll();

        // Outstanding accounts (top 30 by balance)
        $outstanding = $pdo->query("
            SELECT ca.account_number, ca.current_balance, ca.total_amount_due,
                   CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                   c.account_type, bg.name AS barangay_name,
                   (SELECT COUNT(*) FROM billing WHERE account_id=ca.id
                    AND status IN ('Unpaid','Overdue') AND deleted_at IS NULL) AS unpaid_count
            FROM customer_accounts ca
            JOIN customers c  ON ca.customer_id = c.id
            JOIN barangays bg ON c.barangay_id  = bg.id
            WHERE ca.current_balance > 0 AND ca.deleted_at IS NULL
            ORDER BY ca.current_balance DESC
            LIMIT 30
        ")->fetchAll();

        // Handle CSV export
        if (isset($_GET['export'])) {
            $this->exportCSV($_GET['export'], $dailyCollections, $monthlyCollections, $outstanding);
        }

        $pageTitle = 'Reports & Financial Analytics';
        require_once __DIR__ . '/../../views/reports/index.php';
    }

    private function exportCSV(string $exportType, array $daily, array $monthly, array $outstanding): void {
        $filename = match ($exportType) {
            'daily'       => 'Daily_Collections_' . date('Ymd') . '.csv',
            'monthly'     => 'Monthly_Collections_' . date('Ymd') . '.csv',
            'outstanding' => 'Outstanding_Accounts_' . date('Ymd') . '.csv',
            default       => 'Report.csv'
        };

        $rows = match ($exportType) {
            'daily'       => [['Date', 'Transactions', 'Total Amount'], ...array_map(fn($r) => [$r['pay_date'], $r['tx_count'], $r['total_amount']], $daily)],
            'monthly'     => [['Month', 'Transactions', 'Total Amount'], ...array_map(fn($r) => [$r['label'], $r['tx_count'], $r['total_amount']], $monthly)],
            'outstanding' => [['Account No', 'Customer Name', 'Barangay', 'Type', 'Unpaid Bills', 'Balance'], ...array_map(fn($r) => [$r['account_number'], $r['customer_name'], $r['barangay_name'], $r['account_type'], $r['unpaid_count'], $r['current_balance']], $outstanding)],
            default       => []
        };

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

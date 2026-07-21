<?php
// app/controllers/DelinquentController.php

require_once __DIR__ . '/../../config/database.php';

class DelinquentController {
    public function index(): void {
        $pdo = Database::getConnection();
        $barangay = trim($_GET['barangay'] ?? '');

        $sql = "SELECT ca.*, c.customer_code, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.contact_number, c.account_type, bg.name as barangay_name,
                       (SELECT COUNT(*) FROM billing WHERE account_id = ca.id AND status IN ('Unpaid', 'Overdue') AND deleted_at IS NULL) as unpaid_months
                FROM customer_accounts ca
                JOIN customers c ON ca.customer_id = c.id
                JOIN barangays bg ON c.barangay_id = bg.id
                WHERE ca.current_balance > 0 AND ca.deleted_at IS NULL";
        
        $params = [];
        if (!empty($barangay)) {
            $sql .= " AND bg.name = ?";
            $params[] = $barangay;
        }

        $sql .= " ORDER BY ca.current_balance DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $delinquents = $stmt->fetchAll();

        $barangays = $pdo->query("SELECT * FROM barangays ORDER BY name ASC")->fetchAll();

        $pageTitle = "Delinquent Accounts Monitoring";
        require_once __DIR__ . '/../../views/delinquents/index.php';
    }

    public function notice(): void {
        $id  = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT ca.*, ca.id as account_id,
                                      CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                                      c.customer_code, c.address, c.contact_number, c.account_type,
                                      m.meter_number, bg.name AS barangay_name
                               FROM customer_accounts ca
                               JOIN customers c  ON ca.customer_id = c.id
                               JOIN barangays bg ON c.barangay_id  = bg.id
                               LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                               WHERE ca.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $account = $stmt->fetch();

        if (!$account) {
            redirect('/delinquents');
        }

        $bills = $pdo->prepare("SELECT * FROM billing
                                WHERE account_id = ?
                                  AND status IN ('Unpaid','Overdue','Partially Paid')
                                  AND deleted_at IS NULL
                                ORDER BY due_date ASC");
        $bills->execute([$id]);
        $unpaidBills = $bills->fetchAll();

        $pageTitle = "Disconnection Notice - " . $account['account_number'];
        require_once __DIR__ . '/../../views/delinquents/notice.php';
    }
}

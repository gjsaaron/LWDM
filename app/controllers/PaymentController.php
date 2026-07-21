<?php
// app/controllers/PaymentController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class PaymentController {
    public function index(): void {
        $pdo = Database::getConnection();
        $search = trim($_GET['search'] ?? '');
        $selectedAccount = null;
        $unpaidBills = [];

        if (!empty($search)) {
            $stmt = $pdo->prepare("SELECT ca.*, c.customer_code, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.address, m.meter_number, bg.name as barangay_name
                                   FROM customer_accounts ca
                                   JOIN customers c ON ca.customer_id = c.id
                                   JOIN barangays bg ON c.barangay_id = bg.id
                                   LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                                   WHERE ca.account_number = ? OR c.customer_code = ? OR m.meter_number = ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? LIMIT 1");
            $stmt->execute([$search, $search, $search, "%$search%"]);
            $selectedAccount = $stmt->fetch();

            if ($selectedAccount) {
                $bStmt = $pdo->prepare("SELECT * FROM billing WHERE account_id = ? AND status IN ('Unpaid', 'Partially Paid', 'Overdue') AND deleted_at IS NULL ORDER BY due_date ASC");
                $bStmt->execute([$selectedAccount['id']]);
                $unpaidBills = $bStmt->fetchAll();
            }
        }

        // Recent payment transactions
        $recentPayments = $pdo->query("SELECT p.*, ca.account_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, e.full_name as cashier_name
                                       FROM payments p
                                       JOIN customer_accounts ca ON p.account_id = ca.id
                                       JOIN customers c ON ca.customer_id = c.id
                                       JOIN employees e ON p.cashier_id = e.id
                                       WHERE p.deleted_at IS NULL ORDER BY p.id DESC LIMIT 10")->fetchAll();

        $pageTitle = "Cashier Payment Terminal";
        require_once __DIR__ . '/../../views/payments/index.php';
    }

    public function processPayment(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/payments');
        }

        $acctId = (int)($_POST['account_id'] ?? 0);
        $amountPaid = (float)($_POST['amount_paid'] ?? 0.00);
        $method = trim($_POST['payment_method'] ?? 'Cash');
        $refNo = trim($_POST['reference_number'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($acctId) || $amountPaid <= 0) {
            $_SESSION['flash_error'] = 'Please select a customer account and enter a valid payment amount.';
            redirect('/payments');
        }

        $pdo = Database::getConnection();
        $cashier = AuthMiddleware::currentUser();

        try {
            $pdo->beginTransaction();

            $orNumber = 'OR-' . date('Ym') . '-' . rand(10000, 99999);

            $pStmt = $pdo->prepare("INSERT INTO payments (or_number, account_id, cashier_id, payment_date, amount_paid, payment_method, reference_number, remarks) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
            $pStmt->execute([$orNumber, $acctId, $cashier['id'], $amountPaid, $method, $refNo, $remarks]);
            $paymentId = $pdo->lastInsertId();

            // FIFO Bill Settlement
            $unpaidBills = $pdo->prepare("SELECT * FROM billing WHERE account_id = ? AND status IN ('Unpaid', 'Partially Paid', 'Overdue') AND deleted_at IS NULL ORDER BY due_date ASC");
            $unpaidBills->execute([$acctId]);
            $bills = $unpaidBills->fetchAll();

            $remainingPayment = $amountPaid;
            $pdStmt = $pdo->prepare("INSERT INTO payment_details (payment_id, billing_id, amount_applied) VALUES (?, ?, ?)");

            foreach ($bills as $bill) {
                if ($remainingPayment <= 0) break;

                $billBalance = $bill['total_amount'] - $bill['amount_paid'];
                $applied = min($remainingPayment, $billBalance);

                $newPaid = $bill['amount_paid'] + $applied;
                $newStatus = ($newPaid >= $bill['total_amount']) ? 'Paid' : 'Partially Paid';

                $pdo->prepare("UPDATE billing SET amount_paid = ?, status = ? WHERE id = ?")
                    ->execute([$newPaid, $newStatus, $bill['id']]);

                $pdStmt->execute([$paymentId, $bill['id'], $applied]);
                $remainingPayment -= $applied;
            }

            // Update Account current balance (parameterized - no interpolation)
            $balStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount - amount_paid), 0.00)
                                      FROM billing
                                      WHERE account_id = ?
                                        AND status IN ('Unpaid','Partially Paid','Overdue')
                                        AND deleted_at IS NULL");
            $balStmt->execute([$acctId]);
            $newAcctBal = (float)$balStmt->fetchColumn();
            $pdo->prepare("UPDATE customer_accounts SET current_balance = ?, total_amount_due = ?, last_payment_date = CURDATE() WHERE id = ?")
                ->execute([$newAcctBal, $newAcctBal, $acctId]);

            // Audit log
            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
                ->execute([$cashier['id'], $cashier['name'], 'Accept Payment', "payments#$paymentId ($orNumber)", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $pdo->commit();
            $_SESSION['flash_success'] = "Payment recorded successfully! Official Receipt: $orNumber";
            redirect('/payments?action=receipt&id=' . $paymentId);

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Payment processing failed: " . $e->getMessage();
            redirect('/payments');
        }
    }

    public function viewReceipt(): void {
        $id = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT p.*, ca.account_number, c.customer_code, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.address, e.full_name as cashier_name, bg.name as barangay_name
                               FROM payments p
                               JOIN customer_accounts ca ON p.account_id = ca.id
                               JOIN customers c ON ca.customer_id = c.id
                               JOIN barangays bg ON c.barangay_id = bg.id
                               JOIN employees e ON p.cashier_id = e.id
                               WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $payment = $stmt->fetch();

        if (!$payment) {
            redirect('/payments');
        }

        // Applied bills details
        $details = $pdo->prepare("SELECT pd.*, b.bill_number, b.billing_period FROM payment_details pd JOIN billing b ON pd.billing_id = b.id WHERE pd.payment_id = ?");
        $details->execute([$id]);
        $appliedBills = $details->fetchAll();

        $pageTitle = "Official Receipt - " . $payment['or_number'];
        require_once __DIR__ . '/../../views/payments/receipt.php';
    }
}

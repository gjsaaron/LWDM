<?php
// app/controllers/CustomerController.php

require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class CustomerController {
    public function index(): void {
        $search = trim($_GET['search'] ?? '');
        $barangay = trim($_GET['barangay'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $customers = Customer::getAll($search, $barangay, $type, $limit, $offset);
        $totalCount = Customer::getCount($search, $barangay, $type);
        $totalPages = max(1, ceil($totalCount / $limit));

        $pdo = Database::getConnection();
        $barangays = $pdo->query("SELECT * FROM barangays ORDER BY name ASC")->fetchAll();

        $pageTitle = "Customer Management";
        require_once __DIR__ . '/../../views/customers/index.php';
    }

    public function profile(): void {
        $id = (int)($_GET['id'] ?? 0);
        $customer = Customer::getById($id);

        if (!$customer) {
            redirect('/customers');
        }

        $pdo = Database::getConnection();

        // Billing History
        $bills = $pdo->prepare("SELECT b.*, mr.reading_date FROM billing b JOIN meter_readings mr ON b.meter_reading_id = mr.id WHERE b.account_id = ? ORDER BY b.id DESC");
        $bills->execute([$customer['account_id']]);
        $billList = $bills->fetchAll();

        // Payment History — LEFT JOIN employees so deleted cashiers don't break history
        $payments = $pdo->prepare("SELECT p.*, COALESCE(e.full_name, 'Unknown Cashier') as cashier_name FROM payments p LEFT JOIN employees e ON p.cashier_id = e.id WHERE p.account_id = ? AND p.deleted_at IS NULL ORDER BY p.id DESC");
        $payments->execute([$customer['account_id']]);
        $paymentList = $payments->fetchAll();

        // Meter Readings
        $readings = $pdo->prepare("SELECT mr.*, m.meter_number FROM meter_readings mr JOIN meters m ON mr.meter_id = m.id WHERE mr.account_id = ? ORDER BY mr.id DESC");
        $readings->execute([$customer['account_id']]);
        $readingList = $readings->fetchAll();

        // Documents
        $docs = $pdo->prepare("SELECT * FROM documents WHERE customer_id = ? ORDER BY uploaded_at DESC");
        $docs->execute([$customer['id']]);
        $documentList = $docs->fetchAll();

        $activeTab = $_GET['tab'] ?? 'overview';
        $pageTitle = "Customer Profile - " . $customer['first_name'] . ' ' . $customer['last_name'];
        require_once __DIR__ . '/../../views/customers/profile.php';

    }

    public function create(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/customers');
        }

        $fn = trim($_POST['first_name'] ?? '');
        $mn = trim($_POST['middle_name'] ?? '');
        $ln = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bId = (int)($_POST['barangay_id'] ?? 0);
        $type = trim($_POST['account_type'] ?? 'Residential');
        $meterNo = trim($_POST['meter_number'] ?? '');

        if (empty($fn) || empty($ln) || empty($address) || empty($bId) || empty($meterNo)) {
            $_SESSION['flash_error'] = 'Please fill out all required fields.';
            redirect('/customers');
        }

        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            $nextId = $pdo->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'customers'")->fetchColumn() ?: rand(100, 9999);
            $custCode = 'CUST-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
            $acctNo = 'ACCT-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO customers (customer_code, first_name, middle_name, last_name, contact_number, email, address, barangay_id, date_connected, account_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'Active')");
            $stmt->execute([$custCode, $fn, $mn, $ln, $phone, $email, $address, $bId, $type]);
            $custId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO customer_accounts (account_number, customer_id, current_balance, status) VALUES (?, ?, 0.00, 'Active')")->execute([$acctNo, $custId]);
            $acctId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO meters (meter_number, brand_model, installation_date, account_id, status) VALUES (?, 'Standard 20mm', CURDATE(), ?, 'Active')")->execute([$meterNo, $acctId]);

            // Audit Log
            $user = AuthMiddleware::currentUser();
            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user['id'], $user['name'], 'Register Customer', "customers#$custId", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $pdo->commit();
            $_SESSION['flash_success'] = "Customer registered successfully: $custCode";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Failed to register customer: " . $e->getMessage();
        }

        redirect('/customers');
    }

    public function toggleStatus(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            redirect('/customers');
        }

        $id  = (int)($_POST['id'] ?? 0);
        $pdo = Database::getConnection();
        $row = $pdo->prepare("SELECT id, status, first_name, last_name FROM customers WHERE id = ? AND deleted_at IS NULL");
        $row->execute([$id]);
        $customer = $row->fetch();

        if (!$customer) {
            $_SESSION['flash_error'] = 'Customer not found.';
            redirect('/customers');
        }

        $newStatus = $customer['status'] === 'Active' ? 'Disconnected' : 'Active';
        $pdo->prepare("UPDATE customers SET status=? WHERE id=?")->execute([$newStatus, $id]);
        $pdo->prepare("UPDATE customer_accounts SET status=? WHERE customer_id=?")->execute([$newStatus, $id]);

        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], "Set Customer {$newStatus}", "customers#{$id}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = "{$customer['first_name']} {$customer['last_name']} set to {$newStatus}.";
        redirect('/customers');
    }
}

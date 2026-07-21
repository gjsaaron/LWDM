<?php
// app/controllers/MeterController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class MeterController {
    public function index(): void {
        $pdo = Database::getConnection();
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT mr.*, m.meter_number, ca.account_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, c.id as customer_id
                FROM meter_readings mr
                JOIN meters m ON mr.meter_id = m.id
                JOIN customer_accounts ca ON mr.account_id = ca.id
                JOIN customers c ON ca.customer_id = c.id
                WHERE 1=1";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (ca.account_number LIKE ? OR m.meter_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
            $term = "%$search%";
            $params = [$term, $term, $term];
        }

        $countSql = "SELECT COUNT(*) FROM ($sql) as t";
        $cStmt = $pdo->prepare($countSql);
        $cStmt->execute($params);
        $totalCount = (int)$cStmt->fetchColumn();
        $totalPages = max(1, ceil($totalCount / $limit));

        $sql .= " ORDER BY mr.id DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $readings = $stmt->fetchAll();

        $pageTitle = "Meter Readings Management";
        require_once __DIR__ . '/../../views/meters/index.php';
    }

    public function store(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/meters');
        }

        $acctId = (int)($_POST['account_id'] ?? 0);
        $meterId = (int)($_POST['meter_id'] ?? 0);
        $readDate = trim($_POST['reading_date'] ?? date('Y-m-d'));
        $currVal = (int)($_POST['current_reading'] ?? 0);
        $reader = trim($_POST['reader_name'] ?? '');

        if (empty($acctId) || empty($meterId) || empty($reader)) {
            $_SESSION['flash_error'] = 'Please fill out all required fields.';
            redirect('/meters');
        }

        $pdo = Database::getConnection();

        // Fetch last reading value (parameterized)
        $prevStmt = $pdo->prepare("SELECT current_reading FROM meter_readings WHERE account_id = ? ORDER BY id DESC LIMIT 1");
        $prevStmt->execute([$acctId]);
        $prevVal = (int)($prevStmt->fetchColumn() ?: 0);

        if ($currVal < $prevVal) {
            $_SESSION['flash_error'] = "Current reading ($currVal) cannot be less than previous reading ($prevVal). Anomaly detected.";
            redirect('/meters');
        }

        $consumption = $currVal - $prevVal;
        $isAnomaly = ($consumption > 35) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO meter_readings (account_id, meter_id, reading_date, previous_reading, current_reading, reader_name, is_anomaly, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$acctId, $meterId, $readDate, $prevVal, $currVal, $reader, $isAnomaly, $isAnomaly ? 'Encoded high consumption warning' : null]);
        $readingId = $pdo->lastInsertId();

        // Audit log
        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user['id'], $user['name'], 'Encode Meter Reading', "meter_readings#$readingId", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

        $_SESSION['flash_success'] = "Meter reading recorded successfully. Consumption: {$consumption} m³";
        redirect('/meters');
    }
}

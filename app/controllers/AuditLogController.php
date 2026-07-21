<?php
// app/controllers/AuditLogController.php

require_once __DIR__ . '/../../config/database.php';

class AuditLogController {
    public function index(): void {
        $pdo     = Database::getConnection();
        $search  = trim($_GET['search'] ?? '');
        $action  = trim($_GET['action_filter'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = 25;
        $offset  = ($page - 1) * $limit;

        $sql    = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql    .= " AND (employee_name LIKE ? OR affected_record LIKE ? OR ip_address LIKE ?)";
            $t       = "%$search%";
            $params  = array_merge($params, [$t, $t, $t]);
        }
        if (!empty($action)) {
            $sql    .= " AND action LIKE ?";
            $params[] = "%$action%";
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE 1=1"
            . (!empty($search) ? " AND (employee_name LIKE ? OR affected_record LIKE ? OR ip_address LIKE ?)" : "")
            . (!empty($action)  ? " AND action LIKE ?" : ""));
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalCount / $limit));

        $sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Distinct action types for filter dropdown
        $actionTypes = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = "System Audit Logs";
        require_once __DIR__ . '/../../views/audit_logs/index.php';
    }
}

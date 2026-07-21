<?php
// app/controllers/AjaxController.php
// Handles all AJAX/API requests from the frontend

require_once __DIR__ . '/../../config/database.php';

class AjaxController {
    public function handle(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkLoggedIn();

        $action = trim($_GET['action'] ?? '');

        match ($action) {
            'search-customers'   => $this->searchCustomers(),
            'account-bills'      => $this->getAccountBills(),
            'billing-job-status' => $this->getBillingJobStatus(),
            'search-meters'      => $this->searchMeters(),
            default              => $this->notFound()
        };
    }

    /** Live account search for POS & billing lookup */
    private function searchCustomers(): void {
        $q   = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['results' => []]);
            return;
        }

        $pdo  = Database::getConnection();
        $term = "%$q%";
        $stmt = $pdo->prepare("SELECT ca.id, ca.account_number, ca.current_balance,
                                      CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                                      c.customer_code, c.status,
                                      m.meter_number
                               FROM customer_accounts ca
                               JOIN customers c ON ca.customer_id = c.id
                               LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                               WHERE ca.deleted_at IS NULL
                                 AND (ca.account_number LIKE ? OR c.customer_code LIKE ?
                                      OR CONCAT(c.first_name,' ',c.last_name) LIKE ?
                                      OR m.meter_number LIKE ?)
                               LIMIT 8");
        $stmt->execute([$term, $term, $term, $term]);
        echo json_encode(['results' => $stmt->fetchAll()]);
    }

    /** Get unpaid bills for a given account_id (used by POS) */
    private function getAccountBills(): void {
        $acctId = (int)($_GET['account_id'] ?? 0);
        if (!$acctId) {
            echo json_encode(['bills' => [], 'account' => null]);
            return;
        }

        $pdo   = Database::getConnection();
        $aStmt = $pdo->prepare("SELECT ca.*, ca.id as account_id,
                                       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                                       c.customer_code, c.address, c.account_type,
                                       m.meter_number, bg.name AS barangay_name
                                FROM customer_accounts ca
                                JOIN customers c  ON ca.customer_id = c.id
                                JOIN barangays bg ON c.barangay_id  = bg.id
                                LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                                WHERE ca.id = ?");
        $aStmt->execute([$acctId]);
        $account = $aStmt->fetch();

        $bStmt = $pdo->prepare("SELECT * FROM billing
                                WHERE account_id = ?
                                  AND status IN ('Unpaid','Partially Paid','Overdue')
                                  AND deleted_at IS NULL
                                ORDER BY due_date ASC");
        $bStmt->execute([$acctId]);
        $bills = $bStmt->fetchAll();

        echo json_encode(['account' => $account, 'bills' => $bills]);
    }

    /** Poll billing job progress */
    private function getBillingJobStatus(): void {
        $id  = (int)($_GET['job_id'] ?? 0);
        $pdo = Database::getConnection();
        $row = $pdo->prepare("SELECT * FROM billing_jobs WHERE id = ?");
        $row->execute([$id]);
        echo json_encode($row->fetch() ?: ['status' => 'Not Found']);
    }

    private function notFound(): void {
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action']);
    }

    /** Live account+meter search for meter reading entry modal */
    private function searchMeters(): void {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['results' => []]);
            return;
        }

        $pdo  = Database::getConnection();
        $term = "%$q%";
        $stmt = $pdo->prepare("SELECT ca.id AS account_id,
                                      ca.account_number,
                                      CONCAT(c.first_name,' ',c.last_name) AS customer_name,
                                      m.id   AS meter_id,
                                      m.meter_number,
                                      COALESCE(
                                          (SELECT current_reading FROM meter_readings
                                           WHERE account_id = ca.id ORDER BY id DESC LIMIT 1),
                                          0
                                      ) AS last_reading
                               FROM customer_accounts ca
                               JOIN customers c ON ca.customer_id = c.id
                               JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                               WHERE ca.status = 'Active' AND ca.deleted_at IS NULL
                                 AND (ca.account_number LIKE ? OR m.meter_number LIKE ?
                                      OR CONCAT(c.first_name,' ',c.last_name) LIKE ?)
                               LIMIT 8");
        $stmt->execute([$term, $term, $term]);
        echo json_encode(['results' => $stmt->fetchAll()]);
    }
}

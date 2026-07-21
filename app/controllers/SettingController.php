<?php
// app/controllers/SettingController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class SettingController {
    public function index(): void {
        $pdo = Database::getConnection();
        $waterRates = $pdo->query("SELECT * FROM water_rates ORDER BY id ASC")->fetchAll();
        $settings   = $pdo->query("SELECT setting_key, setting_value FROM system_settings")
                          ->fetchAll(PDO::FETCH_KEY_PAIR);

        $pageTitle = 'System Settings & Water Rates';
        require_once __DIR__ . '/../../views/settings/index.php';
    }

    public function update(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/settings');
        }

        $pdo  = Database::getConnection();
        $user = AuthMiddleware::currentUser();

        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

            $keys = ['company_name','company_address','contact_number','billing_due_days','penalty_percentage'];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    $stmt->execute([$k, trim($_POST[$k])]);
                }
            }

            // Update water rates if submitted
            if (!empty($_POST['rates'])) {
                $rStmt = $pdo->prepare("UPDATE water_rates SET min_rate=?, rate_per_m3=?, penalty_rate=? WHERE id=?");
                foreach ($_POST['rates'] as $rateId => $values) {
                    $rStmt->execute([
                        (float)$values['min_rate'],
                        (float)$values['rate_per_m3'],
                        (float)$values['penalty_rate'],
                        (int)$rateId
                    ]);
                }
            }

            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
                ->execute([$user['id'], $user['name'], 'Update System Settings', 'system_settings', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $_SESSION['flash_success'] = 'System settings updated successfully.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Failed to update settings: ' . $e->getMessage();
        }

        redirect('/settings');
    }

    public function backup(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/settings');
        }

        $pdo      = Database::getConnection();
        $user     = AuthMiddleware::currentUser();
        $filename = 'lmwd_backup_' . date('Ymd_His') . '.sql';

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dump   = "-- La Mesa Water District Database Backup\n";
        $dump  .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $dump  .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Table structure
            $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $dump .= "\n-- Table: $table\n";
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $dump .= $createRow[1] . ";\n\n";

            // Table data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as $row) {
                $escaped = array_map(fn($v) => $v === null ? 'NULL' : "'" . addslashes($v) . "'", $row);
                $dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $escaped) . ");\n";
            }
            $dump .= "\n";
        }

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Database Backup', $filename, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        echo $dump;
        exit;
    }

    public function restore(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/settings');
        }

        $user = AuthMiddleware::currentUser();

        if (empty($_FILES['sql_file']['tmp_name']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'No file uploaded or upload error occurred.';
            redirect('/settings');
        }

        $file = $_FILES['sql_file'];

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $_SESSION['flash_error'] = 'Only .sql backup files are accepted.';
            redirect('/settings');
        }

        // Validate size (max 50 MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            $_SESSION['flash_error'] = 'File too large. Maximum size is 50 MB.';
            redirect('/settings');
        }

        $sqlContent = file_get_contents($file['tmp_name']);

        // Safety: must contain a recognisable backup header
        if (!str_contains($sqlContent, 'SET FOREIGN_KEY_CHECKS')) {
            $_SESSION['flash_error'] = 'File does not appear to be a valid LMWD backup (.sql).';
            redirect('/settings');
        }

        try {
            $pdo = Database::getConnection();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

            // Split on semicolons and execute each statement
            $statements = array_filter(
                array_map('trim', explode(";\n", $sqlContent)),
                fn($s) => !empty($s) && !str_starts_with($s, '--')
            );

            $pdo->beginTransaction();
            foreach ($statements as $sql) {
                if (!empty(trim($sql))) {
                    $pdo->exec($sql);
                }
            }
            $pdo->commit();

            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
                ->execute([$user['id'], $user['name'], 'Database Restore', $file['name'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            $_SESSION['flash_success'] = "Database restored successfully from: {$file['name']}.";
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = 'Restore failed: ' . $e->getMessage();
        }

        redirect('/settings');
    }
}

<?php
// app/controllers/ProfileController.php
// Lets the currently logged-in employee view and change their own password.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class ProfileController {
    public function index(): void {
        $user = AuthMiddleware::currentUser();
        $pdo  = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$user['id']]);
        $employee = $stmt->fetch();

        // Last 10 audit entries by this user
        $logs = $pdo->prepare("SELECT * FROM audit_logs WHERE employee_id = ? ORDER BY id DESC LIMIT 10");
        $logs->execute([$user['id']]);
        $activityLogs = $logs->fetchAll();

        $pageTitle = "My Profile";
        require_once __DIR__ . '/../../views/profile/index.php';
    }

    public function changePassword(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            redirect('/profile');
        }

        $user    = AuthMiddleware::currentUser();
        $pdo     = Database::getConnection();
        $current = trim($_POST['current_password'] ?? '');
        $new     = trim($_POST['new_password']     ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        // Fetch hash
        $row = $pdo->prepare("SELECT password_hash FROM employees WHERE id = ?");
        $row->execute([$user['id']]);
        $emp = $row->fetch();

        if (!password_verify($current, $emp['password_hash'])) {
            $_SESSION['flash_error'] = 'Current password is incorrect.';
            redirect('/profile');
        }
        if ($new !== $confirm) {
            $_SESSION['flash_error'] = 'New passwords do not match.';
            redirect('/profile');
        }
        if (strlen($new) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
            redirect('/profile');
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE employees SET password_hash=? WHERE id=?")->execute([$hash, $user['id']]);
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Change Own Password', "employees#{$user['id']}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = 'Password changed successfully.';
        redirect('/profile');
    }
}

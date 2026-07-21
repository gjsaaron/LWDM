<?php
// app/controllers/EmployeeController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class EmployeeController {
    public function index(): void {
        $pdo = Database::getConnection();
        $employees = $pdo->query("SELECT * FROM employees WHERE deleted_at IS NULL ORDER BY id DESC")->fetchAll();
        $pageTitle = "Employee Management";
        require_once __DIR__ . '/../../views/employees/index.php';
    }

    public function store(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            redirect('/employees');
        }

        $name     = trim($_POST['full_name']  ?? '');
        $username = trim($_POST['username']   ?? '');
        $password = trim($_POST['password']   ?? '');
        $role     = trim($_POST['role']       ?? 'Billing Staff');
        $email    = trim($_POST['email']      ?? '');
        $phone    = trim($_POST['phone']      ?? '');

        if (empty($name) || empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'Please fill out all required fields.';
            redirect('/employees');
        }

        if (strlen($password) < 8) {
            $_SESSION['flash_error'] = 'Password must be at least 8 characters.';
            redirect('/employees');
        }

        $pdo    = Database::getConnection();
        $empNo  = 'EMP-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $hash   = password_hash($password, PASSWORD_DEFAULT);
        $user   = AuthMiddleware::currentUser();

        try {
            $pdo->prepare("INSERT INTO employees
                (employee_number, full_name, username, password_hash, role, email, phone, status)
                VALUES (?,?,?,?,?,?,?,'Active')")
                ->execute([$empNo, $name, $username, $hash, $role, $email, $phone]);

            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
                ->execute([$user['id'], $user['name'], 'Create Employee', "employees#$empNo", $_SERVER['REMOTE_ADDR'] ?? '']);

            $_SESSION['flash_success'] = "Employee account created: {$username} ({$empNo}).";
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Failed: username may already be taken.';
        }

        redirect('/employees');
    }

    public function toggleStatus(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            redirect('/employees');
        }

        $id  = (int)($_POST['id'] ?? 0);
        $pdo = Database::getConnection();
        $emp = $pdo->prepare("SELECT id, status, username FROM employees WHERE id = ? AND deleted_at IS NULL");
        $emp->execute([$id]);
        $row = $emp->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = 'Employee not found.';
            redirect('/employees');
        }

        $newStatus = $row['status'] === 'Active' ? 'Inactive' : 'Active';
        $pdo->prepare("UPDATE employees SET status=? WHERE id=?")->execute([$newStatus, $id]);

        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], "Set Employee {$newStatus}", "employees#{$id}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = "Employee '{$row['username']}' is now {$newStatus}.";
        redirect('/employees');
    }

    public function resetPassword(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            redirect('/employees');
        }

        $id       = (int)($_POST['id'] ?? 0);
        $newPass  = trim($_POST['new_password'] ?? '');

        if (strlen($newPass) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
            redirect('/employees');
        }

        $pdo  = Database::getConnection();
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE employees SET password_hash=? WHERE id=? AND deleted_at IS NULL")->execute([$hash, $id]);

        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Reset Employee Password', "employees#{$id}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = 'Password reset successfully.';
        redirect('/employees');
    }

    public function delete(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            redirect('/employees');
        }

        $id  = (int)($_POST['id'] ?? 0);
        $pdo = Database::getConnection();

        // Prevent self-delete
        $user = AuthMiddleware::currentUser();
        if ($id === (int)$user['id']) {
            $_SESSION['flash_error'] = 'You cannot delete your own account.';
            redirect('/employees');
        }

        $pdo->prepare("UPDATE employees SET deleted_at=NOW() WHERE id=?")->execute([$id]);
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Delete Employee', "employees#{$id}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = 'Employee account removed.';
        redirect('/employees');
    }
}

<?php
// app/controllers/AuthController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class AuthController {
    public function showLogin(): void {
        if (isset($_SESSION['user_id'])) {
            redirect('/dashboard');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['login_error'] = 'Invalid request token. Please try again.';
            redirect('/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            redirect('/login');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ? AND status = 'Active' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $pdo->prepare("UPDATE employees SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // Audit log
            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
                ->execute([$user['id'], $user['full_name'], 'User Login', 'employees#' . $user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            redirect('/dashboard');
        } else {
            $_SESSION['login_error'] = 'Invalid username or password.';
            redirect('/login');
        }
    }

    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            $pdo = Database::getConnection();
            $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?, ?, ?, ?, ?)")
                ->execute([$_SESSION['user_id'], $_SESSION['user_name'] ?? 'User', 'User Logout', 'employees#' . $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
        }

        session_unset();
        session_destroy();
        redirect('/login');
    }
}

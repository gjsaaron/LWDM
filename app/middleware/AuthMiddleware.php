<?php
// app/middleware/AuthMiddleware.php

class AuthMiddleware {
    public static function checkLoggedIn(): void {
        if (!isset($_SESSION['user_id'])) {
            redirect('/login');
        }
    }

    public static function checkRole(array $allowedRoles): void {
        self::checkLoggedIn();
        $userRole = $_SESSION['user_role'] ?? '';
        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            require_once __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }

    public static function currentUser(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'Guest'
        ];
    }
}

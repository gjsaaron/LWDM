<?php
// config/app.php

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

define('APP_NAME', 'La Mesa Water District');
define('APP_TAGLINE', 'Water District Management System');
define('BASE_URL', '/LMWD');

// Timezone
date_default_timezone_set('Asia/Manila');

// Global helper functions
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

function format_money(float|int $amount): string {
    return '₱' . number_format((float)$amount, 2);
}

function format_date(?string $dateStr, string $format = 'M d, Y'): string {
    if (!$dateStr) return 'N/A';
    return date($format, strtotime($dateStr));
}

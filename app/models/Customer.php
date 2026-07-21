<?php
// app/models/Customer.php

require_once __DIR__ . '/../../config/database.php';

class Customer {
    public static function getAll(string $search = '', string $barangay = '', string $type = '', int $limit = 20, int $offset = 0): array {
        $pdo = Database::getConnection();
        $sql = "SELECT c.*, b.name as barangay_name, ca.account_number, ca.current_balance, ca.total_amount_due, m.meter_number
                FROM customers c
                JOIN barangays b ON c.barangay_id = b.id
                JOIN customer_accounts ca ON ca.customer_id = c.id
                LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                WHERE c.deleted_at IS NULL";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (c.customer_code LIKE ? OR ca.account_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR m.meter_number LIKE ? OR c.contact_number LIKE ?)";
            $term = "%$search%";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($barangay)) {
            $sql .= " AND b.name = ?";
            $params[] = $barangay;
        }

        if (!empty($type)) {
            $sql .= " AND c.account_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY c.id DESC LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getCount(string $search = '', string $barangay = '', string $type = ''): int {
        $pdo = Database::getConnection();
        $sql = "SELECT COUNT(*)
                FROM customers c
                JOIN barangays b ON c.barangay_id = b.id
                JOIN customer_accounts ca ON ca.customer_id = c.id
                LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                WHERE c.deleted_at IS NULL";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (c.customer_code LIKE ? OR ca.account_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR m.meter_number LIKE ? OR c.contact_number LIKE ?)";
            $term = "%$search%";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($barangay)) {
            $sql .= " AND b.name = ?";
            $params[] = $barangay;
        }

        if (!empty($type)) {
            $sql .= " AND c.account_type = ?";
            $params[] = $type;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT c.*, b.name as barangay_name, ca.id as account_id, ca.account_number, ca.current_balance, ca.previous_balance, ca.total_amount_due, m.id as meter_id, m.meter_number
                               FROM customers c
                               JOIN barangays b ON c.barangay_id = b.id
                               JOIN customer_accounts ca ON ca.customer_id = c.id
                               LEFT JOIN meters m ON m.account_id = ca.id AND m.status = 'Active'
                               WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    }
}

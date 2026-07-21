<?php
// app/controllers/DocumentController.php
// Handles customer document uploads and deletion.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';

class DocumentController {
    private const UPLOAD_DIR  = __DIR__ . '/../../storage/documents/';
    private const MAX_SIZE    = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

    public function upload(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid request token.';
            $this->redirectBack();
        }

        $customerId  = (int)($_POST['customer_id'] ?? 0);
        $docType     = trim($_POST['document_type'] ?? '');
        $allowedTypes = ['Valid ID', 'Proof of Address', 'Application Form', 'Other'];

        if (!$customerId || !in_array($docType, $allowedTypes, true)) {
            $_SESSION['flash_error'] = 'Invalid customer or document type.';
            $this->redirectBack($customerId);
        }

        if (empty($_FILES['document']['tmp_name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'No file uploaded or upload error.';
            $this->redirectBack($customerId);
        }

        $file = $_FILES['document'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            $_SESSION['flash_error'] = 'Only PDF, JPG, and PNG files are accepted.';
            $this->redirectBack($customerId);
        }
        if ($file['size'] > self::MAX_SIZE) {
            $_SESSION['flash_error'] = 'File too large. Maximum size is 5 MB.';
            $this->redirectBack($customerId);
        }

        // Ensure upload directory exists
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Store with a unique name to prevent collisions and path traversal
        $safeName = 'doc_' . $customerId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = self::UPLOAD_DIR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $_SESSION['flash_error'] = 'Failed to save uploaded file. Check server write permissions.';
            $this->redirectBack($customerId);
        }

        $pdo  = Database::getConnection();
        $user = AuthMiddleware::currentUser();

        $pdo->prepare("INSERT INTO documents (customer_id, document_type, file_path, file_name) VALUES (?, ?, ?, ?)")
            ->execute([$customerId, $docType, $safeName, $file['name']]);

        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Upload Document', "customers#{$customerId} ({$docType})", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = "Document '{$file['name']}' uploaded successfully.";
        $this->redirectBack($customerId);
    }

    public function download(): void {
        $id  = (int)($_GET['id'] ?? 0);
        $pdo = Database::getConnection();
        $doc = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $doc->execute([$id]);
        $row = $doc->fetch();

        if (!$row) {
            http_response_code(404);
            echo 'Document not found.';
            exit;
        }

        $filePath = self::UPLOAD_DIR . $row['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'File missing from server.';
            exit;
        }

        $mime = mime_content_type($filePath);
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . addslashes($row['file_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function delete(): void {
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Invalid token.';
            $this->redirectBack();
        }

        $id         = (int)($_POST['doc_id']    ?? 0);
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $pdo        = Database::getConnection();

        $doc = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND customer_id = ?");
        $doc->execute([$id, $customerId]);
        $row = $doc->fetch();

        if (!$row) {
            $_SESSION['flash_error'] = 'Document not found.';
            $this->redirectBack($customerId);
        }

        // Remove physical file
        $filePath = self::UPLOAD_DIR . $row['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$id]);

        $user = AuthMiddleware::currentUser();
        $pdo->prepare("INSERT INTO audit_logs (employee_id, employee_name, action, affected_record, ip_address) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $user['name'], 'Delete Document', "documents#{$id}", $_SERVER['REMOTE_ADDR'] ?? '']);

        $_SESSION['flash_success'] = "Document '{$row['file_name']}' deleted.";
        $this->redirectBack($customerId);
    }

    private function redirectBack(int $customerId = 0): never {
        $url = $customerId ? "/customers?action=view&id={$customerId}&tab=documents" : '/customers';
        redirect($url);
    }
}

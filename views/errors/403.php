<?php
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="container py-5 text-center">
    <div class="card border-0 shadow-sm p-5 max-w-lg mx-auto">
        <div class="mb-3 text-danger fs-1">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h2 class="fw-bold mb-2">403 - Access Forbidden</h2>
        <p class="text-muted mb-4">You do not have the necessary permission to access this module.</p>
        <div>
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary px-4 rounded-pill">Return to Dashboard</a>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/../layouts/footer.php';
?>

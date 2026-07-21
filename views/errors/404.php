<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center px-4">
        <div style="font-size: 6rem; line-height:1; color: #cbd5e1;">
            <i class="bi bi-exclamation-circle"></i>
        </div>
        <h1 class="fw-black mb-1" style="font-size: 5rem; color: var(--primary);">404</h1>
        <h4 class="fw-bold text-dark mb-2">Page Not Found</h4>
        <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary-custom px-5 fw-semibold">
            <i class="bi bi-house-fill me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

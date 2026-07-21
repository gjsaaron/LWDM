<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #0284c7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5 m-3">
    <div class="text-center mb-4">
        <img src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo" class="rounded-circle mb-3 shadow" style="width: 72px; height: 72px; object-fit: cover;">
        <h4 class="fw-bold mb-1 text-dark"><?= APP_NAME ?></h4>
        <p class="text-muted small">Sign in to your staff portal account</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show text-sm py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/login" method="POST">
        <?= CSRF::inputField() ?>
        
        <div class="mb-3">
            <label class="form-label fw-medium text-secondary">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                <input type="text" name="username" class="form-control bg-light border-start-0" placeholder="Enter your username" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-medium text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-semibold mb-3">
            Sign In <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>

    <div class="border-top pt-3 text-center">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>Contact your system administrator if you cannot log in.
        </small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

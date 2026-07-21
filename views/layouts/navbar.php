<?php
$user = AuthMiddleware::currentUser();
?>
<nav class="navbar navbar-expand-lg top-navbar">
    <div class="container-fluid px-0">
        <button class="btn btn-sm btn-light border-0 me-3" id="sidebarCollapse">
            <i class="bi bi-list fs-5"></i>
        </button>

        <form class="d-none d-md-flex me-auto max-w-md w-50" action="<?= BASE_URL ?>/customers" method="GET">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="search" name="search" class="form-control bg-light border-start-0 ps-0 shadow-none" placeholder="Search account number, name, or meter number..." aria-label="Search">
            </div>
        </form>

        <div class="d-flex align-items-center gap-3 ms-auto">

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 36px; height: 36px;">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="d-none d-sm-block text-start me-1">
                        <div class="fw-semibold lh-1 fs-7"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                        <small class="text-muted fs-8"><?= htmlspecialchars($user['role'] ?? 'Role') ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/profile"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

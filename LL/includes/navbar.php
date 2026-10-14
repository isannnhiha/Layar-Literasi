<?php
/**
 * Global Navigation Bar
 * Layar Literasi Digital Library
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand-logo" href="<?= BASE_URL ?>/index.php">
            <div class="brand-icon-box">
                <i class="bi bi-book-half"></i>
            </div>
            <div class="brand-title">
                Layar<span>Literasi</span>
            </div>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Center Navigation (Pill-group as seen in Figma) -->
            <div class="mx-auto my-2 my-lg-0">
                <div class="nav-pill-group">
                    <a href="<?= BASE_URL ?>/index.php" class="nav-pill-link <?= ($currentPage === 'index.php' && $currentDir !== 'admin' && $currentDir !== 'member') ? 'active' : '' ?>">
                        Beranda
                    </a>
                    <a href="<?= BASE_URL ?>/catalog.php" class="nav-pill-link <?= ($currentPage === 'catalog.php' || $currentPage === 'book-detail.php') ? 'active' : '' ?>">
                        Katalog Buku
                    </a>
                    <a href="<?= BASE_URL ?>/catalog.php#kategori" class="nav-pill-link">
                        Kategori
                    </a>
                    <a href="<?= BASE_URL ?>/membership.php" class="nav-pill-link <?= ($currentPage === 'membership.php' || $currentPage === 'payment.php') ? 'active' : '' ?>">
                        Membership
                    </a>
                    <a href="<?= BASE_URL ?>/index.php#tentang" class="nav-pill-link">
                        Tentang
                    </a>
                </div>
            </div>

            <!-- Right Actions / User Profile -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($currentUser): ?>
                    <!-- Quick notification or favorites -->
                    <?php if ($currentUser['role'] === 'member'): ?>
                        <a href="<?= BASE_URL ?>/member/favorites.php" class="btn btn-light rounded-circle text-muted p-2 d-none d-md-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Buku Favorit">
                            <i class="bi bi-heart-fill text-danger"></i>
                        </a>
                    <?php endif; ?>

                    <!-- User Dropdown Menu -->
                    <div class="dropdown">
                        <button class="btn btn-light border-0 dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 rounded-pill shadow-sm" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= getUserAvatarUrl($currentUser['avatar']) ?>" alt="<?= e($currentUser['name']) ?>" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                            <div class="text-start d-none d-sm-block">
                                <span class="d-block fw-bold text-dark lh-1" style="font-size: 0.85rem;"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                                <small class="text-muted" style="font-size: 0.72rem;"><?= ucfirst($currentUser['role']) ?></small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" aria-labelledby="userMenuBtn" style="min-width: 240px;">
                            <li class="px-3 py-2 border-bottom mb-2">
                                <p class="fw-bold mb-0 text-dark"><?= e($currentUser['name']) ?></p>
                                <small class="text-muted d-block text-truncate"><?= e($currentUser['email']) ?></small>
                                <div class="mt-1">
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <span class="badge bg-dark text-white"><i class="bi bi-shield-lock-fill me-1"></i>Administrator</span>
                                    <?php else: ?>
                                        <?= getMembershipBadge($currentUser['membership_slug'] ?? 'free', $currentUser['membership_name'] ?? 'Free') ?>
                                    <?php endif; ?>
                                </div>
                            </li>

                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 text-primary fw-semibold" href="<?= BASE_URL ?>/admin/index.php">
                                        <i class="bi bi-speedometer2"></i> Dashboard Admin
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/books.php">
                                        <i class="bi bi-journal-bookmark"></i> Kelola Buku
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/categories.php">
                                        <i class="bi bi-tags"></i> Kelola Kategori
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/members.php">
                                        <i class="bi bi-people"></i> Kelola Anggota
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/borrowings.php">
                                        <i class="bi bi-arrow-left-right"></i> Peminjaman Buku
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/reports.php">
                                        <i class="bi bi-bar-chart-line"></i> Laporan & Statistik
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 text-primary fw-semibold" href="<?= BASE_URL ?>/member/index.php">
                                        <i class="bi bi-person-workspace"></i> Dashboard Member
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/borrowings.php">
                                        <i class="bi bi-journal-check"></i> Pinjaman Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/progress.php">
                                        <i class="bi bi-bookmark-check"></i> Progress & Bookmark
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/favorites.php">
                                        <i class="bi bi-heart"></i> Buku Favorit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 text-warning fw-semibold" href="<?= BASE_URL ?>/membership.php">
                                        <i class="bi bi-gem"></i> Upgrade Membership
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/profile.php">
                                        <i class="bi bi-gear"></i> Pengaturan Akun
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 text-danger" href="<?= BASE_URL ?>/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Keluar
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold" style="font-size: 0.9rem;">
                        Masuk
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-orange py-2 px-3 fw-semibold rounded-pill" style="font-size: 0.9rem;">
                        Daftar Gratis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

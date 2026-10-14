<?php
/**
 * Member Dashboard
 * Layar Literasi Digital Library
 */
$pageTitle = "Dashboard Anggota";
require_once __DIR__ . '/../config/app.php';

requireMember();
$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

// Counts
$activeBorrowCount = getUserActiveBorrowingsCount($userId);
$totalHistoryCount = $db->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $userId")->fetchColumn();
$favCount = $db->query("SELECT COUNT(*) FROM favorites WHERE user_id = $userId")->fetchColumn();
$progressCount = $db->query("SELECT COUNT(*) FROM reading_progress WHERE user_id = $userId")->fetchColumn();

// Currently borrowed books
$stmtActive = $db->prepare("
    SELECT b.*, bk.title, bk.cover_image, bk.pdf_file, bk.author, c.name AS category_name 
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE b.user_id = ? AND b.status IN ('borrowed', 'overdue')
    ORDER BY b.due_date ASC
");
$stmtActive->execute([$userId]);
$activeBorrowings = $stmtActive->fetchAll();

// Currently reading book (latest progress)
$stmtProg = $db->prepare("
    SELECT rp.*, bk.title, bk.author, bk.cover_image, bk.pdf_file, c.name AS category_name 
    FROM reading_progress rp
    JOIN books bk ON rp.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE rp.user_id = ?
    ORDER BY rp.last_read_at DESC
    LIMIT 1
");
$stmtProg->execute([$userId]);
$latestProgress = $stmtProg->fetch();

// Recent favorites
$stmtFavs = $db->prepare("
    SELECT f.*, bk.title, bk.author, bk.cover_image, bk.membership_tier, bk.rating, c.name AS category_name 
    FROM favorites f
    JOIN books bk ON f.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 4
");
$stmtFavs->execute([$userId]);
$favoriteBooks = $stmtFavs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #101c34 0%, #1e293b 100%);">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <?= getMembershipBadge($user['membership_slug'] ?? 'free', $user['membership_name'] ?? 'Free') ?>
                    <?php if (!empty($user['membership_expires_at'])): ?>
                        <span class="badge bg-white bg-opacity-25 text-white">Berlaku s/d <?= formatDate($user['membership_expires_at']) ?></span>
                    <?php endif; ?>
                </div>
                <h2 class="fw-bold mb-2">Halo, <?= e($user['name']) ?>! 👋</h2>
                <p class="text-white-50 mb-3" style="max-width: 520px;">
                    Selamat datang di ruang baca digital Anda. Lanjutkan eksplorasi ribuan buku berkualitas dan pantau aktivitas literasi Anda di sini.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-orange btn-sm px-3 py-2 rounded-pill fw-semibold shadow">
                        <i class="bi bi-compass me-1"></i> Jelajahi Buku Baru
                    </a>
                    <?php if (($user['membership_slug'] ?? 'free') === 'free'): ?>
                        <a href="<?= BASE_URL ?>/membership.php" class="btn btn-warning btn-sm px-3 py-2 rounded-pill fw-semibold text-dark shadow-sm">
                            <i class="bi bi-gem me-1"></i> Upgrade ke Premium
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0 d-none d-md-block">
                <i class="bi bi-book-half display-1 text-white opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Sedang Dipinjam</p>
                    <h3><?= $activeBorrowCount ?> <span class="fs-6 text-muted fw-normal">/ <?= $user['max_borrow_count'] ?? 2 ?></span></h3>
                </div>
                <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-journal-check"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Total Peminjaman</p>
                    <h3><?= $totalHistoryCount ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Buku Favorit</p>
                    <h3><?= $favCount ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-heart-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Sedang Dibaca</p>
                    <h3><?= $progressCount ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-bookmark-star-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Resume Reading Banner (If active) -->
    <?php if ($latestProgress): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= getBookCoverUrl($latestProgress['cover_image']) ?>" alt="<?= e($latestProgress['title']) ?>" style="width: 55px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <div>
                        <span class="badge bg-warning text-dark mb-1"><i class="bi bi-play-circle-fill me-1"></i>Lanjutkan Membaca</span>
                        <h5 class="fw-bold text-dark mb-1"><?= e($latestProgress['title']) ?></h5>
                        <p class="text-muted small mb-0">Halaman <?= $latestProgress['current_page'] ?> dari <?= $latestProgress['total_pages'] ?> (<?= (float)$latestProgress['percentage'] ?>% Selesai)</p>
                        <div class="progress mt-2" style="height: 6px; width: 220px;">
                            <div class="progress-bar bg-orange" style="width: <?= (float)$latestProgress['percentage'] ?>%;"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/read.php?id=<?= $latestProgress['book_id'] ?>" class="btn btn-orange px-4 py-2 rounded-pill fw-semibold shadow-sm">
                        Buka Halaman <?= $latestProgress['current_page'] ?> <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Active Borrowings Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-hourglass-split text-primary me-2"></i>Buku Sedang Dipinjam
                    </h5>
                    <a href="<?= BASE_URL ?>/member/borrowings.php" class="small text-decoration-none fw-semibold">Lihat Semua &rarr;</a>
                </div>

                <?php if (empty($activeBorrowings)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-journal display-4 d-block mb-2"></i>
                        <p class="mb-3">Anda sedang tidak meminjam buku apa pun saat ini.</p>
                        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            Jelajahi Katalog Buku
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th>Buku</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Sisa Waktu</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeBorrowings as $ab): ?>
                                    <?php 
                                        $daysLeft = calculateDaysRemaining($ab['due_date']);
                                        $isOverdue = ($daysLeft < 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= getBookCoverUrl($ab['cover_image']) ?>" alt="<?= e($ab['title']) ?>" style="width: 36px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                <div>
                                                    <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $ab['book_id'] ?>" class="fw-bold text-dark text-decoration-none d-block text-truncate" style="max-width: 220px;">
                                                        <?= e($ab['title']) ?>
                                                    </a>
                                                    <small class="text-muted"><?= e($ab['category_name']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small <?= $isOverdue ? 'text-danger fw-bold' : 'text-dark' ?>">
                                                <?= formatDate($ab['due_date']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger">Telat <?= abs($daysLeft) ?> hari</span>
                                            <?php elseif ($daysLeft == 0): ?>
                                                <span class="badge bg-warning text-dark">Hari Ini!</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?= $daysLeft ?> hari lagi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <?php if (!empty($ab['pdf_file'])): ?>
                                                    <a href="<?= BASE_URL ?>/read.php?id=<?= $ab['book_id'] ?>" class="btn btn-sm btn-orange py-1 px-2.5 rounded-pill" title="Baca Online">
                                                        <i class="bi bi-book"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <form action="<?= BASE_URL ?>/return.php" method="POST" onsubmit="return confirm('Kembalikan buku ini?');">
                                                    <?= getCsrfField() ?>
                                                    <input type="hidden" name="borrowing_id" value="<?= $ab['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2.5 rounded-pill" title="Kembalikan Buku">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Favorites Sidebar Column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-heart-fill text-danger me-2"></i>Buku Favorit
                    </h5>
                    <a href="<?= BASE_URL ?>/member/favorites.php" class="small text-decoration-none fw-semibold">Kelola &rarr;</a>
                </div>

                <?php if (empty($favoriteBooks)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-heart display-6 d-block mb-2"></i>
                        <p class="small mb-0">Belum ada buku favorit yang disimpan.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($favoriteBooks as $fb): ?>
                            <div class="d-flex align-items-center gap-3 p-2 rounded-3 bg-light">
                                <img src="<?= getBookCoverUrl($fb['cover_image']) ?>" alt="<?= e($fb['title']) ?>" style="width: 40px; height: 56px; object-fit: cover; border-radius: 4px;">
                                <div class="flex-grow-1 overflow-hidden">
                                    <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $fb['book_id'] ?>" class="fw-bold text-dark text-decoration-none d-block text-truncate small">
                                        <?= e($fb['title']) ?>
                                    </a>
                                    <small class="text-muted d-block"><?= e($fb['author']) ?></small>
                                    <small class="text-warning"><i class="bi bi-star-fill"></i> <?= number_format($fb['rating'], 1) ?></small>
                                </div>
                                <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $fb['book_id'] ?>" class="btn btn-sm btn-light border rounded-circle p-1" style="width: 32px; height: 32px;">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

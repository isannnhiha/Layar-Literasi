<?php
/**
 * Member Borrowing History
 * Layar Literasi Digital Library
 */
$pageTitle = "Riwayat Peminjaman Buku";
require_once __DIR__ . '/../config/app.php';

requireMember();
$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = ["b.user_id = ?"];
$params = [$userId];

if (in_array($statusFilter, ['borrowed', 'returned', 'overdue'])) {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(b.borrow_code LIKE ? OR bk.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT b.*, bk.title, bk.author, bk.cover_image, bk.pdf_file, c.name AS category_name 
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    JOIN categories c ON bk.category_id = c.id
    WHERE $whereClause
    ORDER BY b.id DESC
");
$stmt->execute($params);
$borrowings = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Peminjaman Buku</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold text-dark mb-0">Riwayat Peminjaman Saya</h2>
        </div>
        <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-orange rounded-pill px-4 py-2 mt-3 mt-md-0 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Pinjam Buku Lain
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form action="<?= BASE_URL ?>/member/borrowings.php" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Cari kode pinjam atau judul buku..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select bg-light" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="borrowed" <?= ($statusFilter === 'borrowed') ? 'selected' : '' ?>>Sedang Dipinjam</option>
                    <option value="overdue" <?= ($statusFilter === 'overdue') ? 'selected' : '' ?>>Terlambat / Jatuh Tempo</option>
                    <option value="returned" <?= ($statusFilter === 'returned') ? 'selected' : '' ?>>Sudah Dikembalikan</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">Filter</button>
                <?php if ($statusFilter || $search): ?>
                    <a href="<?= BASE_URL ?>/member/borrowings.php" class="btn btn-light rounded-pill border" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Borrowings Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <?php if (empty($borrowings)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-journal-x display-3 text-muted d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Belum Ada Riwayat Peminjaman</h5>
                <p class="small text-muted mb-3">Buku yang Anda pinjam dari perpustakaan akan tercatat rapi di sini.</p>
                <a href="<?= BASE_URL ?>/catalog.php" class="btn btn-outline-primary rounded-pill px-4">
                    Jelajahi Buku Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th class="ps-4">Kode Pinjam</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowings as $b): ?>
                            <?php 
                                $daysLeft = calculateDaysRemaining($b['due_date']);
                                $isOverdue = ($b['status'] === 'borrowed' && $daysLeft < 0);
                                $displayStatus = $isOverdue ? 'overdue' : $b['status'];
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold small text-secondary">
                                    <code><?= e($b['borrow_code']) ?></code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= getBookCoverUrl($b['cover_image']) ?>" alt="<?= e($b['title']) ?>" style="width: 40px; height: 56px; object-fit: cover; border-radius: 6px;">
                                        <div>
                                            <a href="<?= BASE_URL ?>/book-detail.php?id=<?= $b['book_id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                                                <?= e($b['title']) ?>
                                            </a>
                                            <small class="text-muted"><?= e($b['author']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="small"><?= formatDate($b['borrow_date']) ?></td>
                                <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <?= formatDate($b['due_date']) ?>
                                    <?php if ($b['status'] === 'borrowed'): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php if ($daysLeft < 0): ?>
                                                <span class="text-danger fw-bold">Telat <?= abs($daysLeft) ?> hari</span>
                                            <?php elseif ($daysLeft == 0): ?>
                                                <span class="text-warning fw-bold">Hari ini!</span>
                                            <?php else: ?>
                                                <?= $daysLeft ?> hari lagi
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?= $b['return_date'] ? formatDate($b['return_date']) : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <?= getStatusBadge($displayStatus) ?>
                                </td>
                                <td class="small fw-semibold <?= $b['fine_amount'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= formatRupiah($b['fine_amount']) ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if (!empty($b['pdf_file'])): ?>
                                            <a href="<?= BASE_URL ?>/read.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-orange rounded-pill px-3" title="Baca Online">
                                                <i class="bi bi-book me-1"></i> Baca
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($b['status'] !== 'returned'): ?>
                                            <form action="<?= BASE_URL ?>/return.php" method="POST" onsubmit="return confirm('Kembalikan buku ini sekarang?');">
                                                <?= getCsrfField() ?>
                                                <input type="hidden" name="borrowing_id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Kembalikan Buku">
                                                    <i class="bi bi-arrow-return-left me-1"></i> Kembalikan
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

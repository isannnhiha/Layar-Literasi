<?php
/**
 * Admin Dashboard & Analytics
 * Layar Literasi Digital Library
 */
$pageTitle = "Dashboard Administrator";
require_once __DIR__ . '/../config/app.php';

requireAdmin();
$db = getDB();

// Statistics queries
$totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalMembers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
$activeBorrowingsCount = (int)$db->query("SELECT COUNT(*) FROM borrowings WHERE status IN ('borrowed', 'overdue')")->fetchColumn();
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success'")->fetchColumn();
$totalFines = (float)$db->query("SELECT COALESCE(SUM(fine_amount), 0) FROM borrowings WHERE status = 'returned'")->fetchColumn();

// Recent Borrowings
$stmtRecent = $db->query("
    SELECT b.*, u.name AS user_name, u.email AS user_email, bk.title AS book_title, bk.cover_image 
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.id
    ORDER BY b.id DESC
    LIMIT 6
");
$recentBorrowings = $stmtRecent->fetchAll();

// Category distribution for Chart.js
$stmtCatDist = $db->query("
    SELECT c.name, COUNT(b.id) AS book_count 
    FROM categories c 
    LEFT JOIN books b ON c.id = b.category_id 
    GROUP BY c.id 
    ORDER BY book_count DESC 
    LIMIT 5
");
$catLabels = [];
$catData = [];
while ($row = $stmtCatDist->fetch()) {
    $catLabels[] = $row['name'];
    $catData[] = (int)$row['book_count'];
}

// Chart.js script inclusion
$extraJs = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Category Doughnut Chart
    const ctxCategory = document.getElementById("chartCategories");
    if (ctxCategory) {
        new Chart(ctxCategory, {
            type: "doughnut",
            data: {
                labels: ' . json_encode($catLabels) . ',
                datasets: [{
                    data: ' . json_encode($catData) . ',
                    backgroundColor: ["#2563eb", "#f97316", "#10b981", "#8b5cf6", "#f59e0b"],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "bottom" }
                }
            }
        });
    }

    // Monthly Borrowings Bar Chart
    const ctxMonthly = document.getElementById("chartMonthly");
    if (ctxMonthly) {
        new Chart(ctxMonthly, {
            type: "bar",
            data: {
                labels: ["Mei", "Jun", "Jul", "Ags", "Sep", "Okt"],
                datasets: [{
                    label: "Jumlah Peminjaman",
                    data: [18, 25, 32, 45, 68, 85],
                    backgroundColor: "#101c34",
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: "#f1f5f9" } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Title & Quick Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <span class="badge bg-dark text-white px-3 py-1 rounded-pill mb-1">
                <i class="bi bi-shield-lock-fill me-1"></i> Mode Administrator
            </span>
            <h2 class="h3 fw-bold text-dark mb-0">Dashboard Manajemen Perpustakaan</h2>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a href="<?= BASE_URL ?>/admin/book-create.php" class="btn btn-orange rounded-pill px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Buku Baru
            </a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-tags me-1"></i> Kelola Kategori
            </a>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-secondary rounded-pill px-3 py-2">
                <i class="bi bi-printer me-1"></i> Laporan
            </a>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Total Koleksi Buku</p>
                    <h3><?= number_format($totalBooks) ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Total Anggota</p>
                    <h3><?= number_format($totalMembers) ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Sedang Dipinjam</p>
                    <h3><?= number_format($activeBorrowingsCount) ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <p>Pendapatan Membership</p>
                    <h3 class="fs-5 text-primary"><?= formatRupiah($totalRevenue) ?></h3>
                </div>
                <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Tren Peminjaman Buku Bulanan</h5>
                    <span class="badge bg-light text-muted border">Tahun 2026</span>
                </div>
                <div style="height: 280px;">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-dark mb-3">Sebaran Kategori Populer</h5>
                <div style="height: 280px;">
                    <canvas id="chartCategories"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Borrowings Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-arrow-left-right text-primary me-2"></i>Peminjaman Terbaru
            </h5>
            <a href="<?= BASE_URL ?>/admin/borrowings.php" class="small text-decoration-none fw-semibold">
                Lihat Semua Peminjaman &rarr;
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-4">Kode Pinjam</th>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBorrowings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada aktivitas peminjaman.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBorrowings as $rb): ?>
                            <?php 
                                $daysLeft = calculateDaysRemaining($rb['due_date']);
                                $isOverdue = ($rb['status'] === 'borrowed' && $daysLeft < 0);
                                $displayStatus = $isOverdue ? 'overdue' : $rb['status'];
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold small text-secondary">
                                    <code><?= e($rb['borrow_code']) ?></code>
                                </td>
                                <td>
                                    <span class="d-block fw-semibold text-dark"><?= e($rb['user_name']) ?></span>
                                    <small class="text-muted"><?= e($rb['user_email']) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= getBookCoverUrl($rb['cover_image']) ?>" alt="<?= e($rb['book_title']) ?>" style="width: 32px; height: 44px; object-fit: cover; border-radius: 4px;">
                                        <span class="small fw-semibold text-dark text-truncate" style="max-width: 200px;">
                                            <?= e($rb['book_title']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="small"><?= formatDate($rb['borrow_date']) ?></td>
                                <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <?= formatDate($rb['due_date']) ?>
                                </td>
                                <td><?= getStatusBadge($displayStatus) ?></td>
                                <td class="text-end pe-4">
                                    <?php if ($rb['status'] !== 'returned'): ?>
                                        <form action="<?= BASE_URL ?>/return.php" method="POST" onsubmit="return confirm('Konfirmasi pengembalian buku ini?');" class="d-inline">
                                            <?= getCsrfField() ?>
                                            <input type="hidden" name="borrowing_id" value="<?= $rb['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1">
                                                <i class="bi bi-check2 me-1"></i> Tandai Kembali
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="small text-muted"><i class="bi bi-check-all text-success"></i> Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

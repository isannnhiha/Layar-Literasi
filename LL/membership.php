<?php
/**
 * Membership Plans & Subscription Page
 * Layar Literasi Digital Library
 */
$pageTitle = "Pilihan Paket Membership";
require_once __DIR__ . '/config/app.php';

$db = getDB();
$memberships = $db->query("SELECT * FROM memberships ORDER BY price ASC")->fetchAll();
$currentUser = currentUser();

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-white border-bottom">
    <div class="container text-center max-w-700 mx-auto">
        <span class="section-badge-orange">Investasi Literasi</span>
        <h1 class="display-6 fw-bold text-dark mb-3">Tingkatkan Akses Membacamu</h1>
        <p class="text-muted fs-6 mb-0">
            Dapatkan fleksibilitas membaca buku premium, durasi peminjaman lebih panjang, serta kuota peminjaman yang lebih banyak dengan paket membership Layar Literasi.
        </p>

        <?php if ($currentUser): ?>
            <div class="mt-4 p-3 rounded-4 bg-light d-inline-flex align-items-center gap-3 border">
                <span class="text-muted small">Paket Anda Saat Ini:</span>
                <?= getMembershipBadge($currentUser['membership_slug'] ?? 'free', $currentUser['membership_name'] ?? 'Free') ?>
                <?php if (!empty($currentUser['membership_expires_at'])): ?>
                    <span class="text-muted small">Berlaku s/d: <strong><?= formatDate($currentUser['membership_expires_at']) ?></strong></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 justify-content-center align-items-stretch">
        <?php foreach ($memberships as $m): ?>
            <?php 
                $isCurrent = $currentUser && ($currentUser['membership_id'] == $m['id']);
                $isFeatured = ($m['slug'] === 'premium');
                $features = json_decode($m['features'] ?? '[]', true) ?: [];
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?>">
                    <?php if ($isFeatured): ?>
                        <div class="pricing-featured-badge">PALING POPULER</div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold mb-0 text-dark"><?= e($m['name']) ?></h4>
                        <?php if ($isCurrent): ?>
                            <span class="badge bg-success">Paket Aktif</span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-muted small mt-1 mb-2"><?= e($m['description']) ?></p>

                    <div class="pricing-price <?= $isFeatured ? 'text-orange' : '' ?>">
                        <?= formatRupiah($m['price']) ?> <span>/ <?= $m['duration_days'] ?> hari</span>
                    </div>

                    <ul class="pricing-features-list">
                        <?php foreach ($features as $f): ?>
                            <li>
                                <i class="bi bi-check-circle-fill <?= $isFeatured ? 'text-warning' : 'text-success' ?>"></i>
                                <span><?= e($f) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li>
                            <i class="bi bi-journal-check <?= $isFeatured ? 'text-warning' : 'text-success' ?>"></i>
                            <span>Pinjam maks. <strong><?= $m['max_borrow_count'] ?> buku</strong></span>
                        </li>
                        <li>
                            <i class="bi bi-clock-history <?= $isFeatured ? 'text-warning' : 'text-success' ?>"></i>
                            <span>Durasi pinjam <strong><?= $m['max_borrow_days'] ?> hari</strong></span>
                        </li>
                    </ul>

                    <div class="mt-auto">
                        <?php if (!$currentUser): ?>
                            <a href="<?= BASE_URL ?>/login.php?redirect=membership.php" class="btn <?= $isFeatured ? 'btn-orange' : 'btn-outline-primary' ?> w-100 py-2.5 rounded-pill fw-semibold">
                                Masuk untuk Memilih
                            </a>
                        <?php elseif ($isCurrent): ?>
                            <button type="button" class="btn btn-light w-100 py-2.5 rounded-pill fw-semibold border" disabled>
                                Paket Sedang Aktif
                            </button>
                        <?php elseif ($m['price'] == 0): ?>
                            <button type="button" class="btn btn-outline-secondary w-100 py-2.5 rounded-pill" disabled>
                                Paket Standar
                            </button>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/payment.php?membership_id=<?= $m['id'] ?>" class="btn <?= $isFeatured ? 'btn-orange shadow' : 'btn-primary' ?> w-100 py-2.5 rounded-pill fw-semibold">
                                Upgrade ke <?= e($m['name']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FAQ & Guarantees -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mt-5 bg-white">
        <h4 class="fw-bold text-dark text-center mb-4">Pertanyaan Seputar Membership</h4>
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="bi bi-question-circle text-primary me-2"></i>Bagaimana cara mengaktifkan paket Premium?</h6>
                <p class="text-muted small">
                    Pilih paket yang Anda inginkan, selesaikan proses konfirmasi pembayaran melalui QRIS atau transfer virtual account, dan sistem akan mengaktifkan paket Anda secara instan.
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="bi bi-question-circle text-primary me-2"></i>Apakah saya bisa meminjam lebih banyak buku?</h6>
                <p class="text-muted small">
                    Ya, paket Basic dapat meminjam hingga 5 buku sekaligus selama 7 hari, dan paket Premium dapat meminjam hingga 10 buku sekaligus selama 14 hari.
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="bi bi-question-circle text-primary me-2"></i>Apakah membaca PDF online dibatasi?</h6>
                <p class="text-muted small">
                    Untuk paket Basic dan Premium, Anda dapat membaca seluruh katalog yang sesuai tanpa batas halaman dengan fitur penyimpanan bookmark otomatis.
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-dark"><i class="bi bi-question-circle text-primary me-2"></i>Kapan masa berlaku membership berakhir?</h6>
                <p class="text-muted small">
                    Masa aktif berlangganan adalah 30 hari sejak tanggal pembayaran berhasil diverifikasi. Anda dapat memperpanjang paket kapan saja.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * Payment & Subscription Checkout Simulation
 * Layar Literasi Digital Library
 */
$pageTitle = "Checkout & Pembayaran Membership";
require_once __DIR__ . '/config/app.php';

requireLogin();

$db = getDB();
$currentUser = currentUser();

$membershipId = isset($_GET['membership_id']) ? (int)$_GET['membership_id'] : 0;
if ($membershipId <= 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('membership.php');
}

// Fetch membership details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $targetMembershipId = (int)$_POST['membership_id'];
    $paymentMethod = trim($_POST['payment_method'] ?? 'QRIS Instant');

    $stmtM = $db->prepare("SELECT * FROM memberships WHERE id = ?");
    $stmtM->execute([$targetMembershipId]);
    $targetM = $stmtM->fetch();

    if (!$targetM || $targetM['price'] <= 0) {
        setFlash('danger', 'Paket membership tidak valid.');
        redirect('membership.php');
    }

    $orderId = 'INV-LL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    $amount = $targetM['price'];
    $days = (int)$targetM['duration_days'];
    $newExpiry = date('Y-m-d', strtotime("+$days days"));

    try {
        $db->beginTransaction();

        // Record payment
        $stmtPay = $db->prepare("
            INSERT INTO payments (user_id, membership_id, order_id, amount, payment_method, status, payment_date, created_at)
            VALUES (?, ?, ?, ?, ?, 'success', NOW(), NOW())
        ");
        $stmtPay->execute([$currentUser['id'], $targetMembershipId, $orderId, $amount, $paymentMethod]);

        // Update user membership
        $stmtUser = $db->prepare("
            UPDATE users 
            SET membership_id = ?, membership_expires_at = ? 
            WHERE id = ?
        ");
        $stmtUser->execute([$targetMembershipId, $newExpiry, $currentUser['id']]);

        $db->commit();

        setFlash('success', "Selamat! Pembayaran berhasil. Akun Anda kini aktif sebagai member {$targetM['name']} hingga " . formatDate($newExpiry) . ".");
        redirect('member/index.php');

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        setFlash('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
        redirect('membership.php');
    }
}

// GET view
$stmt = $db->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->execute([$membershipId]);
$membership = $stmt->fetch();

if (!$membership || $membership['price'] <= 0) {
    redirect('membership.php');
}

$sampleOrderId = 'INV-LL-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-4" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/membership.php" class="text-decoration-none">Membership</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout Pembayaran</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-warning text-dark mb-2">Simulasi Gateway Pembayaran</span>
                            <h3 class="fw-bold mb-0">Konfirmasi Langganan</h3>
                        </div>
                        <i class="bi bi-shield-check display-5 text-white-50"></i>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <div class="row g-4 mb-4">
                        <!-- Invoice details -->
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-dark mb-3">Rincian Paket Terpilih</h6>
                            <div class="p-3 bg-light rounded-3 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Paket:</span>
                                    <strong class="text-dark"><?= e($membership['name']) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Masa Aktif:</span>
                                    <span><?= $membership['duration_days'] ?> Hari</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Maksimal Pinjam:</span>
                                    <span><?= $membership['max_borrow_count'] ?> Buku</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Akses Premium:</span>
                                    <span class="text-success"><?= $membership['can_read_premium'] ? 'Ya, Tanpa Batas' : 'Standar' ?></span>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark mb-2">Informasi Pemesan</h6>
                            <p class="small text-muted mb-1">Nama: <strong><?= e($currentUser['name']) ?></strong></p>
                            <p class="small text-muted mb-0">Email: <strong><?= e($currentUser['email']) ?></strong></p>
                        </div>

                        <!-- Price calculation -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-3">Rincian Tagihan</h6>
                            <ul class="list-group list-group-flush small mb-3">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Harga Paket Langganan</span>
                                    <span><?= formatRupiah($membership['price']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Biaya Administrasi & Pajak</span>
                                    <span class="text-success">Rp 0 (Gratis)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0 fs-6 fw-bold border-top pt-2">
                                    <span>Total Pembayaran</span>
                                    <span class="text-primary"><?= formatRupiah($membership['price']) ?></span>
                                </li>
                            </ul>

                            <div class="alert alert-info py-2 px-3 small border-0 rounded-3">
                                <i class="bi bi-info-circle-fill me-1"></i> Ini adalah simulasi transaksi sandbox. Saldo atau akun Anda akan diaktifkan secara instan setelah menekan tombol bayar.
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?= BASE_URL ?>/payment.php" method="POST">
                        <?= getCsrfField() ?>
                        <input type="hidden" name="membership_id" value="<?= $membership['id'] ?>">

                        <h6 class="fw-bold text-dark mb-3">Pilih Metode Pembayaran</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="border rounded-3 p-3 d-flex align-items-center gap-3 w-100 cursor-pointer h-100">
                                    <input type="radio" name="payment_method" value="QRIS Instant" checked class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-qr-code-scan me-1 text-primary"></i> QRIS Instant</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">BCA, GoPay, OVO, Dana, ShopeePay</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="border rounded-3 p-3 d-flex align-items-center gap-3 w-100 cursor-pointer h-100">
                                    <input type="radio" name="payment_method" value="Transfer Bank BCA" class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-bank me-1 text-primary"></i> Virtual Account BCA</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Verifikasi Otomatis 24 Jam</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="border rounded-3 p-3 d-flex align-items-center gap-3 w-100 cursor-pointer h-100">
                                    <input type="radio" name="payment_method" value="Transfer Bank Mandiri" class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-bank2 me-1 text-primary"></i> Mandiri Livin'</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Virtual Account Instan</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="border rounded-3 p-3 d-flex align-items-center gap-3 w-100 cursor-pointer h-100">
                                    <input type="radio" name="payment_method" value="E-Wallet GoPay" class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-wallet2 me-1 text-primary"></i> GoPay / OVO</strong>
                                        <small class="text-muted" style="font-size: 0.75rem;">Konfirmasi Cepat 1 Klik</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="<?= BASE_URL ?>/membership.php" class="btn btn-light rounded-pill px-4">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-orange px-5 py-2.5 rounded-pill fw-semibold shadow">
                                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang (<?= formatRupiah($membership['price']) ?>)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

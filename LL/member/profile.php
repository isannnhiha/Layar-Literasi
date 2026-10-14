<?php
/**
 * Member Profile Settings
 * Layar Literasi Digital Library
 */
$pageTitle = "Pengaturan Profil Saya";
require_once __DIR__ . '/../config/app.php';

requireMember();
$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $error = 'Nama tidak boleh kosong.';
        } else {
            // Check avatar upload
            $avatarName = $user['avatar'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Format avatar harus JPG, PNG, atau WebP.';
                } else {
                    $newAvatar = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($fileTmp, UPLOADS_PATH . '/avatars/' . $newAvatar)) {
                        $avatarName = $newAvatar;
                    }
                }
            }

            if (!$error) {
                $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $avatarName, $userId]);
                $_SESSION['user_name'] = $name;
                setFlash('success', 'Profil Anda berhasil diperbarui.');
                redirect('member/profile.php');
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password'])) {
            $error = 'Kata sandi saat ini tidak cocok.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Kata sandi baru minimal 6 karakter.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'Konfirmasi kata sandi baru tidak sama.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $userId]);
            setFlash('success', 'Kata sandi berhasil diperbarui.');
            redirect('member/profile.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/index.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengaturan Profil</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold text-dark mb-0">Pengaturan Akun & Profil</h2>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small border-0 rounded-3 mb-4">
            <i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Info Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white mb-4">
                <img src="<?= getUserAvatarUrl($user['avatar']) ?>" alt="<?= e($user['name']) ?>" class="rounded-circle mx-auto mb-3 shadow" style="width: 100px; height: 100px; object-fit: cover;">
                <h5 class="fw-bold text-dark mb-1"><?= e($user['name']) ?></h5>
                <p class="text-muted small mb-2"><?= e($user['email']) ?></p>
                <div>
                    <?= getMembershipBadge($user['membership_slug'] ?? 'free', $user['membership_name'] ?? 'Free') ?>
                </div>

                <div class="border-top mt-4 pt-3 text-start small">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Status Akun:</span>
                        <span class="text-success fw-bold">Aktif</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Maks. Pinjaman:</span>
                        <span><?= $user['max_borrow_count'] ?? 2 ?> Buku</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Durasi Pinjam:</span>
                        <span><?= $user['max_borrow_days'] ?? 3 ?> Hari</span>
                    </div>
                    <?php if (!empty($user['membership_expires_at'])): ?>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Berlaku Sampai:</span>
                            <span class="fw-semibold"><?= formatDate($user['membership_expires_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/membership.php" class="btn btn-warning w-100 py-2 rounded-pill fw-semibold text-dark shadow-sm">
                        <i class="bi bi-gem me-1"></i> Upgrade Membership
                    </a>
                </div>
            </div>
        </div>

        <!-- Edit Form Column -->
        <div class="col-lg-8">
            <!-- Edit Profile Form -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-person-gear text-primary me-2"></i>Informasi Pribadi</h5>

                <form action="<?= BASE_URL ?>/member/profile.php" method="POST" enctype="multipart/form-data">
                    <?= getCsrfField() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control bg-light" value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">Alamat Email (Tidak dapat diubah)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">Nomor Telepon / WhatsApp</label>
                            <input type="tel" name="phone" class="form-control bg-light" value="<?= e($user['phone']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Ganti Foto Profil / Avatar</label>
                        <input type="file" name="avatar" class="form-control bg-light" accept="image/*">
                        <div class="form-text small">Format JPG, PNG, atau WebP. Maksimal 2MB.</div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                        Simpan Perubahan Profil
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-key text-primary me-2"></i>Ganti Kata Sandi</h5>

                <form action="<?= BASE_URL ?>/member/profile.php" method="POST">
                    <?= getCsrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Kata Sandi Saat Ini</label>
                        <input type="password" name="current_password" class="form-control bg-light" placeholder="••••••••" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">Kata Sandi Baru</label>
                            <input type="password" name="new_password" class="form-control bg-light" placeholder="Min. 6 karakter" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">Ulangi Kata Sandi Baru</label>
                            <input type="password" name="confirm_password" class="form-control bg-light" placeholder="Konfirmasi kata sandi baru" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-danger px-4 py-2 rounded-pill fw-semibold">
                        Perbarui Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

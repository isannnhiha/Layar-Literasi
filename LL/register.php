<?php
/**
 * Registration Page
 * Layar Literasi Digital Library
 */
$pageTitle = "Daftar Akun Anggota Baru";
require_once __DIR__ . '/config/app.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nama lengkap, email, dan kata sandi wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus terdiri dari 6 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $db = getDB();

        // Check if email already registered
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error = 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $stmtInsert = $db->prepare("
                INSERT INTO users (name, email, phone, password, role, avatar, membership_id, membership_expires_at, status, created_at)
                VALUES (?, ?, ?, ?, 'member', 'default-avatar.png', 1, DATE_ADD(NOW(), INTERVAL 1 YEAR), 'active', NOW())
            ");
            $stmtInsert->execute([$name, $email, $phone, $hashedPassword]);
            $newUserId = $db->lastInsertId();

            // Auto-login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_role'] = 'member';
            $_SESSION['user_name'] = $name;

            setFlash('success', "Pendaftaran berhasil! Selamat datang di Layar Literasi, $name.");
            redirect('member/index.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Daftar Akun Baru</h3>
                        <p class="text-muted small">Bergabunglah dan nikmati ribuan buku digital secara gratis</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2.5 px-3 small border-0 rounded-3 mb-4">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/register.php" method="POST">
                        <?= getCsrfField() ?>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control bg-light" placeholder="Masukkan nama lengkap Anda" value="<?= e($_POST['name'] ?? '') ?>" required autofocus>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-7">
                                <label class="form-label small fw-semibold text-muted">Alamat Email</label>
                                <input type="email" name="email" class="form-control bg-light" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold text-muted">Nomor WhatsApp / HP</label>
                                <input type="tel" name="phone" class="form-control bg-light" placeholder="08xxxxxxxxxx" value="<?= e($_POST['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-muted">Kata Sandi</label>
                                <input type="password" name="password" class="form-control bg-light" placeholder="Min. 6 karakter" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-muted">Ulangi Kata Sandi</label>
                                <input type="password" name="password_confirm" class="form-control bg-light" placeholder="Konfirmasi sandi" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-orange w-100 py-2.5 rounded-pill fw-semibold shadow-sm mb-3">
                            Buat Akun Sekarang <i class="bi bi-arrow-right ms-1"></i>
                        </button>

                        <div class="text-center text-muted small">
                            Sudah memiliki akun? <a href="<?= BASE_URL ?>/login.php" class="text-primary fw-bold text-decoration-none">Masuk di Sini</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

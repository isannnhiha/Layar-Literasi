<?php
/**
 * Login Page
 * Layar Literasi Digital Library
 */
$pageTitle = "Masuk ke Akun Anda";
require_once __DIR__ . '/config/app.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $u = currentUser();
    if ($u['role'] === 'admin') {
        redirect('admin/index.php');
    } else {
        redirect('member/index.php');
    }
}

$redirect = $_GET['redirect'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Silakan masukkan email dan kata sandi Anda.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin perpustakaan.';
            } else {
                // Session fixation prevention
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                setFlash('success', "Selamat datang kembali, {$user['name']}!");

                if (!empty($redirect) && strpos($redirect, 'http') === false) {
                    redirect($redirect);
                } else {
                    if ($user['role'] === 'admin') {
                        redirect('admin/index.php');
                    } else {
                        redirect('member/index.php');
                    }
                }
            }
        } else {
            $error = 'Email atau kata sandi yang Anda masukkan salah.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="brand-icon-box mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="bi bi-book-half"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Selamat Datang</h3>
                        <p class="text-muted small">Masuk ke akun Layar Literasi Anda</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2.5 px-3 small border-0 rounded-3 mb-4">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/login.php" method="POST">
                        <?= getCsrfField() ?>
                        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Alamat Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" id="login-email" class="form-control bg-light border-start-0" placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-muted mb-0">Kata Sandi</label>
                                <a href="#" class="small text-decoration-none text-muted">Lupa sandi?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" id="login-password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm mb-3">
                            Masuk Sekarang <i class="bi bi-box-arrow-in-right ms-1"></i>
                        </button>

                        <div class="text-center text-muted small mb-4">
                            Belum memiliki akun? <a href="<?= BASE_URL ?>/register.php" class="text-orange fw-bold text-decoration-none">Daftar Gratis</a>
                        </div>
                    </form>

                    <!-- Quick Demo Credentials Box for Testing -->
                    <div class="p-3 bg-light rounded-4 border">
                        <span class="d-block small fw-bold text-dark mb-2"><i class="bi bi-key-fill text-warning me-1"></i> Akun Cepat Pengujian:</span>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill py-1 text-start" onclick="fillDemo('admin@layarliterasi.id', 'admin123')">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> <strong>Admin:</strong> admin@layarliterasi.id / admin123
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 text-start" onclick="fillDemo('member@layarliterasi.id', 'member123')">
                                <i class="bi bi-person me-1 text-success"></i> <strong>Member:</strong> member@layarliterasi.id / member123
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('login-email').value = email;
    document.getElementById('login-password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

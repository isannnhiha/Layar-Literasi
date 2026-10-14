<?php
/**
 * Authentication & Authorization Middleware
 * Perpustakaan Digital "Layar Literasi"
 */

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, m.name AS membership_name, m.slug AS membership_slug, 
               m.max_borrow_count, m.max_borrow_days, m.can_read_premium, m.badge_color
        FROM users u
        LEFT JOIN memberships m ON u.membership_id = m.id
        WHERE u.id = ? AND u.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $res = $stmt->fetch();
    
    if (!$res) {
        // User suspended or deleted
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);
        return null;
    }
    
    $user = $res;
    return $user;
}

function isAdmin(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function isMember(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'member';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Silakan masuk ke akun Anda terlebih dahulu.');
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php?redirect=' . urlencode($currentUri));
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        setFlash('danger', 'Akses ditolak. Halaman ini hanya untuk Administrator.');
        redirect('index.php');
    }
}

function requireMember(): void {
    requireLogin();
    if (isAdmin()) {
        redirect('admin/index.php');
    }
}

function canAccessBook(?array $user, array $book): array {
    $bookTier = strtolower($book['membership_tier'] ?? 'free');
    
    // Free books can be read by anyone (or any logged-in user)
    if ($bookTier === 'free') {
        return ['allowed' => true, 'reason' => 'Buku ini gratis untuk semua pembaca.'];
    }
    
    if (!$user) {
        return ['allowed' => false, 'reason' => 'Silakan login terlebih dahulu untuk mengakses buku ini.', 'need_login' => true];
    }
    
    if ($user['role'] === 'admin') {
        return ['allowed' => true, 'reason' => 'Akses Administrator.'];
    }
    
    $userTier = strtolower($user['membership_slug'] ?? 'free');
    
    if ($bookTier === 'basic') {
        if ($userTier === 'basic' || $userTier === 'premium') {
            return ['allowed' => true, 'reason' => 'Sesuai dengan paket membership Anda.'];
        }
        return ['allowed' => false, 'reason' => 'Buku ini memerlukan paket Basic atau Premium.', 'need_upgrade' => true, 'required_tier' => 'Basic'];
    }
    
    if ($bookTier === 'premium') {
        if ($userTier === 'premium' || !empty($user['can_read_premium'])) {
            return ['allowed' => true, 'reason' => 'Sesuai dengan paket membership Anda.'];
        }
        return ['allowed' => false, 'reason' => 'Buku ini adalah koleksi eksklusif paket Premium.', 'need_upgrade' => true, 'required_tier' => 'Premium'];
    }
    
    return ['allowed' => true, 'reason' => 'OK'];
}

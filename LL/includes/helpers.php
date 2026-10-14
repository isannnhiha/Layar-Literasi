<?php
/**
 * Helper Functions
 * Perpustakaan Digital "Layar Literasi"
 */

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function formatDate(?string $dateStr): string {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $day = date('d', $time);
    $month = $months[(int)date('m', $time)];
    $year = date('Y', $time);
    return "$day $month $year";
}

function formatDateTime(?string $dateTimeStr): string {
    if (!$dateTimeStr) return '-';
    $time = strtotime($dateTimeStr);
    return date('d/m/Y H:i', $time) . ' WIB';
}

function calculateDaysRemaining(string $dueDate): int {
    $today = new DateTime('today');
    $due = new DateTime($dueDate);
    $diff = $today->diff($due);
    $days = (int)$diff->format('%r%a');
    return $days;
}

function calculateFine(string $dueDate, ?string $returnDate = null): float {
    $due = new DateTime($dueDate);
    $actualReturn = $returnDate ? new DateTime($returnDate) : new DateTime('today');
    
    if ($actualReturn > $due) {
        $interval = $due->diff($actualReturn);
        $lateDays = (int)$interval->format('%a');
        return $lateDays * 1000.00; // Rp 1.000 per hari keterlambatan
    }
    return 0.00;
}

function getMembershipBadge(string $slug, string $name): string {
    $classes = [
        'free' => 'bg-secondary text-white',
        'basic' => 'bg-info text-dark',
        'premium' => 'bg-warning text-dark'
    ];
    $cls = $classes[strtolower($slug)] ?? 'bg-primary text-white';
    return '<span class="badge ' . $cls . ' px-2.5 py-1 rounded-pill"><i class="bi bi-star-fill me-1"></i>' . e($name) . '</span>';
}

function getTierBadge(string $tier): string {
    $t = strtolower($tier);
    switch ($t) {
        case 'premium':
            return '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-gem me-1"></i>Premium</span>';
        case 'basic':
            return '<span class="badge bg-info text-dark px-2 py-1"><i class="bi bi-shield-check me-1"></i>Basic</span>';
        default:
            return '<span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-unlock me-1"></i>Free</span>';
    }
}

function getStatusBadge(string $status): string {
    switch ($status) {
        case 'borrowed':
            return '<span class="badge bg-primary text-white"><i class="bi bi-hourglass-split me-1"></i>Dipinjam</span>';
        case 'returned':
            return '<span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i>Dikembalikan</span>';
        case 'overdue':
            return '<span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle me-1"></i>Terlambat</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Menunggu</span>';
        case 'success':
            return '<span class="badge bg-success text-white"><i class="bi bi-check2-circle me-1"></i>Berhasil</span>';
        case 'failed':
            return '<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i>Gagal</span>';
        case 'active':
            return '<span class="badge bg-success text-white">Aktif</span>';
        case 'suspended':
            return '<span class="badge bg-secondary text-white">Nonaktif</span>';
        default:
            return '<span class="badge bg-secondary text-white">' . e($status) . '</span>';
    }
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: $path");
    } else {
        header("Location: " . BASE_URL . '/' . ltrim($path, '/'));
    }
    exit;
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function getBookCoverUrl(?string $coverName): string {
    if (!$coverName) {
        return ASSETS_URL . '/images/default-book.png';
    }
    // Check if uploaded
    if (file_exists(UPLOADS_PATH . '/covers/' . $coverName)) {
        return UPLOADS_URL . '/covers/' . $coverName;
    }
    // Check in assets
    if (file_exists(ROOT_PATH . '/assets/images/' . $coverName)) {
        return ASSETS_URL . '/images/' . $coverName;
    }
    return ASSETS_URL . '/images/default-book.png';
}

function getUserAvatarUrl(?string $avatarName): string {
    if (!$avatarName) {
        return ASSETS_URL . '/images/default-avatar.png';
    }
    if (file_exists(UPLOADS_PATH . '/avatars/' . $avatarName)) {
        return UPLOADS_URL . '/avatars/' . $avatarName;
    }
    if (file_exists(ROOT_PATH . '/assets/images/' . $avatarName)) {
        return ASSETS_URL . '/images/' . $avatarName;
    }
    return ASSETS_URL . '/images/default-avatar.png';
}

function isBookFavorited(int $userId, int $bookId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->execute([$userId, $bookId]);
    return (bool)$stmt->fetch();
}

function getUserReadingProgress(int $userId, int $bookId): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM reading_progress WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->execute([$userId, $bookId]);
    $res = $stmt->fetch();
    return $res ?: null;
}

function getUserActiveBorrowingsCount(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND status IN ('borrowed', 'overdue')");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

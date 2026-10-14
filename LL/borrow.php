<?php
/**
 * Handle Book Borrowing Action
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/config/app.php';

requireLogin();
verifyCsrfOrDie();

$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
if ($bookId <= 0) {
    setFlash('danger', 'Data buku tidak valid.');
    redirect('catalog.php');
}

$db = getDB();
$user = currentUser();
$userId = (int)$user['id'];

// Check if user already borrowed this book and hasn't returned it
$stmtCheck = $db->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue') LIMIT 1");
$stmtCheck->execute([$userId, $bookId]);
if ($stmtCheck->fetch()) {
    setFlash('warning', 'Anda sudah meminjam buku ini dan belum mengembalikannya.');
    redirect('book-detail.php?id=' . $bookId);
}

// Check active borrow count against membership limit
$activeBorrowCount = getUserActiveBorrowingsCount($userId);
$maxBorrowCount = $user['max_borrow_count'] ?? 2;

if ($activeBorrowCount >= $maxBorrowCount) {
    setFlash('danger', "Batas peminjaman Anda sudah mencapai batas maksimal ($maxBorrowCount buku). Silakan kembalikan buku lain atau upgrade membership Anda.");
    redirect('book-detail.php?id=' . $bookId);
}

// Fetch book and check stock
$stmtBook = $db->prepare("SELECT * FROM books WHERE id = ? FOR UPDATE");
try {
    $db->beginTransaction();

    $stmtBook->execute([$bookId]);
    $book = $stmtBook->fetch();

    if (!$book || $book['stock'] <= 0) {
        $db->rollBack();
        setFlash('danger', 'Maaf, stok buku ini sedang habis.');
        redirect('book-detail.php?id=' . $bookId);
    }

    // Access tier check
    $access = canAccessBook($user, $book);
    if (!$access['allowed']) {
        $db->rollBack();
        setFlash('warning', $access['reason']);
        redirect('book-detail.php?id=' . $bookId);
    }

    // Calculate dates
    $durationDays = (int)($user['max_borrow_days'] ?? 3);
    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime("+$durationDays days"));
    $borrowCode = 'PJ-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    // Decrement stock
    $stmtDec = $db->prepare("UPDATE books SET stock = stock - 1 WHERE id = ?");
    $stmtDec->execute([$bookId]);

    // Insert borrowing record
    $stmtInsert = $db->prepare("
        INSERT INTO borrowings (user_id, book_id, borrow_code, borrow_date, due_date, status, fine_amount, created_at)
        VALUES (?, ?, ?, ?, ?, 'borrowed', 0.00, NOW())
    ");
    $stmtInsert->execute([$userId, $bookId, $borrowCode, $borrowDate, $dueDate]);

    $db->commit();

    setFlash('success', "Peminjaman buku '{$book['title']}' berhasil! Kode Peminjaman: $borrowCode. Jatuh tempo: " . formatDate($dueDate));
    redirect('member/borrowings.php');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Terjadi kesalahan sistem saat memproses peminjaman.');
    redirect('book-detail.php?id=' . $bookId);
}

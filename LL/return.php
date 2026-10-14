<?php
/**
 * Handle Book Return Action
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/config/app.php';

requireLogin();
verifyCsrfOrDie();

$borrowingId = isset($_POST['borrowing_id']) ? (int)$_POST['borrowing_id'] : 0;
if ($borrowingId <= 0) {
    setFlash('danger', 'Data peminjaman tidak valid.');
    redirect('index.php');
}

$db = getDB();
$user = currentUser();
$userId = (int)$user['id'];
$isAdmin = isAdmin();

// Query borrowing
$query = "SELECT * FROM borrowings WHERE id = ?";
$params = [$borrowingId];

if (!$isAdmin) {
    $query .= " AND user_id = ?";
    $params[] = $userId;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$borrowing = $stmt->fetch();

if (!$borrowing || $borrowing['status'] === 'returned') {
    setFlash('warning', 'Peminjaman tidak ditemukan atau buku sudah dikembalikan sebelumnya.');
    redirect($isAdmin ? 'admin/borrowings.php' : 'member/borrowings.php');
}

$returnDate = date('Y-m-d');
$fineAmount = calculateFine($borrowing['due_date'], $returnDate);

try {
    $db->beginTransaction();

    // Increment stock
    $stmtInc = $db->prepare("UPDATE books SET stock = stock + 1 WHERE id = ?");
    $stmtInc->execute([$borrowing['book_id']]);

    // Update borrowing status
    $stmtUpd = $db->prepare("
        UPDATE borrowings 
        SET return_date = ?, status = 'returned', fine_amount = ? 
        WHERE id = ?
    ");
    $stmtUpd->execute([$returnDate, $fineAmount, $borrowingId]);

    $db->commit();

    $fineMsg = ($fineAmount > 0) ? " Terdapat denda keterlambatan sebesar " . formatRupiah($fineAmount) . "." : " Tepat waktu tanpa denda.";
    setFlash('success', "Buku berhasil dikembalikan!$fineMsg");

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', 'Gagal memproses pengembalian buku: ' . $e->getMessage());
}

redirect($isAdmin ? 'admin/borrowings.php' : 'member/borrowings.php');

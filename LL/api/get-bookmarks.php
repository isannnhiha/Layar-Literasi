<?php
/**
 * API: Get Bookmarks for current user and book
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'bookmarks' => []]);
    exit;
}

$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
if ($bookId <= 0) {
    echo json_encode(['success' => false, 'bookmarks' => []]);
    exit;
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("SELECT id, page_number, note, created_at FROM bookmarks WHERE user_id = ? AND book_id = ? ORDER BY page_number ASC");
$stmt->execute([$userId, $bookId]);
$bookmarks = $stmt->fetchAll();

echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);

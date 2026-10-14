<?php
/**
 * API: Add Bookmark
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
$pageNumber = isset($input['page_number']) ? max(1, (int)$input['page_number']) : 1;
$note = trim($input['note'] ?? '');

if (empty($note)) {
    $note = "Penanda Halaman " . $pageNumber;
}

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("INSERT INTO bookmarks (user_id, book_id, page_number, note, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, $bookId, $pageNumber, $note]);
$bookmarkId = $db->lastInsertId();

echo json_encode([
    'success' => true,
    'id' => $bookmarkId,
    'page_number' => $pageNumber,
    'note' => $note
]);

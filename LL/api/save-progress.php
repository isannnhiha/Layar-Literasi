<?php
/**
 * API: Save Reading Progress
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
$currentPage = isset($input['current_page']) ? max(1, (int)$input['current_page']) : 1;
$totalPages = isset($input['total_pages']) ? max(1, (int)$input['total_pages']) : 1;
$percentage = min(100.0, round(($currentPage / $totalPages) * 100, 2));

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// UPSERT reading progress
$sql = "INSERT INTO reading_progress (user_id, book_id, current_page, total_pages, percentage, last_read_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            current_page = VALUES(current_page),
            total_pages = VALUES(total_pages),
            percentage = VALUES(percentage),
            last_read_at = NOW()";

$stmt = $db->prepare($sql);
$stmt->execute([$userId, $bookId, $currentPage, $totalPages, $percentage]);

echo json_encode([
    'success' => true,
    'current_page' => $currentPage,
    'total_pages' => $totalPages,
    'percentage' => $percentage
]);

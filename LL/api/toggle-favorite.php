<?php
/**
 * API: Toggle Book Favorite
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'need_login' => true, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookId = isset($input['book_id']) ? (int)$input['book_id'] : 0;
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

if (!validateCsrfToken($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit;
}

if ($bookId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Check if already favorited
$stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND book_id = ? LIMIT 1");
$stmt->execute([$userId, $bookId]);
$fav = $stmt->fetch();

if ($fav) {
    $del = $db->prepare("DELETE FROM favorites WHERE id = ?");
    $del->execute([$fav['id']]);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    $ins = $db->prepare("INSERT INTO favorites (user_id, book_id, created_at) VALUES (?, ?, NOW())");
    $ins->execute([$userId, $bookId]);
    echo json_encode(['success' => true, 'action' => 'added']);
}

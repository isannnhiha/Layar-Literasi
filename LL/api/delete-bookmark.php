<?php
/**
 * API: Delete Bookmark
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/app.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bookmarkId = isset($input['bookmark_id']) ? (int)$input['bookmark_id'] : 0;

if ($bookmarkId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bookmark ID']);
    exit;
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("DELETE FROM bookmarks WHERE id = ? AND user_id = ?");
$stmt->execute([$bookmarkId, $userId]);

echo json_encode(['success' => true]);

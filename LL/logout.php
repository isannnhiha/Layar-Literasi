<?php
/**
 * Logout Handler
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/config/app.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start new session just for the flash message
session_start();
$_SESSION['flash'] = [
    'type' => 'info',
    'message' => 'Anda telah berhasil keluar dari sistem.'
];

header("Location: " . BASE_URL . "/login.php");
exit;

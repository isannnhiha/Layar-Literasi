<?php
/**
 * Global Header Component
 * Layar Literasi Digital Library
 */
require_once __DIR__ . '/../config/app.php';
$currentUser = currentUser();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Layar Literasi - Platform perpustakaan digital modern dengan ribuan koleksi buku, baca online PDF, dan manajemen peminjaman interaktif.">
    <meta name="csrf-token" content="<?= e(generateCsrfToken()) ?>">
    <meta name="base-url" content="<?= e(BASE_URL) ?>">
    
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Custom Style matching Figma UI/UX -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<?php require_once __DIR__ . '/alerts.php'; ?>
<main>

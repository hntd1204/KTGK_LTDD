<?php
/**
 * Global Header View
 */
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../helpers/auth.php';
Auth::requireLogin();
$currentUser = Auth::user();
$pageTitle = $pageTitle ?? 'Quản Trị TOEIC Reading';
$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | TOEIC Reading Admin</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Top Navbar -->
        <header class="top-bar">
            <div class="top-title">
                <span><?= htmlspecialchars($pageTitle) ?></span>
            </div>
            <div class="top-actions">
                <a href="import.php" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Import Đề Thi
                </a>
                <a href="logout.php" class="btn btn-outline btn-sm text-danger" title="Đăng xuất" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            </div>
        </header>

        <!-- Main View Body -->
        <main class="content-body">

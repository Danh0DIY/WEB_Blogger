<?php
require_once __DIR__ . '/auth.php';
startSession();
$currentUser = currentUser();
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDesc = $pageDesc ?? SITE_DESCRIPTION;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= SITE_URL ?>/" class="logo">
                <span class="logo-icon">⚡</span>
                <span class="logo-text"><?= e(SITE_NAME) ?></span>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <nav class="main-nav" id="mainNav">
                <a href="<?= SITE_URL ?>/">Trang chủ</a>
                <a href="<?= SITE_URL ?>/category.php?slug=esp32-iot">ESP32 & IoT</a>
                <a href="<?= SITE_URL ?>/category.php?slug=dien-tu">Điện tử</a>
                <a href="<?= SITE_URL ?>/category.php?slug=diy-che-tao">DIY</a>
                <?php if ($currentUser): ?>
                    <a href="<?= SITE_URL ?>/admin/" class="btn-admin">Quản trị</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/admin/login.php" class="btn-login">Đăng nhập</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="site-main">

<?php
// header.php - Shared Header Component
// Works for both root pages and admin/ subdirectory pages

if (!isset($conn)) {
    $cfg = __DIR__ . '/config.php';
    if (!file_exists($cfg)) $cfg = __DIR__ . '/../config.php';
    require_once $cfg;
}

$current_user = isLoggedIn() ? getCurrentUser($conn) : null;
$page_title = isset($page_title) ? $page_title : 'Aromea';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — Aromea</title>
    <script src="https://kit.fontawesome.com/0b3d769464.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/style.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/images/logo-image.png">
</head>
<body>
    <header>
        <a class="logo" href="<?php echo BASE_URL; ?>/index.php"><h1>Aromea</h1></a>
        <?php if (isset($admin_page)): ?>
        <button class="admin-menu-btn" onclick="openAdminSidebar()" aria-label="Admin menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <?php endif; ?>
        <button class="hamburger-btn" id="hamburger-btn" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <nav class="nav-links" id="nav-links">
            <a href="<?php echo BASE_URL; ?>/index.php">Home</a>
            <a href="<?php echo BASE_URL; ?>/collection.php">Collection</a>
            <a href="<?php echo BASE_URL; ?>/explore.php">Explore</a>
            <a href="<?php echo BASE_URL; ?>/about.php">About</a>

            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/user-account.php">
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($current_user['fullname'] ?? 'Account'); ?>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" style="color:#b0763a;">
                        <i class="fa-solid fa-gauge"></i> Admin
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/login.php">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </header>

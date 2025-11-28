<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = getUserId();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="header-banner">
        <div class="header-container">
            <a href="Posts.php">
                <img src="assets/images/schoolconnectlogo.png" alt="School Connect logo" class="header-logo">
            </a>
            <nav class="site-nav">
                <ul class="nav-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="user-greeting">
                            <a href="userProfile.php" style="text-decoration:none;color:inherit;">
                                <img src="<?= htmlspecialchars(getUserProfilePic($userId) ?: 'assets/images/default-avatar.png') ?>"
                                    class="avatar-xs">
                                <span><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="content-container">
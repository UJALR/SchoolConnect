<?php
if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}
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
            <a href="index.php">
                <img src="assets/images/schoolconnectlogo.png"
                    alt="School Connect logo"
                    class="header-logo">
            </a>
            <nav class="site-nav">
                <button class="nav-toggle" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <ul class="nav-links">
                    <li><a href="index.php"><i class="fas fa-home"></i>&nbsp;Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="MyFriends.php"><i class="fas fa-user-friends"></i>&nbsp;My Friends</a></li>
                        <li><a href="MyAlbums.php"><i class="fas fa-images"></i>&nbsp;My Albums</a></li>
                        <li><a href="MyPictures.php"><i class="fas fa-photo-video"></i>&nbsp;My Pictures</a></li>
                        <li><a href="UploadPictures.php"><i class="fas fa-cloud-upload-alt"></i>&nbsp;Upload Pictures</a></li>
                        <li class="user-greeting">
                            <span><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                            <a href="Logout.php"><i class="fas fa-sign-out-alt"></i></a>
                        </li>
                    <?php else: ?>
                        <li><a href="Login.php"><i class="fas fa-sign-in-alt"></i> Log In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="content-container">
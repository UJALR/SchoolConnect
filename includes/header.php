<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algonquin Social Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- White Header Banner -->
    <header class="header-banner">
        <div class="header-container">
            <a href="index.php">
                <img src="assets/images/83-835011_algonquin-college-logo-png.webp" 
                     alt="Algonquin College" 
                     class="header-logo" >
            </a>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="MyFriends.php"><i class="fas fa-user-friends"></i> My Friends</a></li>
                        <li><a href="MyAlbums.php"><i class="fas fa-images"></i> My Albums</a></li>
                        <li><a href="MyPictures.php"><i class="fas fa-photo-video"></i> My Pictures</a></li>
                        <li><a href="UploadPictures.php"><i class="fas fa-cloud-upload-alt"></i> Upload Pictures</a></li>
                        <li class="user-greeting">
                            <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            <a href="Logout.php"><i class="fas fa-sign-out-alt"></i></a>
                        </li>
                    <?php else: ?>
                        <li><a href="Login.php"><i class="fas fa-sign-in-alt"></i> Log In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Main Content Area -->
    <main class="main-content">
        <div class="content-container">
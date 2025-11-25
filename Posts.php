<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

protectPage();
$userId = getUserId();

// Handle post submission from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $postText = trim($_POST['post_text']);
    $mediaUrl = null;

    // Handle image upload
    if (!empty($_FILES['media_file']['name'])) {
        $uploadDir = "uploads/posts/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmp = $_FILES['media_file']['tmp_name'];
        $fileName = time() . "_" . basename($_FILES['media_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $mediaUrl = $targetPath;
        }
    }

    createPost($userId, $postText, $mediaUrl);

    header("Location: Posts.php"); // reload & show newest post
    exit;
}

$posts = getAllPosts(); // newest → oldest
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/feed.css">

<div class="feed-wrapper">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar-left">
        <input type="text" class="search-box" placeholder="Search">
        <nav class="sidebar-links">
            <a href="userProfile.php">My Profile</a>
            <a href="MyFriends.php">Friends</a>
            <a href="#">Groups</a>
            <a href="Logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN FEED -->
    <div class="feed-main">

        <!-- POST INPUT BAR -->
        <div class="create-post-bar">
            <img src="assets/images/default-avatar.png" class="avatar-sm">
            <input id="mainPostInput" type="text" placeholder="What's happening today?" class="post-input">
            <button id="openPostModal" class="post-btn">POST</button>
        </div>

        <!-- FEED POSTS -->
        <?php foreach ($posts as $post): ?>
        <div class="post-card">
            <div class="post-header">
                <img src="<?= htmlspecialchars(getUserProfilePic($post['user_id'])) ?>" class="avatar-sm">
                <div>
                    <strong><?= htmlspecialchars(getUserName($post['user_id'])) ?></strong><br>
                    <span class="post-date"><?= htmlspecialchars($post['created_at']) ?></span>
                </div>
            </div>

            <p class="post-text"><?= nl2br(htmlspecialchars($post['post_text'])) ?></p>

            <?php if ($post['media_url']): ?>
                <img src="<?= htmlspecialchars($post['media_url']) ?>" class="post-image">
            <?php endif; ?>

            <div class="post-actions">
                <a href="#">Comment</a>
                <a href="#">Share</a>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- RIGHT SIDEBAR -->
    <aside class="sidebar-right">
        <div class="section-title">Potential Buddies</div>
        <?php foreach (getSuggestedFriends($userId) as $fr): ?>
            <div class="buddy-item">
                <img src="<?= htmlspecialchars($fr['profile_picture']) ?>" class="avatar-xs">
                <span><?= htmlspecialchars($fr['full_name']) ?></span>
                
            </div>
        
        <?php endforeach; ?>
<a href="viewFriends.php" class="view-all-link">View All </a>

        <div class="section-title">Join a Community</div>
        <a class="community-item" href="#">Code & Coffee</a>
        <a class="community-item" href="#">Green Campus</a>
        <a class="community-item" href="#">Study Sprint</a>
    </aside>

</div>


<!-- MODAL FOR CREATING POST -->
<div id="modalBackdrop" class="modal-backdrop"></div>

<div id="postModal" class="modal">
    <div class="modal-content">
        <span id="modalClose">&times;</span>

        <h3>Create Post</h3>

        <form action="Posts.php" method="POST" enctype="multipart/form-data">
            <textarea id="modalPostText" name="post_text" class="modal-textarea" placeholder="Write something..."></textarea>

            <label class="upload-label">
                <i class="fa fa-image"></i> Upload Image
                <input type="file" name="media_file" accept="image/*">
            </label>

            <button type="submit" name="create_post" class="submit-post-btn">Post</button>
        </form>
    </div>
</div>


<script src="assets/js/postModal.js"></script>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE)
{
    session_start();
}

protectPage();
$userId = getUserId();

// --- POST SUBMISSION HANDLER (from modal) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post']))
{
    $postText = trim($_POST['post_text']);
    $mediaUrl = null;

    // Handle image upload
    if (!empty($_FILES['media_file']['name']))
    {
        $uploadDir = "uploads/posts/";
        if (!is_dir($uploadDir))
        {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmp = $_FILES['media_file']['tmp_name'];
        $fileName = time() . "_" . basename($_FILES['media_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $targetPath))
        {
            $mediaUrl = $targetPath;
        }
    }

    createPost($userId, $postText, $mediaUrl);

    header("Location: Posts.php"); // reload & show newest post
    exit;
}

// --- COMMENT SUBMISSION HANDLER (from post card) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']))
{
    $postId = (int)$_POST['post_id'];
    $commentText = trim($_POST['comment_text']);

    if (!empty($commentText) && $postId > 0)
    {
        createComment($postId, $userId, $commentText);
    }

    // Redirect to clear POST data and show the new comment, targeting the post element
    header("Location: Posts.php#post-" . $postId);
    exit;
}

// --- DATA FETCHING ---
// Assumes getAllPosts now accepts userId to fetch relevant posts (general + user groups)
$posts = getAllPosts($userId);
$suggestedGroups = getSuggestedGroups(3);
$pendingCount = getPendingFriendRequestCount($userId);
?>

<?php include 'includes/header.php'; ?>

<!-- <link rel="stylesheet" href="assets/css/feed.css"> -->

<div class="feed-wrapper">

    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <div class="create-post-bar">
            <img src="assets/images/default-avatar.png" class="avatar-sm">
            <input id="mainPostInput" type="text" placeholder="What's happening today?" class="post-input">
            <button id="openPostModal" class="post-btn">POST</button>
        </div>

        <?php foreach ($posts as $post): ?>
            <div class="post-card" id="post-<?= $post['post_id'] ?>">
                <div class="post-header">
                    <img src="<?= htmlspecialchars(getUserProfilePic($post['user_id'])) ?>" class="avatar-sm">
                    <div>
                        <a href="friendProfile.php?user_id=<?= $post['user_id'] ?>">
                            <?= htmlspecialchars(getUserName($post['user_id'])) ?>
                        </a>
                        <?php if (!empty($post['group_name'])): ?>
                            <span class="group-tag">
                                • Posted to <a href="groups_detail.php?group_id=<?= $post['group_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($post['group_name']) ?></a>
                            </span>
                        <?php endif; ?>
                        <span class="post-date"><?= date("M j, Y H:i", strtotime($post['created_at'])) ?></span>
                    </div>
                </div>

                <p class="post-text"><?= nl2br(htmlspecialchars($post['post_text'])) ?></p>

                <?php if ($post['media_url']): ?>
                    <img src="<?= htmlspecialchars($post['media_url']) ?>" class="post-image">
                <?php endif; ?>

                <div class="post-actions">
                    <a href="#comment-form-<?= $post['post_id'] ?>" onclick="document.getElementById('comment-input-<?= $post['post_id'] ?>').focus(); return false;">Comment</a>
                    <a href="#">Share</a>
                </div>

                <div class="comment-section">

                    <form action="Posts.php" method="POST" class="comment-form" id="comment-form-<?= $post['post_id'] ?>">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <input
                            type="text"
                            name="comment_text"
                            id="comment-input-<?= $post['post_id'] ?>"
                            class="comment-input-field"
                            placeholder="Add a comment..."
                            required>
                        <button type="submit" name="submit_comment" class="comment-btn">
                            <i class="fa fa-comment"></i>
                        </button>
                    </form>

                    <div class="comments-list">
                        <?php
                        // You must ensure this function exists in includes/functions.php
                        $comments = getCommentsForPost($post['post_id']);
                        foreach ($comments as $comment):
                            $commentAvatar = $comment['profile_picture'] ?: 'assets/images/default-avatar.png';
                        ?>
                            <div class="comment-item">
                                <img src="<?= htmlspecialchars($commentAvatar) ?>" class="avatar-xs">
                                <div class="comment-content">
                                    <span class="comment-author">
                                        <a href="friendProfile.php?user_id=<?= $comment['user_id'] ?>">
                                            <?= htmlspecialchars($comment['full_name']) ?>
                                        </a>
                                    </span>
                                    <span class="comment-text-content"><?= htmlspecialchars($comment['comment_text']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <?php include 'includes/right_panel_partial.php'; ?>

</div>


<!-- <div id="modalBackdrop" class="modal-backdrop fixed inset-0 bg-[rgba(0,0,0,0.45)] opacity-0 hidden pointer-events-auto transition-opacity duration-[350ms] ease-in-out"></div>

<div id="postModal" class="modal fixed inset-0 flex justify-center items-start top-[-1000px] opacity-0 hidden pointer-events-auto transition-all duration-[350ms] ease-in-out z-[2000]">
    <div class="modal-content w-full max-w-[640px] bg-white rounded-[12px] px-[22px] py-[20px] shadow-mdf relative">
        <span id="modalClose" class="absolute right-[20px] top-[10px] text-[1.5em] text-pine-green hover:text-pistachio cursor-pointer">&times;</span>

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
</div> -->

<div id="modalBackdrop" class="modal-backdrop "></div>

<div id="postModal" class="modal">
    <div class="modal-content">
        <span id="modalClose" class="absolute right-[20px] top-[10px] text-[1.5em] text-pine-green hover:text-pistachio cursor-pointer">&times;</span>

        <h3>Create Post</h3>

        <form action="Posts.php" method="POST" enctype="multipart/form-data">
            <textarea id="modalPostText" name="post_text" class="modal-textarea" rows="4" placeholder="Write something..."></textarea>

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
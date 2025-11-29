<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

protectPage();
$userId = getUserId();

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("User not found.");
}

$friendId = (int) $_GET['user_id'];

if ($friendId === $userId) {
    header("Location: userProfile.php");
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT
        full_name,
        username,
        college_email,
        bio,
        profile_picture,
        created_at
    FROM Users
    WHERE user_id = ?
");

$stmt->execute([$friendId]);
$friend = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$friend) {
    die("User not found.");
}

$avatar = $friend['profile_picture'] ?: "assets/images/default-avatar.png";

$friendshipStatus = getFriendshipStatus($userId, $friendId);
$friendInterests = getInterestsForUser($friendId);
$friendLanguages = getLanguagesForUser($friendId);
$posts = getPostsByUserId($friendId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_action'])) {
    $action = $_POST['friend_action'];
    $otherId = $friendId; // Friend's ID

    // Friend action processing logic (identical to previous version)
    if ($action === 'add') {
        sendFriendRequest($userId, $otherId);
    } elseif ($action === 'cancel') {
        // Check who sent the request before canceling
        $senderId = ($friendshipStatus === 'pending_sent') ? $userId : $otherId;
        $receiverId = ($friendshipStatus === 'pending_sent') ? $otherId : $userId;
        cancelFriendRequest($senderId, $receiverId);

    } elseif ($action === 'accept') {
        acceptFriendRequest($otherId, $userId);

    } elseif ($action === 'unfriend') {
        unfriendUser($userId, $otherId);
    }

    header("Location: friendProfile.php?user_id=" . $friendId);
    exit;
}

include __DIR__ . "/includes/header.php";
?>

<!-- <link rel="stylesheet" href="assets/css/feed.css">

<style>
    /* --- Profile Card Base Styles (Reused from userProfile.php) --- */
    .feed-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .profile-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }

    /* Banner: Removed default gradient in CSS to rely ONLY on inline style from PHP */
    .profile-banner {
        position: relative;
        width: 100%;
        height: 260px;
        border-radius: 18px 18px 0 0;
        overflow: hidden;
        /* Important: No background property here, it is set via PHP inline style */
    }

    /* Header, Avatar, Info Styles (kept for visual consistency) */
    .profile-header {
        position: relative;
        margin-top: -70px;
        text-align: center;
    }

    .profile-avatar-wrap {
        position: relative;
        width: 170px;
        margin: 0 auto;
    }

    .profile-avatar-lg {
        width: 170px;
        height: 170px;
        border-radius: 50%;
        object-fit: cover;
        border: 6px solid #fff;
        background: #e5e7eb;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .profile-avatar-edit {
        display: none;
        /* Hide camera icon for friend profile */
    }

    .profile-info-block h2 {
        margin: 12px 0 0;
        font-size: 2rem;
        font-weight: 800;
        color: #111;
        text-align: center;
    }

    .profile-info-block .username {
        margin-top: 4px;
        font-size: 1rem;
        color: #6b7280;
    }

    .profile-info-block .joined {
        margin-top: 8px;
        font-size: 0.9rem;
        color: #9ca3af;
    }

    /* Friend action button styles */
    .friend-action-btn {
        margin-top: 12px;
        padding: 10px 18px;
        border-radius: 999px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 20px;
    }

    .btn-add {
        background: #22c55e;
        color: #fff;
    }

    .btn-cancel,
    .btn-unfriend {
        background: #ef4444;
        color: #fff;
    }

    .btn-accept {
        background: #3b82f6;
        color: #fff;
        margin-right: 10px;
    }

    /* Body, Sections, and Social Card Styles (kept for visual consistency) */
    .profile-body {
        padding: 18px 24px;
    }

    .profile-section {
        margin-bottom: 14px;
    }

    .profile-section h3 {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #666;
        margin: 0 0 6px;
    }

    .profile-section p {
        margin: 2px 0;
        font-size: 0.95rem;
        color: #333;
    }

    .profile-divider {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 16px 0;
    }

    .profile-social-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        padding: 18px 22px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .social-row {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 8px;
        margin-bottom: 10px;
    }

    .social-label {
        text-align: right;
        font-weight: 600;
        color: #555;
    }

    .chip {
        background: #bbf7d0;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.85rem;
        margin: 2px 4px;
        color: #166534;
        display: inline-block;
    }

    .chip.language {
        background: #d1fae5;
        color: #047857;
    }

    .social-icons-row {
        display: flex;
        gap: 12px;
    }

    .social-icons-row a {
        font-size: 1.4rem;
        text-decoration: none;
    }

    .social-icons-row a.instagram {
        background: radial-gradient(circle at 30% 30%, #fdf497 0, #fd5949 40%, #d6249f 70%, #285AEB 100%);
        -webkit-background-clip: text;
        color: transparent;
    }

    .social-icons-row a.tiktok {
        color: black;
    }
</style> -->

<div class="feed-wrapper">

    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">

        <div class="profile-card">
            <div class="profile-header">

                <div class="profile-avatar-wrap">
                    <img src="<?= htmlspecialchars($avatar) ?>" class="profile-avatar-lg">
                    <div class="profile-avatar-edit"></div>
                </div>

                <div class="profile-info-block">
                    <h2><?= htmlspecialchars($friend['full_name']) ?></h2>
                    <div class="username">@<?= htmlspecialchars($friend['username']) ?></div>
                    <div class="joined">
                        Joined <?= date("F j, Y", strtotime($friend['created_at'])) ?>
                    </div>
                </div>

                <form method="POST" class="friend-actions">
                    <input type="hidden" name="friend_id" value="<?= $friendId ?>">

                    <?php if ($friendshipStatus === 'not_friends' || $friendshipStatus === ''): ?>
                        <button type="submit" name="friend_action" value="add" class="friend-action-btn btn-add">
                            <i class="fa fa-user-plus"></i> Add Friend
                        </button>

                    <?php elseif ($friendshipStatus === 'pending_sent'): ?>
                        <button type="submit" name="friend_action" value="cancel" class="friend-action-btn btn-cancel">
                            <i class="fa fa-times"></i> Cancel Request
                        </button>

                    <?php elseif ($friendshipStatus === 'pending_received'): ?>
                        <button type="submit" name="friend_action" value="accept" class="friend-action-btn btn-accept">
                            <i class="fa fa-check"></i> Accept Request
                        </button>
                        <button type="submit" name="friend_action" value="cancel" class="friend-action-btn btn-cancel">
                            <i class="fa fa-times"></i> Decline
                        </button>

                    <?php elseif ($friendshipStatus === 'friends'): ?>
                        <button type="submit" name="friend_action" value="unfriend" class="friend-action-btn btn-unfriend">
                            <i class="fa fa-user-times"></i> Unfriend
                        </button>
                    <?php endif; ?>
                </form>

            </div>

            <div class="profile-body">
                <div class="profile-section">
                    <h3>About Me</h3>
                    <p><?= nl2br(htmlspecialchars($friend['bio'] ?: "No bio provided.")) ?></p>
                </div>

                <!-- <hr class="profile-divider"> -->

                <div class="profile-section">
                    <h3>Contact Info</h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($friend['college_email']) ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($friend['username']) ?></p>
                </div>
            </div>
        </div>

        <div class="profile-social-card">
            <h3>Social & Community</h3>

<div class="social-row">
                <div class="social-label">Interests:</div>
                <div class="social-value">
                    <?php if (empty($friendInterests)): ?>
                        <span style="color:#999;font-style:italic;">None listed.</span>
                    <?php else: ?>
                        <?php foreach ($friendInterests as $interest): ?>
                            <span class="chip"><?= htmlspecialchars($interest) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="social-row">
                <div class="social-label">Languages:</div>
                <div class="social-value">
                    <?php if (empty($friendLanguages)): ?>
                        <span style="color:#999;font-style:italic;">None listed.</span>
                    <?php else: ?>
                        <?php foreach ($friendLanguages as $language): ?>
                            <span class="chip language"><?= htmlspecialchars($language) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="social-row">
                <div class="social-label">Social Links:</div>
                <div class="social-value">
                    <div class="social-icons-row">
                        <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="tiktok"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="posts-list">
            <h3><?= htmlspecialchars($friend['full_name']) ?>'s Posts</h3>

            <?php if (empty($posts)): ?>
                <div class="no-content-message">
                    This user hasn't created any posts yet.
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post):
                    // In a real application, you might use a separate file (e.g., post_template.php) 
                    // to render a single post, but for simplicity, we'll embed the loop here.
            
                    // Re-fetch the avatar/name for the post, even though we already have it (optional, but good practice if you separate code)
                    $postUserAvatar = $avatar; // Use the already fetched avatar
                    $postUserName = $friend['full_name']; // Use the already fetched name
                    ?>
                    <div class="post-card">
                        <div class="post-header">
                            <img src="<?= htmlspecialchars($postUserAvatar) ?>" class="avatar-sm">
                            <div class="post-info">
                                <span class="post-author"><?= htmlspecialchars($postUserName) ?></span>
                                <span class="post-timestamp"><?= date("M j, Y H:i", strtotime($post['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p><?= nl2br(htmlspecialchars($post['post_text'])) ?></p>
                            <?php if ($post['media_url']): ?>
                                <img src="<?= htmlspecialchars($post['media_url']) ?>" class="post-media">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <?php include 'includes/right_panel_partial.php'; ?>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
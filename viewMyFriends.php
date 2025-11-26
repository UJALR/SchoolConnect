<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

protectPage();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['accept_request'])) {
        acceptFriendRequest($_POST['sender_id'], $userId);
    }

    if (isset($_POST['unfriend'])) {
        unfriendUser($userId, $_POST['other_id']);
    }

    header("Location: viewMyFriends.php");
    exit;
}

$stmt = $db->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.profile_picture,
        f.status,
        f.user_id_sender,
        f.user_id_receiver
    FROM userfriends f
    JOIN users u 
      ON (u.user_id = f.user_id_sender OR u.user_id = f.user_id_receiver)
    WHERE (f.user_id_sender = ? OR f.user_id_receiver = ?)
    AND u.user_id != ?
");

$stmt->execute([
    $userId,
    $userId,
    $userId
]);

$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/feed.css">

<style>
.feed-wrapper {
    display: flex;
    background: #0b5f43;
    min-height: 100vh;
}
.feed-main {
    flex: 1;
    padding: 40px;
}
.page-title {
    color: white;
    font-size: 36px;
    margin-bottom: 30px;
}
.friend-card {
    background: #0e6a4b;
    padding: 18px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.friend-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.avatar-xs {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}
.view-btn {
    background: #f59e0b;
    color: #fff;
    padding: 10px 22px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.buddy-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
</style>

<div class="feed-wrapper">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar-left">
        <input type="text" class="search-box" placeholder="Search">
        <nav class="sidebar-links">
            <a href="userProfile.php">My Profile</a>
            <a href="viewMyFriends.php" class="active-link">Friends</a>
            <a href="groups.php">Groups</a>
            <a href="Logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="feed-main">
        <h1 class="page-title">My Friends</h1>

        <?php if (empty($friends)): ?>
    <p style="color:white;">You don't have any friends or requests yet.</p>
<?php else: ?>
    <?php foreach ($friends as $friend): ?>
        <div class="buddyitem" style="display: flex; gap: 40px;padding: 8px 0; align-items: center;">
            
            <a href="friendProfile.php?user_id=<?= $friend['user_id'] ?>" class="buddy-info" 
                style="display: flex; gap: 12px; align-items: center;">
                <img src="<?= htmlspecialchars($friend['profile_picture'] ?: 'assets/images/default-avatar.png') ?>"
                    class="avatar-xs">
                <div>
                    <strong style="color: white;"><?= htmlspecialchars($friend['full_name']) ?></strong><br>
                    <small style="color: #ccc;">@<?= htmlspecialchars($friend['username']) ?></small>
                </div>
            </a>

            <div class="buddy-actions">

                <?php if ($friend['status'] === 'pending' && $friend['user_id_sender'] != $userId): ?>
                    <!-- Incoming request -->
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="sender_id" value="<?= $friend['user_id_sender'] ?>">
                        <button type="submit" name="accept_request" class="add-btn" style="background:#22c55e; padding: 10px 22px; border-radius: 10px; border: none; color: white; cursor: pointer;">
                            Accept
                        </button>
                    </form>

                <?php elseif ($friend['status'] === 'accepted'): ?>
                    <!-- Already friends -->
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="other_id" value="<?= $friend['user_id'] ?>">
                        <button type="submit" name="unfriend" class="add-btn gray-btn" style="background:#6b7280; padding: 10px 22px; border-radius: 10px; border: none; color: white; cursor: pointer;">
                            Unfriend
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
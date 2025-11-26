<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

protectPage();
$userId = getUserId();
$db = Database::getInstance()->getConnection();

/* =============================
   ACTION HANDLING
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_friend'])) {
        sendFriendRequest($userId, intval($_POST['receiver_id']));
    }

    if (isset($_POST['cancel_request'])) {
        cancelFriendRequest($userId, intval($_POST['receiver_id']));
    }

    if (isset($_POST['accept_request'])) {
        acceptFriendRequest(intval($_POST['receiver_id']), $userId);
    }

    if (isset($_POST['unfriend'])) {
        unfriendUser($userId, intval($_POST['receiver_id']));
    }

    header("Location: viewFriends.php");
    exit;
}

/* =============================
   LOAD PEOPLE YOU MAY KNOW
============================= */
$stmt = $db->prepare("
    SELECT user_id, full_name, username, profile_picture
    FROM users
    WHERE user_id != :uid
");
$stmt->execute([':uid' => $userId]);
$people = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
.buddy-item {
    background: #0e6a4b;
    padding: 18px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.buddy-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.avatar-xs {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}
.add-btn {
    background: #9fd56d;
    color: white;
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
}
.gray-btn {
    background: gray;
}
</style>

<div class="feed-wrapper">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar-left">
        <input type="text" class="search-box" placeholder="Search people..." id="friendSearch">
        <nav class="sidebar-links">
            <a href="Posts.php">Home</a>
            <a href="userProfile.php">My Profile</a>
            <a href="viewFriends.php" class="active-link">Friends</a>
            <a href="Logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="feed-main">
        <h1 class="page-title">People You May Know</h1>

        <?php if (empty($people)): ?>
            <p style="color:#e5e7eb;">No users to suggest.</p>
        <?php else: ?>
            <?php foreach ($people as $person): ?>
                <?php $statusData = getFriendshipStatus($userId, $person['user_id']); ?>

                <div class="buddy-item" data-name="<?= strtolower(htmlspecialchars($person['full_name'])) ?>">
                    <div class="buddy-info">
                        <img src="<?= htmlspecialchars($person['profile_picture'] ?: 'assets/images/default-avatar.png') ?>" class="avatar-xs">
                        <div style="color:white;">
                            <div style="font-weight:600;">
                                <?= htmlspecialchars($person['full_name']) ?>
                            </div>
                            <div style="font-size:0.9rem;color:#d1fae5;">
                                @<?= htmlspecialchars($person['username']) ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <?php if (!$statusData): ?>
                            <!-- No relationship -->
                            <form method="POST">
                                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                                <button type="submit" name="add_friend" class="add-btn">Add</button>
                            </form>

                        <?php elseif ($statusData['status'] === 'pending' && $statusData['user_id_sender'] == $userId): ?>
                            <!-- YOU sent the request -->
                            <form method="POST">
                                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                                <button type="submit" name="cancel_request" class="add-btn gray-btn">
                                    Cancel Request
                                </button>
                            </form>

                        <?php elseif ($statusData['status'] === 'pending' && $statusData['user_id_sender'] != $userId): ?>
                            <!-- THEY sent the request -->
                            <form method="POST">
                                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                                <button type="submit" name="accept_request" class="add-btn" style="background:#22c55e;">
                                    Accept
                                </button>
                            </form>

                        <?php elseif ($statusData['status'] === 'accepted'): ?>
                            <!-- Already friends -->
                            <form method="POST">
                                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                                <button type="submit" name="unfriend" class="add-btn gray-btn">
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

<script>
document.getElementById('friendSearch').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let users = document.querySelectorAll('.buddy-item');

    users.forEach(user => {
        let name = user.getAttribute('data-name');
        user.style.display = name.includes(searchValue) ? "flex" : "none";
    });
});
</script>

<?php include 'includes/footer.php'; ?>

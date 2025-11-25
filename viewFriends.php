
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

    if (isset($_POST['unfriend'])) {
        unfriendUser($userId, intval($_POST['receiver_id']));
    }

    header("Location: viewFriends.php");
    exit;
}

/* =============================
   LOAD USERS
============================= */
$stmt = $db->prepare("
    SELECT user_id, full_name, profile_picture 
    FROM users 
    WHERE user_id != :uid
");
$stmt->execute([':uid' => $userId]);
$people = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/feed.css">
<style>.feed-wrapper {
    display: flex;
    background: #0b5f43;
    min-height: 100vh;
}

.feed-main {
    flex: 1;
    padding: 40px;
    overflow-y: auto;
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
}

.avatar-xs {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-right: 15px;
}

.add-btn {
    background: #9fd56d;
    color: white;
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
}

.gray-btn {
    background: gray;
}

.friend-text {
    color: #9fe870;
}

.pending-text {
    color: #ffd966;
}
</style>

<div class="feed-wrapper">

<!-- LEFT SIDEBAR -->
<aside class="sidebar-left">

    <!-- LIVE SEARCH INPUT -->
    <input 
        type="text" 
        class="search-box" 
        placeholder="Search people..." 
        id="friendSearch"
    >

    <nav class="sidebar-links">
        <a href="Posts.php">Home</a>
        <a href="userProfile.php">My Profile</a>
        <a href="viewFriends.php">Friends</a>
        <a href="Logout.php">Logout</a>
    </nav>
</aside>

<!-- MAIN CONTENT -->
<div class="feed-main">
<h1 class="page-title">People You May Know</h1>

<?php foreach ($people as $person): ?>
<?php $statusData = getFriendshipStatus($userId, $person['user_id']); ?>

<div class="buddy-item" data-name="<?= strtolower(htmlspecialchars($person['full_name'])) ?>">
    <div class="buddy-info">
        <img src="<?= htmlspecialchars($person['profile_picture'] ?: 'assets/images/default-avatar.png') ?>" class="avatar-xs">
        <strong><?= htmlspecialchars($person['full_name']) ?></strong>
    </div>

    <div>
        <?php if (!$statusData): ?>
            <form method="POST">
                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                <button type="submit" name="add_friend" class="add-btn">Add</button>
            </form>

        <?php elseif ($statusData['status'] === 'pending' && $statusData['user_id_sender'] == $userId): ?>
            <form method="POST">
                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                <button type="submit" name="cancel_request" class="add-btn gray-btn">
                    Cancel Request
                </button>
            </form>

        <?php elseif ($statusData['status'] === 'accepted'): ?>
            <form method="POST">
                <input type="hidden" name="receiver_id" value="<?= $person['user_id'] ?>">
                <button type="submit" name="unfriend" class="add-btn gray-btn">
                    Unfriend
                </button>
            </form>

        <?php else: ?>
            <span class="pending-text">Request Received</span>
        <?php endif; ?>
    </div>
</div>

<?php endforeach; ?>

</div>
</div>

<!-- LIVE SEARCH SCRIPT -->
<script>
document.getElementById('friendSearch').addEventListener('keyup', function() {
    let searchValue = this.value.toLowerCase();
    let users = document.querySelectorAll('.buddy-item');

    users.forEach(user => {
        let name = user.getAttribute('data-name');
        if (name.includes(searchValue)) {
            user.style.display = "flex";
        } else {
            user.style.display = "none";
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

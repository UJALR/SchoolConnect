<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

protectPage();
$userId = getUserId();
$db = Database::getInstance()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['accept_request'])) {
        acceptFriendRequest($_POST['sender_id'], $userId);
    }

    if (isset($_POST['cancel_request'])) {
        // user cancels an outgoing friend request
        $receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        if ($receiverId) {
            cancelFriendRequest($userId, $receiverId);
        }
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
    JOIN Users u 
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

<!-- <link rel="stylesheet" href="assets/css/feed.css"> -->

<div class="feed-wrapper">

    <!-- LEFT SIDEBAR -->
    <?php include 'includes/left_panel_partial.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="feed-main">
        <h1 class="page-title">My Friends</h1>

        <?php if (empty($friends)): ?>
            <p>You don't have any friends or requests yet.</p>
        <?php else: ?>
            <?php foreach ($friends as $friend): ?>
                <div class="buddy-item">

                    <a href="friendProfile.php?user_id=<?= $friend['user_id'] ?>" class="buddy-info">
                        <img src="<?= htmlspecialchars($friend['profile_picture'] ?: 'assets/images/default-avatar.png') ?>"
                            class="avatar-xs">
                        <div>
                            <span class="buddy-name"><?= htmlspecialchars($friend['full_name']) ?></span>
                            <span class="buddy-id">@<?= htmlspecialchars($friend['username']) ?></span>
                        </div>
                    </a>

                    <div class="buddy-actions">

                        <?php if ($friend['status'] === 'pending' && $friend['user_id_sender'] != $userId): ?>
                            <!-- Incoming request -->
                            <form method="POST">
                                <input type="hidden" name="sender_id" value="<?= $friend['user_id_sender'] ?>">
                                <button type="submit" name="accept_request" class="btn-primary">Accept</button>
                            </form>
                        <?php elseif ($friend['status'] === 'pending' && $friend['user_id_sender'] == $userId): ?>
                            <!-- Outgoing request: allow cancel -->
                            <form method="POST">
                                <input type="hidden" name="receiver_id" value="<?= $friend['user_id'] ?>">
                                <button type="submit" name="cancel_request" class="btn-gray">Cancel Request</button>
                            </form>

                        <?php elseif ($friend['status'] === 'accepted'): ?>
                            <!-- Already friends -->
                            <form method="POST">
                                <input type="hidden" name="other_id" value="<?= $friend['user_id'] ?>">
                                <button type="submit" name="unfriend" class="btn-warning">Unfriend</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
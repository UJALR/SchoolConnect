<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

protectPage();

$friendId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT 
        full_name,
        username,
        college_email,
        bio,
        profile_picture,
        cover_picture,
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
$cover  = $friend['cover_picture'] ?: "assets/images/default-cover.jpg";

include __DIR__ . "/includes/header.php";
?>

<link rel="stylesheet" href="assets/css/feed.css">

<style>
.friend-profile-container {
    max-width: 900px;
    margin: 40px auto;
}

.friend-profile-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.friend-banner {
    height: 240px;
    background-image: url('<?= htmlspecialchars($cover) ?>');
    background-size: cover;
    background-position: center;
}

.friend-header {
    margin-top: -80px;
    text-align: center;
}

.friend-avatar {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    border: 6px solid white;
    object-fit: cover;
    box-shadow: 0 4px 14px rgba(0,0,0,0.2);
}

.friend-info h2 {
    font-size: 2rem;
    margin-top: 14px;
}

.friend-info .username {
    color: #6b7280;
    margin-top: 4px;
}

.friend-info .joined {
    color: #9ca3af;
    font-size: 0.9rem;
    margin-top: 6px;
}

.friend-body {
    padding: 20px 26px;
}

.friend-section h3 {
    font-size: 0.9rem;
    color: #555;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.friend-section p {
    font-size: 0.95rem;
    color: #333;
}

.friend-divider {
    border-top: 1px solid #e5e7eb;
    margin: 16px 0;
}
</style>

<div class="friend-profile-container">
    <div class="friend-profile-card">

        <div class="friend-banner"></div>

        <div class="friend-header">
            <img src="<?= htmlspecialchars($avatar) ?>" class="friend-avatar">

            <div class="friend-info">
                <h2><?= htmlspecialchars($friend['full_name']) ?></h2>
                <div class="username">@<?= htmlspecialchars($friend['username']) ?></div>
                <div class="joined">
                    Joined <?= date("F j, Y", strtotime($friend['created_at'])) ?>
                </div>
            </div>
        </div>

        <div class="friend-body">

            <div class="friend-section">
                <h3>About</h3>
                <p><?= nl2br(htmlspecialchars($friend['bio'] ?: "No bio provided.")) ?></p>
            </div>

            <hr class="friend-divider">

            <div class="friend-section">
                <h3>Contact</h3>
                <p><strong>Email:</strong> <?= htmlspecialchars($friend['college_email']) ?></p>
            </div>

        </div>

    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

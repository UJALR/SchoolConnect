<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

protectPage();
$userId = getUserId();

/* ====== FRIEND ACTIONS (same page) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_action'], $_POST['other_id'])) {
    $otherId = (int)$_POST['other_id'];

    if ($_POST['friend_action'] === 'cancel') {
        cancelFriendRequest($userId, $otherId);
    } elseif ($_POST['friend_action'] === 'accept') {
        // other user sent it to me
        acceptFriendRequest($otherId, $userId);
    } elseif ($_POST['friend_action'] === 'unfriend') {
        unfriendUser($userId, $otherId);
    }

    header("Location: userProfile.php");
    exit;
}

/* ====== USER DATA ====== */
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
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set avatar path
$avatar = $user['profile_picture'] ?: "assets/images/default-avatar.png";
$userInterests = getInterestsForUser($userId);
$userLanguages = getLanguagesForUser($userId);

/* ====== HEADER ====== */
include __DIR__ . "/includes/header.php";
?>

<!-- <link rel="stylesheet" href="assets/css/feed.css"> 

<style>
.feed-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Profile card container */
.profile-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* Gradient banner */
.profile-banner {
    position: relative;
    width: 100%;
    height: 260px;
    border-radius: 18px 18px 0 0;
    overflow: hidden;
    background: linear-gradient(90deg, #16a34a, #22c55e, #38bdf8);
}

/* Header: avatar + info + edit btn */
.profile-header {
    position: relative;
    margin-top: -70px; /* THIS makes it overlap */
    text-align: center;
}

/* Avatar container */
.profile-avatar-wrap {
    position: relative;
    width: 170px;
    margin: 0 auto;
}

/* Avatar */
.profile-avatar-lg {
    width: 170px;
    height: 170px;
    border-radius: 50%;
    object-fit: cover;
    border: 6px solid #fff;
    background: #e5e7eb;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

/* Small camera circle */
.profile-avatar-edit i { 
    font-size: 0.9rem; 
    color: #333;
}
.profile-avatar-edit {
    position: absolute;
    bottom: 14px;
    right: 8px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    border: none;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 6px 14px rgba(0,0,0,0.25);
    cursor: pointer;
    z-index: 1000;
}
.profile-avatar-edit i {
    font-size: 20px;
    color: #333;
}

/* Name + username + date */
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

/* Edit profile button */
.edit-profile-btn {
    margin-top: 12px;
    padding: 10px 18px;
    border-radius: 999px;
    background: #22c55e;
    color: #fff;
    font-weight: 600;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(16,185,129,0.35);
}
.edit-profile-btn i {
    margin-right: 6px;
}

/* Profile body */
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

/* Divider */
.profile-divider {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 16px 0;
}

/* Social card */
.profile-social-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 18px 22px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
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
}

.chip.language {
    background: #d1fae5;
    color: #047857;
}

/* Icons */
.social-icons-row {
    display: flex;
    gap: 12px;
}

.social-icons-row a {
    font-size: 1.4rem;
}
.social-icons-row a.instagram {
    background: radial-gradient(circle at 30% 30%, #fdf497 0, #fd5949 40%, #d6249f 70%, #285AEB 100%);
    -webkit-background-clip: text;
    color: transparent;
}
.social-icons-row a.tiktok {
    color: black;
}

.sidebar-links .active-link {
    color: #006341;
    font-weight: 700;
}

/* small helper just for friends list text spacing */
.friends-panel {
    margin-top: 16px;
    font-size: 0.9rem;
}
</style> -->

<div class="feed-wrapper">

    <!-- LEFT SIDEBAR -->
    <?php include 'includes/left_panel_partial.php'; ?>

    <!-- CENTER -->
    <div class="feed-main">

        <!-- PROFILE CARD -->
        <div class="profile-card">
            <div class="profile-header">

                <!-- Avatar + camera -->
                <div class="profile-avatar-wrap">
                    <img src="<?= htmlspecialchars($avatar) ?>" class="profile-avatar-lg">

                    <!-- Camera triggers file input -->
                    <!-- <label for="avatarUpload" class="profile-avatar-edit">
                        <i class="fa fa-camera"></i>
                    </label> -->

                    <!-- Hidden file input -->
                    <!-- <form action="updateAvatar.php" method="POST" enctype="multipart/form-data">
                        <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display:none" onchange="this.form.submit()">
                    </form> -->
                </div>

                <!-- Main profile text -->
                <div class="profile-info-block">
                    <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                    <div class="username">@<?= htmlspecialchars($user['username']) ?></div>
                    <div class="joined">
                        Joined <?= date("F j, Y", strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <!-- Edit Profile -->
                <a href="edit_profile.php">
                    <button class="edit-profile-btn">
                        <i class="fa fa-pen"></i> Edit Profile
                    </button>
                </a>
            </div>

            <!-- Body -->
            <div class="profile-body">
                <div class="profile-section">
                    <h3>About Me</h3>
                    <p><?= nl2br(htmlspecialchars($user['bio'] ?: "Tell others about yourself!")) ?></p>
                </div>

                <div class="profile-section">
                    <h3>Contact Info</h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['college_email']) ?></p>
                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                </div>
            </div>
        </div>

        <!-- SOCIAL CARD -->
        <div class="profile-social-card">
            <h3>Social & Community</h3>

            <div class="social-row">
                <div class="social-label">Interests:</div>
                <div class="social-value">
                    <?php if (empty($userInterests)): ?>
                        <span style="color:#999;font-style:italic;">No interests listed yet.</span>
                    <?php else: ?>
                        <?php foreach ($userInterests as $interest): ?>
                            <span class="chip"><?= htmlspecialchars($interest) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="social-row">
                <div class="social-label">Languages:</div>
                <div class="social-value">
                    <?php if (empty($userLanguages)): ?>
                        <span style="color:#999;font-style:italic;">No languages listed yet.</span>
                    <?php else: ?>
                        <?php foreach ($userLanguages as $language): ?>
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

    </div>

    <?php include 'includes/right_panel_partial.php'; ?>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

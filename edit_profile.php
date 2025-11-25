<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

protectPage();
$userId = getUserId();

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT full_name, username, college_email, bio, profile_picture, cover_picture
    FROM Users 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$avatar = $user['profile_picture'] ?: "assets/images/default-avatar.png";
$cover  = $user['cover_picture'] ?: "assets/images/default-cover.jpg";

include __DIR__ . "/includes/header.php";
?>

<style>
.edit-card {
    background: #fff;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    max-width: 760px;
    margin: 0 auto;
}
.edit-card h2 {
    margin-bottom: 18px;
    font-size: 1.4rem;
    font-weight: 700;
}
.form-row {
    margin-bottom: 16px;
}
.form-row label {
    font-weight: 600;
    color: #222;
}
.form-row input,
.form-row textarea {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-top: 6px;
    font-size: 1rem;
    color: #222;
    background: #fff !important;
}
.save-btn {
    padding: 12px 22px;
    background: #22c55e;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 20px;
}
.avatar-preview, .cover-preview {
    width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
    border: 3px solid #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
}
</style>

<div class="feed-wrapper">
    <div class="feed-main">

        <div class="edit-card">

            <h2>Edit Profile</h2>

            <!--  1. UPDATE AVATAR FIRST -->
            <h3>Profile Picture</h3>

<img src="<?= htmlspecialchars($avatar) ?>" class="avatar-preview avatar-circle">
<br><br>

<form action="updateAvatar.php" method="POST" enctype="multipart/form-data" id="avatarForm">

    <!-- proper file input -->
    <input type="file" name="avatar" accept="image/*" id="avatarUploadInput">

    
    <!-- force submit button -->
    <button type="submit" class="save-btn">Upload New Avatar</button>

</form>


            <!--  2. UPDATE COVER BANNER -->
            <h3>Cover Banner</h3>
            <img src="<?= htmlspecialchars($cover) ?>" class="cover-preview"><br><br>

            <form action="updateCover.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="cover_picture" accept="image/*" required>
                <button class="save-btn">Upload New Cover</button>
            </form>

            <hr><br>

            <!--  3. PROFILE DETAILS FORM -->
            <form action="updateProfile.php" method="POST">

                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" name="full_name" 
                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="form-row">
                    <label>Username</label>
                    <input type="text" name="username" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="form-row">
                    <label>Email (not editable)</label>
                    <input type="text" value="<?= htmlspecialchars($user['college_email']) ?>" disabled>
                </div>

                <div class="form-row">
                    <label>Bio</label>
                    <textarea name="bio" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
                </div>

                <!-- UI only
                <div class="form-row">
                    <label>Interests</label>
                    <input type="text" placeholder="coding, music, sports">
                </div>

                <div class="form-row">
                    <label>Languages</label>
                    <input type="text" placeholder="English, French">
                </div>

                <div class="form-row">
                    <label>Social Links</label>
                    <input type="text" placeholder="Facebook URL">
                    <input type="text" placeholder="Instagram URL">
                    <input type="text" placeholder="TikTok URL">
                </div> -->

                <!--  4. SAVE CHANGES (LAST BUTTON) -->
                <button class="save-btn" type="submit">Save Changes</button>

            </form>
<script>
document.getElementById('avatarUploadInput').addEventListener('change', function() {
    document.getElementById('avatarForm').submit();
});
</script>

        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

protectPage();
$userId = getUserId();

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT full_name, username, college_email, bio, profile_picture
    FROM Users 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$avatar = $user['profile_picture'] ?: "assets/images/default-avatar.png";
$allInterests = getAllInterests();
$allLanguages = getAllLanguages();
$userInterests = getInterestsForUser($userId);
$userLanguages = getLanguagesForUser($userId);

include __DIR__ . "/includes/header.php";
?>

<style>
    .edit-card {
        background: #fff;
        padding: 22px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
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
        /* background: #fff !important; */
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

    .avatar-preview {
        width: 200px;
        height: 120px;
        object-fit: cover;
        border-radius: 10px;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
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
                <input type="file" name="avatar" accept="image/*" id="avatarUploadInput">
                <button type="submit" class="save-btn">Upload New Avatar</button>
            </form>
            <br><br>

            <!--  2. INTERESTS & LANGUAGES FORM -->
            <h3>Interests & Languages</h3>
            <p style="font-size:0.9rem; color:#666; margin-bottom: 20px;">Use the dropdowns to select or unselect existing items. Use the checkboxes below to add new ones.</p>
            <form action="updateInterestsAndLanguages.php" method="POST" id="IandLForm">
                <div class="form-row">
                    <label for="existing_interests">Current/Existing Interests (Select multiple):</label>
                    <select id="existing_interests" name="interests[]" multiple size="5" style="height: auto; width: 100%; min-height: 120px;">
                        <?php 
                        $userInterestsNames = array_map(fn($n) => trim(strtolower($n)), $userInterests);
                        foreach ($allInterests as $interest): 
                            $selected = in_array(strtolower($interest), $userInterestsNames) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($interest) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($interest) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <input type="checkbox" id="add_new_interests_check" name="add_new_interests_check" style="margin-right: 8px;">
                    <label for="add_new_interests_check" style="font-weight: normal;">Add New Interests</label>
                    <input type="text" id="new_interests_input" name="new_interests" disabled placeholder="Type new interests, separated by commas (e.g., Chess, Drawing, Hiking)">
                </div>

                <div class="form-row">
                    <label for="existing_languages">Current/Existing Languages (Select multiple):</label>
                    <select id="existing_languages" name="languages[]" multiple size="5" style="height: auto; width: 100%; min-height: 120px;">
                        <?php 
                        $userLanguagesNames = array_map(fn($n) => trim(strtolower($n)), $userLanguages);
                        foreach ($allLanguages as $language): 
                            $selected = in_array(strtolower($language), $userLanguagesNames) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($language) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($language) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <input type="checkbox" id="add_new_languages_check" name="add_new_languages_check" style="margin-right: 8px;">
                    <label for="add_new_languages_check" style="font-weight: normal;">Add New Languages</label>
                    <input type="text" id="new_languages_input" name="new_languages" disabled placeholder="Type new languages, separated by commas (e.g., German, Mandarin)">
                </div>

                <button class="save-btn" type="submit" style="background:#3b82f6;">Save Interests & Languages</button>
            </form>
            <hr class="profile-divider" style="margin-top: 30px;">

            <!--  3. PROFILE DETAILS FORM -->
            <form action="updateProfile.php" method="POST">
                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="form-row">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
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
                document.getElementById('avatarUploadInput').addEventListener('change', function () {
                    document.getElementById('avatarForm').submit();
                });

                // --- Interest Input Toggle ---
                const interestCheck = document.getElementById('add_new_interests_check');
                const interestInput = document.getElementById('new_interests_input');

                interestCheck.addEventListener('change', function() {
                    interestInput.disabled = !this.checked;
                    interestInput.style.backgroundColor = this.checked ? 'white' : '#f4f4f4';
                    if (!this.checked) interestInput.value = ''; // Clear input if disabled
                });

                // --- Language Input Toggle ---
                const languageCheck = document.getElementById('add_new_languages_check');
                const languageInput = document.getElementById('new_languages_input');

                languageCheck.addEventListener('change', function() {
                    languageInput.disabled = !this.checked;
                    languageInput.style.backgroundColor = this.checked ? 'white' : '#f4f4f4';
                    if (!this.checked) languageInput.value = ''; // Clear input if disabled
                });

                // Initialize state
                interestInput.style.backgroundColor = '#f4f4f4';
                languageInput.style.backgroundColor = '#f4f4f4';
            </script>

        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) session_start();
protectPage();
$userId = getUserId();
$db = Database::getInstance();
$pdo = $db->getConnection();

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
if (!$groupId) {
    header('Location: my_groups.php');
    exit;
}

// Load group and verify existence
$gStmt = $pdo->prepare("SELECT * FROM UserGroups WHERE group_id = ?");
$gStmt->execute([$groupId]);
$group = $gStmt->fetch(PDO::FETCH_ASSOC);
if (!$group) {
    header('Location: my_groups.php');
    exit;
}

$isCreator = ($group['creator_id'] == $userId);

$message = '';
$error = '';

// Handle group update (only by creator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    if (!$isCreator) {
        $error = 'Only the group creator can edit this group.';
    } else {
        $name = trim($_POST['group_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        if ($name === '') {
            $error = 'Group name cannot be empty.';
        } else {
            $uStmt = $pdo->prepare("UPDATE UserGroups SET group_name = ?, description = ?, is_private = ? WHERE group_id = ? AND creator_id = ?");
            if ($uStmt->execute([$name, $desc, $is_private, $groupId, $userId])) {
                // Redirect to group details after successful update
                header("Location: groups_detail.php?group_id={$groupId}");
                exit;
            } else {
                $error = 'Failed to update group, please try again.';
            }
        }
    }
}

// Handle remove member action (only creator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $removeId = (int)($_POST['member_user_id'] ?? 0);
    if (!$isCreator) {
        $error = 'Only the group creator can remove members.';
    } elseif ($removeId === $userId) {
        $error = 'You cannot remove yourself as the creator.';
    } else {
        $r = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        if ($r->execute([$groupId, $removeId])) {
            // After removing member, refresh the edit page to avoid resubmission
            header("Location: edit_group.php?group_id={$groupId}");
            exit;
        } else {
            $error = 'Failed to remove member.';
        }
    }
}

// Refresh members list
$memStmt = $pdo->prepare("SELECT u.user_id, u.full_name, u.username, u.profile_picture, gm.joined_at FROM GroupMembers gm JOIN Users u ON gm.user_id = u.user_id WHERE gm.group_id = ? ORDER BY gm.joined_at ASC");
$memStmt->execute([$groupId]);
$members = $memStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/feed.css">
<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <div class="post-card">
            <h2>Edit Group</h2>
            <?php if ($error): ?>
                <div class="error" style="color:#b91c1c;margin-bottom:10px"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="success" style="color:#065f46;margin-bottom:10px"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (!$isCreator): ?>
                <div class="text-center">Only the group creator can edit this group.</div>
            <?php else: ?>
                <form method="post">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <label>Group Name</label>
                        <input type="text" name="group_name" value="<?= htmlspecialchars($group['group_name']) ?>" style="padding:8px;border:1px solid #e6e6e6;border-radius:8px;">

                        <label>Description</label>
                        <textarea name="description" rows="4" style="padding:8px;border:1px solid #e6e6e6;border-radius:8px"><?= htmlspecialchars($group['description']) ?></textarea>

                        <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_private" <?= $group['is_private'] ? 'checked' : '' ?>> Private group</label>

                        <div>
                            <button type="submit" name="update_group" class="btn-primary">Save Changes</button>
                            <a href="groups_detail.php?group_id=<?= $groupId ?>" class="btn btn-secondary" style="margin-left:8px;">Back</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="post-card" style="margin-top:16px;">
            <h3>Members</h3>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <?php foreach ($members as $m): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f1f1f1;padding-bottom:8px;padding-top:8px;">
                        <div style="display:flex;gap:12px;align-items:center;">
                            <img src="<?= htmlspecialchars($m['profile_picture']?: 'assets/images/default-avatar.png') ?>" class="avatar-xs">
                            <div>
                                <div style="font-weight:700"><?= htmlspecialchars($m['full_name']) ?></div>
                                <div style="font-size:0.9rem;color:#666">@<?= htmlspecialchars($m['username']) ?></div>
                            </div>
                        </div>
                        <div>
                            <?php if ($isCreator && $m['user_id'] != $userId): ?>
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="member_user_id" value="<?= $m['user_id'] ?>">
                                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                                    <button type="submit" name="remove_member" class="btn btn-warning">Remove</button>
                                </form>
                            <?php else: ?>
                                <?php if ($m['user_id'] == $userId): ?>
                                    <span style="color:#6b7280;font-size:0.9rem">Creator</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>

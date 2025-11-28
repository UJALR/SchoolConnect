<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

protectPage();
$userId = getUserId();
$db = Database::getInstance();
$pdo = $db->getConnection();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

// Handle Join Group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group'])) {
    $groupIdToModify = (int) $_POST['group_id'];

    $joinStmt = $pdo->prepare("INSERT IGNORE INTO GroupMembers (group_id, user_id) VALUES (?, ?)");
    if ($joinStmt->execute([$groupIdToModify, $userId])) {
        // Redirect to clear POST data and show updated status
        header("Location: groups_detail.php?group_id=" . $groupIdToModify);
        exit;
    } else {
        $error = "Failed to join group. Please try again.";
    }
}

// Handle Leave Group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group'])) {
    $groupIdToModify = (int) $_POST['group_id'];

    // Check if user is the creator (Prevent creator from leaving)
    $checkCreatorStmt = $pdo->prepare("SELECT creator_id FROM UserGroups WHERE group_id = ?");
    $checkCreatorStmt->execute([$groupIdToModify]);
    $groupCreator = $checkCreatorStmt->fetchColumn();

    if ($groupCreator == $userId) {
        $error = "You cannot leave a group you created. You must transfer ownership or delete the group first.";
    } else {
        $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        if ($leaveStmt->execute([$groupIdToModify, $userId])) {
            // Redirect to clear POST data and show updated status
            header("Location: groups_detail.php?group_id=" . $groupIdToModify);
            exit;
        } else {
            $error = "Failed to leave group. Please try again.";
        }
    }
}

// Get group details
$groupStmt = $pdo->prepare("
    SELECT 
        g.*,
        u.username as creator_name,
        -- Subquery to get the total member count (since we can't GROUP BY g.group_id 
        -- and also select gm.member_role without violating ONLY_FULL_GROUP_BY)
        (
            SELECT COUNT(user_id) FROM GroupMembers WHERE group_id = g.group_id
        ) as member_count,
        -- Check if the current user is a member (1=true, 0=false)
        EXISTS(SELECT 1 FROM GroupMembers WHERE group_id = g.group_id AND user_id = ?) as is_member,
        -- Get the current user's specific member role
        (
            SELECT member_role FROM GroupMembers WHERE group_id = g.group_id AND user_id = ?
        ) as member_role
    FROM UserGroups g
    LEFT JOIN Users u ON g.creator_id = u.user_id
    WHERE g.group_id = ?
");
$groupStmt->execute([$userId, $userId, $groupId]);
$group = $groupStmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header("Location: my_groups.php");
    exit();
}

// Get group members
$membersStmt = $pdo->prepare("
    SELECT u.user_id, u.username, u.full_name, u.profile_picture, gm.member_role, gm.joined_at
    FROM GroupMembers gm
    JOIN Users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ?
    ORDER BY 
        CASE WHEN gm.member_role = 'admin' THEN 1 ELSE 2 END,
        gm.joined_at ASC
");
$membersStmt->execute([$groupId]);
$members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get group posts
$postsStmt = $pdo->prepare("
    SELECT p.*, u.username, u.full_name, u.profile_picture
    FROM Posts p
    JOIN Users u ON p.user_id = u.user_id
    WHERE p.group_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
");
$postsStmt->execute([$groupId]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="content-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1><?php echo htmlspecialchars($group['group_name']); ?></h1>
            <p style="color: rgba(255, 255, 255, 0.8); margin: 0;">
                <?php echo htmlspecialchars($group['description']); ?>
            </p>
        </div>
        <div style="text-align: right;">
            <span style="
                background: <?php echo $group['is_private'] ? 'var(--warning-color)' : 'var(--success-color)'; ?>;
                color: var(--white);
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 600;
            ">
                <?php echo $group['is_private'] ? 'Private Group' : 'Public Group'; ?>
            </span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Left Column - Posts -->
        <div>
            <h2>Group Posts</h2>
            <?php if ($group['is_member']): ?>
                <!-- Post creation form for members -->
                <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h4>Create a Post</h4>
                    <form method="post" action="create_group_post.php">
                        <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                        <textarea name="post_text" placeholder="What's happening in the group?" style="width: 100%; height: 100px; padding: 10px; border-radius: 8px; 
                                         background: rgba(255, 255, 255, 0.1); color: var(--white); 
                                         border: 1px solid rgba(255, 255, 255, 0.3);"></textarea>
                        <button type="submit" class="btn" style="margin-top: 10px;">Post to Group</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Group Posts -->
            <?php foreach ($posts as $post): ?>
                <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <img src="<?php echo htmlspecialchars($post['profile_picture'] ?: 'assets/images/default-avatar.png'); ?>"
                            style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <strong
                                style="color: var(--white);"><?php echo htmlspecialchars($post['full_name']); ?></strong><br>
                            <small
                                style="color: rgba(255, 255, 255, 0.6);"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                        </div>
                    </div>
                    <p style="color: rgba(255, 255, 255, 0.9); margin: 0; line-height: 1.5;">
                        <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <?php if (empty($posts)): ?>
                <div style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.6);">
                    <p>No posts yet in this group.</p>
                    <?php if ($group['is_member']): ?>
                        <p>Be the first to post something!</p>
                    <?php else: ?>
                        <p>Join the group to see and create posts.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Group Info -->
        <div>
            <!-- Group Statistics -->
            <?php if (isset($error)): ?>
                <div class="error" style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px;">

                <div style="margin-bottom: 15px;">
                    <?php if ($group['is_member']): ?>
                        <?php if ($group['creator_id'] == $userId): ?>
                            <button class="btn btn-secondary" disabled>Creator (Cannot Leave)</button>
                        <?php else: ?>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                <button type="submit" name="leave_group" class="btn btn-secondary">
                                    Leave Group
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (!$group['is_private']): ?>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                <button type="submit" name="join_group" class="btn">
                                    Join Group
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: var(--warning-color); font-weight: 600;">Private (Request Required)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <h3>Group Info</h3>
                <div style="color: rgba(255, 255, 255, 0.9);">
                    <p><strong>Creator:</strong> <?php echo htmlspecialchars($group['creator_name']); ?></p>
                    <p><strong>Members:</strong> <?php echo $group['member_count']; ?></p>
                    <p><strong>Created:</strong> <?php echo date('M j, Y', strtotime($group['created_at'])); ?></p>
                    <?php if ($group['is_member']): ?>
                        <p><strong>Your Role:</strong> <span
                                style="color: var(--accent-color);"><?php echo ucfirst($group['member_role']); ?></span></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Members -->
            <div style="background: rgba(255, 255, 255, 0.1); padding: 20px; border-radius: 8px;">
                <h3>Members (<?php echo count($members); ?>)</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($members as $member): ?>
                        <div
                            style="display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <img src="<?php echo htmlspecialchars($member['profile_picture'] ?: 'assets/images/default-avatar.png'); ?>"
                                style="width: 35px; height: 35px; border-radius: 50%; margin-right: 10px;">
                            <div style="flex: 1;">
                                <div style="color: var(--white); font-weight: 600;">
                                    <?php echo htmlspecialchars($member['full_name']); ?></div>
                                <div style="color: rgba(255, 255, 255, 0.6); font-size: 0.8rem;">
                                    @<?php echo htmlspecialchars($member['username']); ?>
                                    <?php if ($member['member_role'] === 'admin'): ?>
                                        <span style="color: var(--accent-color); margin-left: 5px;">• Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
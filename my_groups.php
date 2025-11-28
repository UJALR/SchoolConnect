<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

protectPage();
$userId = getUserId();
$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle leave group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group'])) {
    $groupId = (int)$_POST['group_id'];
    
    // Check if user is the creator
    $checkCreatorStmt = $pdo->prepare("SELECT creator_id FROM UserGroups WHERE group_id = ?");
    $checkCreatorStmt->execute([$groupId]);
    $groupCreator = $checkCreatorStmt->fetchColumn();
    
    if ($groupCreator == $userId) {
        $error = "You cannot leave a group you created. You must transfer ownership or delete the group first.";
    } else {
        $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        if ($leaveStmt->execute([$groupId, $userId])) {
            $success = "Successfully left the group.";
        } else {
            $error = "Failed to leave group. Please try again.";
        }
    }
}

// Get user's groups
try {
    $myGroupsStmt = $pdo->prepare("
        SELECT 
            g.group_id,
            g.group_name,
            g.description,
            g.creator_id,
            g.is_private,
            g.created_at,
            u.username as creator_name,
            u.full_name as creator_full_name,
            -- Subquery 1: Get total member count
            (
                SELECT COUNT(user_id) FROM GroupMembers WHERE group_id = g.group_id
            ) as member_count,
            -- Subquery 2: Get current user's specific role
            (
                SELECT member_role FROM GroupMembers WHERE group_id = g.group_id AND user_id = ?
            ) as member_role,
            -- Get current user's join date from GroupMembers
            gm.joined_at
        FROM UserGroups g
        -- JOIN to GroupMembers (gm) is necessary to filter groups for the current user
        JOIN GroupMembers gm ON g.group_id = gm.group_id AND gm.user_id = ? 
        LEFT JOIN Users u ON g.creator_id = u.user_id
        WHERE gm.user_id = ?
        ORDER BY gm.joined_at DESC
    ");
    $myGroupsStmt->execute([$userId, $userId, $userId]);
    $myGroups = $myGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading your groups: " . $e->getMessage();
    $myGroups = [];
}
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/feed.css">

<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <div class="page-header">
        <h1>My Groups</h1>
        <p>Groups you are currently a member of</p>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <a href="all_groups.php" class="btn">Browse All Groups</a>
            <a href="create_group.php" class="btn btn-secondary">Create New Group</a>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="groups-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 30px;">
        <?php foreach ($myGroups as $group): ?>
            <div class="group-card" style="
                background: rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                position: relative;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            ">
                <!-- Role Badge -->
                <div style="position: absolute; top: 15px; right: 15px;">
                    <span style="
                        background: <?php echo $group['member_role'] === 'admin' ? 'var(--accent-color)' : 'var(--primary-color)'; ?>;
                        color: var(--white);
                        padding: 4px 8px;
                        border-radius: 20px;
                        font-size: 0.8rem;
                        font-weight: 600;
                    ">
                        <?php echo ucfirst($group['member_role']); ?>
                    </span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; padding-right: 80px;">
                    <h3 style="color: var(--white); margin: 0; font-size: 1.3rem;">
                        <a href="groups_detail.php?group_id=<?php echo $group['group_id']; ?>" style="color: var(--white); text-decoration: none;">
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </a>
                    </h3>
                    <span style="
                        background: <?php echo $group['is_private'] ? 'var(--warning-color)' : 'var(--success-color)'; ?>;
                        color: var(--white);
                        padding: 4px 8px;
                        border-radius: 20px;
                        font-size: 0.8rem;
                        font-weight: 600;
                    ">
                        <?php echo $group['is_private'] ? 'Private' : 'Public'; ?>
                    </span>
                </div>
                
                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 15px; line-height: 1.5; min-height: 60px;">
                    <?php echo htmlspecialchars($group['description'] ?: 'No description provided.'); ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                        <div><strong>Creator:</strong> <a href="friendProfile.php?user_id=<?php echo $group['creator_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($group['creator_full_name']); ?></a></div>
                        <div><strong>Members:</strong> <?php echo $group['member_count']; ?></div>
                        <div><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($group['joined_at'])); ?></div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if ($group['member_role'] !== 'admin' || $group['creator_id'] != $userId): ?>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                <button type="submit" name="leave_group" class="btn btn-secondary" 
                                        style="padding: 8px 16px; font-size: 0.9rem;">
                                    Leave
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: var(--accent-color); font-size: 0.9rem; font-weight: 600; padding: 8px 0;">
                                Creator
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($myGroups)): ?>
            <div style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.8); grid-column: 1 / -1;">
                <h3>You haven't joined any groups yet</h3>
                <p>Discover and join groups that match your interests</p>
                <a href="all_groups.php" class="btn" style="margin-top: 15px;">Browse All Groups</a>
            </div>
        <?php endif; ?>
    </div>
    </div>
    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
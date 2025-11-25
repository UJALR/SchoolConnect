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

// Handle leave group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group'])) {
    $groupId = (int)$_POST['group_id'];
    $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
    $leaveStmt->execute([$groupId, $userId]);
    
    // Refresh page to show updated list
    header("Location: my_groups.php");
    exit();
}

// Get user's groups
$myGroupsStmt = $pdo->prepare("
    SELECT 
        g.group_id,
        g.group_name,
        g.description,
        g.creator_id,
        g.is_private,
        g.created_at,
        u.username as creator_name,
        COUNT(gm.user_id) as member_count,
        gm.member_role,
        gm.joined_at
    FROM UserGroups g
    JOIN GroupMembers gm ON g.group_id = gm.group_id
    LEFT JOIN Users u ON g.creator_id = u.user_id
    LEFT JOIN GroupMembers gm2 ON g.group_id = gm2.group_id
    WHERE gm.user_id = ?
    GROUP BY g.group_id
    ORDER BY gm.joined_at DESC
");
$myGroupsStmt->execute([$userId]);
$myGroups = $myGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="content-container">
    <h1>My Groups</h1>
    <p>Groups you are currently a member of</p>

    <div class="groups-grid" style="display: grid; gap: 20px; margin-top: 30px;">
        <?php foreach ($myGroups as $group): ?>
            <div class="group-card" style="
                background: rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                position: relative;
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
                    <h3 style="color: var(--white); margin: 0;"><?php echo htmlspecialchars($group['group_name']); ?></h3>
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
                
                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 15px; line-height: 1.5;">
                    <?php echo htmlspecialchars($group['description'] ?: 'No description provided.'); ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">
                        <strong>Created by:</strong> <?php echo htmlspecialchars($group['creator_name']); ?><br>
                        <strong>Members:</strong> <?php echo $group['member_count']; ?><br>
                        <strong>Joined:</strong> <?php echo date('M j, Y', strtotime($group['joined_at'])); ?>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <a href="group_details.php?group_id=<?php echo $group['group_id']; ?>" 
                           class="btn" style="padding: 8px 16px; font-size: 0.9rem;">
                            View Group
                        </a>
                        
                        <?php if ($group['member_role'] !== 'admin'): ?>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                <button type="submit" name="leave_group" class="btn btn-secondary" 
                                        style="padding: 8px 16px; font-size: 0.9rem;"
                                        onclick="return confirm('Are you sure you want to leave this group?')">
                                    Leave Group
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: var(--accent-color); font-size: 0.9rem; font-weight: 600;">
                                Can't leave (Admin)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($myGroups)): ?>
            <div style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.8);">
                <h3>You haven't joined any groups yet</h3>
                <p>Discover and join groups that match your interests</p>
                <a href="all_groups.php" class="btn" style="margin-top: 15px;">Browse All Groups</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
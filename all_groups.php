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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join_group'])) {
        $groupId = (int)$_POST['group_id'];
        
        $checkStmt = $pdo->prepare("SELECT * FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $checkStmt->execute([$groupId, $userId]);
        
        if (!$checkStmt->fetch()) {
            $joinStmt = $pdo->prepare("INSERT INTO GroupMembers (group_id, user_id, member_role) VALUES (?, ?, 'member')");
            $joinStmt->execute([$groupId, $userId]);
        }
    } elseif (isset($_POST['leave_group'])) {
        $groupId = (int)$_POST['group_id'];
        $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $leaveStmt->execute([$groupId, $userId]);
    }
    
    header("Location: all_groups.php");
    exit();
}

$groupsStmt = $pdo->prepare("
    SELECT 
        g.group_id,
        g.group_name,
        g.description,
        g.creator_id,
        g.is_private,
        g.created_at,
        u.username as creator_name,
        COUNT(gm.user_id) as member_count,
        EXISTS(SELECT 1 FROM GroupMembers WHERE group_id = g.group_id AND user_id = ?) as is_member
    FROM UserGroups g
    LEFT JOIN GroupMembers gm ON g.group_id = gm.group_id
    LEFT JOIN Users u ON g.creator_id = u.user_id
    GROUP BY g.group_id
    ORDER BY g.created_at DESC
");
$groupsStmt->execute([$userId]);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="content-container">
    <h1>All Groups</h1>
    <p>Discover and join groups that match your interests</p>

    <div class="groups-grid" style="display: grid; gap: 20px; margin-top: 30px;">
        <?php foreach ($groups as $group): ?>
            <div class="group-card" style="
                background: rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
            ">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
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
                        <strong>Members:</strong> <?php echo $group['member_count']; ?>
                    </div>
                    
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                        <?php if ($group['is_member']): ?>
                            <button type="submit" name="leave_group" class="btn btn-secondary" 
                                    style="padding: 8px 16px; font-size: 0.9rem;">
                                Leave Group
                            </button>
                        <?php else: ?>
                            <button type="submit" name="join_group" class="btn" 
                                    style="padding: 8px 16px; font-size: 0.9rem;">
                                Join Group
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div style="color: rgba(255, 255, 255, 0.6); font-size: 0.8rem;">
                    Created: <?php echo date('M j, Y', strtotime($group['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($groups)): ?>
            <div style="text-align: center; padding: 40px; color: rgba(255, 255, 255, 0.8);">
                <h3>No groups found</h3>
                <p>Be the first to create a group!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
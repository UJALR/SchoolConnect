<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE)
{
    session_start();
}

protectPage();
$userId = getUserId();
$db = Database::getInstance();
$pdo = $db->getConnection();

// Handle leave group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group']))
{
    $groupId = (int)$_POST['group_id'];

    // Check if user is the creator
    $checkCreatorStmt = $pdo->prepare("SELECT creator_id FROM UserGroups WHERE group_id = ?");
    $checkCreatorStmt->execute([$groupId]);
    $groupCreator = $checkCreatorStmt->fetchColumn();

    if ($groupCreator == $userId)
    {
        $error = "You cannot leave a group you created. You must transfer ownership or delete the group first.";
    }
    else
    {
        $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        if ($leaveStmt->execute([$groupId, $userId]))
        {
            $success = "Successfully left the group.";
        }
        else
        {
            $error = "Failed to leave group. Please try again.";
        }
    }
}

// Get user's groups
try
{
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
}
catch (PDOException $e)
{
    $error = "Error loading your groups: " . $e->getMessage();
    $myGroups = [];
}
?>

<?php include 'includes/header.php'; ?>

<!-- <link rel="stylesheet" href="assets/css/feed.css"> -->

<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <div class="page-header">
            <h1>My Groups</h1>
            <p>Groups you are currently a member of</p>
            <div class="group-actions">
                <a href="create_group.php" class="btn-primary">Create New Group</a>
                <a href="all_groups.php" class="btn-secondary">Browse All Groups</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="groups-item">
            <?php foreach ($myGroups as $group): ?>
                <div class="group-card">
                    <!-- Role Badge -->
                    <div class="group-meta">
                        <div>
                            <?php echo ucfirst($group['member_role']); ?>
                        </div>
                        <div>
                            <?php echo $group['is_private'] ? 'Private' : 'Public'; ?>
                        </div>
                    </div>

                    <h3 class="group-title">
                        <a href="groups_detail.php?group_id=<?php echo $group['group_id']; ?>">
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </a>
                    </h3>

                    <p class="group-description">
                        <?php echo htmlspecialchars($group['description'] ?: 'No description provided.'); ?>
                    </p>

                    <div class="group-details">
                        <div>
                            <div><strong>Creator:</strong> <a href="friendProfile.php?user_id=<?php echo $group['creator_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($group['creator_full_name']); ?></a></div>
                            <div><strong>Members:</strong> <?php echo $group['member_count']; ?></div>
                            <div><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($group['joined_at'])); ?></div>
                        </div>

                        <?php if ($group['member_role'] !== 'admin' || $group['creator_id'] != $userId): ?>
                            <form method="post">
                                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                <button type="submit" name="leave_group" class="btn-warning">Leave</button>
                            </form>
                        <?php else: ?>
                            <!-- @apply bg-gray-400 border-gray-400 cursor-pointer hover:bg-gray-500 hover:border-gray-500 text-seasalt px-4 py-2 rounded-md no-underline; -->
                            <span class="inline-block border border-gray-400 p-2 rounded-md text-gray-400 mt-3">Creator</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($myGroups)): ?>
                <div class="group-card">
                    <p class="text-lg text-center font-semibold">You haven't joined any groups yet</p>
                    <p class="text-center">Discover and join groups that match your interests</p>
                    <a class="text-center" href="all_groups.php">Browse All Groups</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
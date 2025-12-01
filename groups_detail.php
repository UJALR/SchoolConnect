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

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

// Handle Join Group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group']))
{
    $groupIdToModify = (int) $_POST['group_id'];

    $joinStmt = $pdo->prepare("INSERT IGNORE INTO GroupMembers (group_id, user_id) VALUES (?, ?)");
    if ($joinStmt->execute([$groupIdToModify, $userId]))
    {
        // Redirect to clear POST data and show updated status
        header("Location: groups_detail.php?group_id=" . $groupIdToModify);
        exit;
    }
    else
    {
        $error = "Failed to join group. Please try again.";
    }
}

// Handle Leave Group action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_group']))
{
    $groupIdToModify = (int) $_POST['group_id'];

    // Check if user is the creator (Prevent creator from leaving)
    $checkCreatorStmt = $pdo->prepare("SELECT creator_id FROM UserGroups WHERE group_id = ?");
    $checkCreatorStmt->execute([$groupIdToModify]);
    $groupCreator = $checkCreatorStmt->fetchColumn();

    if ($groupCreator == $userId)
    {
        $error = "You cannot leave a group you created. You must transfer ownership or delete the group first.";
    }
    else
    {
        $leaveStmt = $pdo->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        if ($leaveStmt->execute([$groupIdToModify, $userId]))
        {
            // Redirect to clear POST data and show updated status
            header("Location: groups_detail.php?group_id=" . $groupIdToModify);
            exit;
        }
        else
        {
            $error = "Failed to leave group. Please try again.";
        }
    }
}

// Handle comment submission for group posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']))
{
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $commentText = trim($_POST['comment_text'] ?? '');

    if ($postId > 0 && $groupId > 0 && $commentText !== '')
    {
        // verify membership
        $checkMember = $pdo->prepare("SELECT 1 FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $checkMember->execute([$groupId, $userId]);
        if ($checkMember->fetch())
        {
            createComment($postId, $userId, $commentText);
        }
    }

    header("Location: groups_detail.php?group_id={$groupId}#post-" . $postId);
    exit;
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

if (!$group)
{
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

<!-- <link rel="stylesheet" href="assets/css/feed.css"> -->

<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <!-- <div class="content-container"> -->
        <div class="group-title card">
            <div class="group-info">
                <small class="meta">
                    <?php echo $group['is_private'] ? 'Private Group' : 'Public Group'; ?>
                </small>
                <h1 class="group-name"><?php echo htmlspecialchars($group['group_name']); ?></h1>
                <p class="group-description">
                    <?php echo htmlspecialchars($group['description']); ?>
                </p>
            </div>
        </div>

        <!-- <div class="group-post"> -->
        <?php if ($group['is_member']): ?>
            <!-- Post creation form for members -->
            <div class="create-group-post">
                <h2>Create a Post</h2>
                <form method="post" action="create_group_post.php">
                    <input type="hidden" name="group_id" value="<?php echo $groupId; ?>">
                    <textarea class="w-full" name="post_text" rows="4" placeholder="What's happening in the group?"></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">Post to Group</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <h2 class="m-0">Group Posts</h2>

        <!-- Group Posts -->
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div id="post-<?php echo $post['post_id']; ?>">
                    <div class="post-header">
                        <img src="<?php echo htmlspecialchars($post['profile_picture'] ?: 'assets/images/default-avatar.png'); ?>" class="avatar-sm">
                        <div>
                            <span class="user-name"><?php echo htmlspecialchars($post['full_name']); ?></span>
                            <small class="post-date"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></small>
                        </div>
                    </div>
                    <p>
                        <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                    </p>
                    <!-- Comments -->
                    <div class="comment-section">
                        <?php if ($group['is_member']): ?>
                            <form action="groups_detail.php?group_id=<?php echo $groupId; ?>" method="POST" class="comment-form" id="comment-form-<?php echo $post['post_id']; ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                <input type="text" name="comment_text" id="comment-input-<?php echo $post['post_id']; ?>" class="comment-input-field" placeholder="Add a comment..." required>
                                <button type="submit" name="submit_comment" class="comment-btn"><i class="fa fa-comment"></i></button>
                            </form>
                        <?php endif; ?>

                        <div class="comments-list">
                            <?php $comments = getCommentsForPost($post['post_id']);
                            foreach ($comments as $comment):
                                $commentAvatar = $comment['profile_picture'] ?: 'assets/images/default-avatar.png';
                            ?>
                                <div class="comment-item">
                                    <img src="<?= htmlspecialchars($commentAvatar) ?>" class="avatar-xs mr-2">
                                    <div class="comment-content">
                                        <a class="comment-author" href="friendProfile.php?user_id=<?= $comment['user_id'] ?>"><?= htmlspecialchars($comment['full_name']) ?></a>
                                        <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($posts)): ?>
            <div class="text-center">
                <p>No posts yet in this group.</p>
                <?php if ($group['is_member']): ?>
                    <p>Be the first to post something!</p>
                <?php else: ?>
                    <p>Join the group to see and create posts.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- </div> -->
        <!-- </div> -->
    </div>

    <?php include 'includes/right_panel_partial.php'; ?>

</div>

<?php include 'includes/footer.php'; ?>
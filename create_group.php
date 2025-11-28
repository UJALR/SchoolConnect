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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = trim($_POST['group_name']);
    $description = trim($_POST['description']);
    $isPrivate = isset($_POST['is_private']) ? 1 : 0;
    
    if (empty($groupName)) {
        $error = "Group name is required.";
    } else {
        try {
            // Check if group name already exists
            $checkStmt = $pdo->prepare("SELECT group_id FROM UserGroups WHERE group_name = ?");
            $checkStmt->execute([$groupName]);
            
            if ($checkStmt->fetch()) {
                $error = "A group with this name already exists. Please choose a different name.";
            } else {
                // Create the group
                $createStmt = $pdo->prepare("
                    INSERT INTO UserGroups (group_name, description, creator_id, is_private, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $createStmt->execute([$groupName, $description, $userId, $isPrivate]);
                
                $groupId = $pdo->lastInsertId();
                
                // Add creator as admin member
                $memberStmt = $pdo->prepare("
                    INSERT INTO GroupMembers (group_id, user_id, member_role, joined_at) 
                    VALUES (?, ?, 'admin', NOW())
                ");
                $memberStmt->execute([$groupId, $userId]);
                
                $success = "Group created successfully!";
                
                // Redirect to the new group
                header("Location: groups_detail.php?group_id=" . $groupId);
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error creating group: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="assets/css/feed.css">

<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>

    <div class="feed-main">
        <div class="content-container">
            <div class="auth-container">
        <h1>Create New Group</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="group_name">Group Name *</label>
                <input type="text" id="group_name" name="group_name" required 
                       value="<?php echo isset($_POST['group_name']) ? htmlspecialchars($_POST['group_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" 
                          placeholder="What is this group about?"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="is_private" name="is_private" value="1"
                           <?php echo isset($_POST['is_private']) ? 'checked' : ''; ?>>
                    <span>Make this a private group (requires approval to join)</span>
                </label>
                <small style="color: rgba(255, 255, 255, 0.7); margin-top: 5px;">
                    Private groups are only visible to members and require approval to join.
                </small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Create Group</button>
                <a href="my_groups.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
            </div>
        </div>
    </div>
    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<?php include 'includes/footer.php'; ?>
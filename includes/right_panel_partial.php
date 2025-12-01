<?php
// Right panel partial - Suggested friends and suggested groups
// Expects $userId to be available or will attempt to get it via getUserId().
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$userId = $userId ?? (function_exists('getUserId') ? getUserId() : null);

if (!isset($suggestedFriends)) {
    if ($userId && function_exists('getSuggestedFriends')) {
        $suggestedFriends = getSuggestedFriends($userId);
    } else {
        $suggestedFriends = [];
    }
}

if (!isset($suggestedGroups)) {
    if (function_exists('getSuggestedGroups')) {
        $suggestedGroups = getSuggestedGroups(3);
    } else {
        $suggestedGroups = [];
    }
}
?>

<aside class="sidebar-right">
    <div class="section-title">Potential Buddies</div>
    <?php foreach ($suggestedFriends as $fr): ?>
        <a href="friendProfile.php?user_id=<?= $fr['user_id'] ?>" class="buddy-item" style="text-decoration:none;color:inherit;">
            <img src="<?= htmlspecialchars($fr['profile_picture'] ?: 'assets/images/default-avatar.png') ?>" class="avatar-xs">
            <span><?= htmlspecialchars($fr['full_name']) ?></span>
        </a>
    <?php endforeach; ?>
    <a href="viewFriends.php" class="view-all-link">View All Friends &raquo;</a>

    <div class="section-title">Join a Community</div>
    <?php if (empty($suggestedGroups)): ?>
        <p style="color: rgba(255, 255, 255, 0.6); padding: 5px 0;">No groups to suggest yet.</p>
    <?php else: ?>
        <?php foreach ($suggestedGroups as $group): ?>
            <a class="community-item" href="groups_detail.php?group_id=<?= $group['group_id'] ?>"><?= htmlspecialchars($group['group_name']) ?></a>
        <?php endforeach; ?>
        <a href="all_groups.php" class="view-all-link">View All Groups &raquo;</a>
    <?php endif; ?>
</aside>

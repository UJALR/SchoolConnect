<?php
// Left panel partial - displays common left sidebar links and pending friend count
// Ensure `getUserId()` and `getPendingFriendRequestCount()` are available (from includes/auth.php and includes/functions.php)
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

$currentUserId = $currentUserId ?? (function_exists('getUserId') ? getUserId() : null);

if (!isset($pendingCount)) {
	if ($currentUserId && function_exists('getPendingFriendRequestCount')) {
		$pendingCount = getPendingFriendRequestCount($currentUserId);
	} else {
		$pendingCount = 0;
	}
}
?>

<aside class="sidebar-left">
	<input type="text" class="search-box" placeholder="Search">
	<nav class="sidebar-links">
		<a href="userProfile.php">My Profile</a>
		<a href="viewMyFriends.php" class="friends-link">
			Friends
			<?php if ($pendingCount > 0): ?>
				<span class="friend-badge"><?= $pendingCount ?></span>
			<?php endif; ?>
		</a>
		<a href="my_groups.php">Groups</a>
		<a href="Logout.php">Logout</a>
	</nav>
</aside>

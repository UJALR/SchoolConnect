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
		<a href="posts.php" class="mb-2">Home</a>
		<a href="chat.php" class="messages-link">Messages
			<?php if ($currentUserId && function_exists('getUnreadMessageCount')): $unread = getUnreadMessageCount($currentUserId); else: $unread = 0; endif; ?>
			<?php if ($unread > 0): ?>
				<span class="friend-badge"><?= $unread ?></span>
			<?php endif; ?>
		</a>
		<hr class="text-gray-200">

		<a href="userProfile.php">My Profile</a>
		<a href="viewMyFriends.php" class="friends-link">
			My Friends
			<?php if ($pendingCount > 0): ?>
				<span class="friend-badge"><?= $pendingCount ?></span>
			<?php endif; ?>
		</a>
		<a href="my_groups.php">My Groups</a>
		<hr class="text-gray-200">
		<a href="Logout.php">Logout</a>
	</nav>
</aside>

<script>
// Poll unread messages count and update the Messages badge without reload
(function(){
	function updateBadge(){
		fetch('api/get_unread_count.php', { credentials: 'same-origin' })
			.then(r => r.json())
			.then(data => {
				const cnt = data && data.unread ? parseInt(data.unread,10) : 0;
				const link = document.querySelector('.messages-link');
				if (!link) return;
				let badge = link.querySelector('.friend-badge');
				if (cnt > 0) {
					if (!badge) {
						badge = document.createElement('span');
						badge.className = 'friend-badge';
						link.appendChild(badge);
					}
					badge.textContent = String(cnt);
				} else {
					if (badge) badge.remove();
				}
			}).catch(()=>{});
	}
	// initial
	try { updateBadge(); setInterval(updateBadge, 10000); } catch(e){}
})();
</script>

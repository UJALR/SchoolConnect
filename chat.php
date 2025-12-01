<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
protectPage();
$userId = getUserId();
$friends = getUserFriends($userId);
?>
<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/feed.css">
<div class="feed-wrapper">
    <?php include 'includes/left_panel_partial.php'; ?>
    <div class="feed-main">
        <h1 class="page-title">Chat</h1>
        <div class="chat-container" style="display:flex; gap:18px;">
            <div class="chat-list" style="width:280px;">
                <h3>Friends</h3>
                <div id="friendsList">
                    <?php foreach ($friends as $f): ?>
                        <div class="chat-friend" data-user-id="<?= $f['user_id'] ?>" style="padding:8px;border-radius:8px;display:flex;align-items:center;gap:8px;cursor:pointer;border:1px solid #eee;margin-bottom:8px;">
                            <img src="<?= htmlspecialchars($f['profile_picture']?: 'assets/images/default-avatar.png') ?>" style="width:40px;height:40px;border-radius:50%;">
                            <div>
                                <div style="font-weight:600"><?= htmlspecialchars($f['full_name']) ?></div>
                                <div style="font-size:0.9rem;color:#666">@<?= htmlspecialchars($f['username']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chat-panel" style="flex:1;display:flex;flex-direction:column;">
                <div id="chatHeader" style="padding:12px;border-bottom:1px solid #eee;">Select a friend to chat</div>
                <div id="chatMessages" style="flex:1;overflow:auto;padding:12px;background:#fafafa"></div>
                <form id="chatForm" style="display:flex;gap:8px;padding:12px;border-top:1px solid #eee;">
                    <input type="hidden" id="chatToId" name="to_id" value="">
                    <!-- disabled until a friend is selected -->
                    <input id="chatInput" type="text" placeholder="Select a friend to start typing..." style="flex:1;padding:10px;border:1px solid #ddd;border-radius:6px;" disabled>
                    <!-- type="button" prevents accidental full-form submit if JS misbehaves -->
                    <button id="chatSend" type="button" class="btn-primary" disabled>Send</button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'includes/right_panel_partial.php'; ?>
</div>

<script>
// expose current user id for client JS
window.SC_CURRENT_USER = <?= json_encode($userId) ?>;
</script>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script src="assets/js/chat.js"></script>

<?php include 'includes/footer.php'; ?>

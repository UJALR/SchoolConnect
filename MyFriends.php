<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

protectPage();

$userId = getUserId();

// Handle friend actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle defriend
    if (isset($_POST['defriend']) && !empty($_POST['friends'])) {
        foreach ($_POST['friends'] as $friendId) {
            $friendId = sanitizeInput($friendId, $conn);
            
            // Delete friendship records in both directions
            $delete1 = $conn->prepare("DELETE FROM Friendship 
                                     WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
            $delete1->bind_param("ss", $userId, $friendId);
            $delete1->execute();
            
            $delete2 = $conn->prepare("DELETE FROM Friendship 
                                     WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
            $delete2->bind_param("ss", $friendId, $userId);
            $delete2->execute();
        }
    }
    
    // Handle friend request responses
    if (isset($_POST['accept_friends'])) {
        foreach ($_POST['friend_requests'] as $requesterId) {
            $requesterId = sanitizeInput($requesterId, $conn);
            
            // Update status to accepted
            $update = $conn->prepare("UPDATE Friendship SET Status = 'accepted' 
                                    WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
            $update->bind_param("ss", $requesterId, $userId);
            $update->execute();
            
            // Create reciprocal friendship record
            $insert = $conn->prepare("INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status)
                                    VALUES (?, ?, 'accepted')");
            $insert->bind_param("ss", $userId, $requesterId);
            $insert->execute();
        }
    } elseif (isset($_POST['deny_friends'])) {
        foreach ($_POST['friend_requests'] as $requesterId) {
            $requesterId = sanitizeInput($requesterId, $conn);
            
            // Delete friend request
            $delete = $conn->prepare("DELETE FROM Friendship 
                                    WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
            $delete->bind_param("ss", $requesterId, $userId);
            $delete->execute();
        }
    }
    
    // Refresh page after actions
    header("Location: MyFriends.php");
    exit();
}

// Get current friends
$friends = $conn->prepare("
    SELECT u.UserId, u.Name, COUNT(a.Album_Id) as SharedAlbums
    FROM Friendship f
    JOIN User u ON f.Friend_RequesteeId = u.UserId
    LEFT JOIN Album a ON f.Friend_RequesteeId = a.Owner_id AND a.Accessibility_code = 'shared'
    WHERE f.Friend_RequesterId = ? AND f.Status = 'accepted'
    GROUP BY u.UserId, u.Name
    UNION
    SELECT u.UserId, u.Name, COUNT(a.Album_Id) as SharedAlbums
    FROM Friendship f
    JOIN User u ON f.Friend_RequesterId = u.UserId
    LEFT JOIN Album a ON f.Friend_RequesterId = a.Owner_id AND a.Accessibility_code = 'shared'
    WHERE f.Friend_RequesteeId = ? AND f.Status = 'accepted'
    GROUP BY u.UserId, u.Name
");
$friends->bind_param("ss", $userId, $userId);
$friends->execute();
$friendsResult = $friends->get_result();

// Get pending friend requests
$requests = $conn->prepare("
    SELECT u.UserId, u.Name 
    FROM Friendship f
    JOIN User u ON f.Friend_RequesterId = u.UserId
    WHERE f.Friend_RequesteeId = ? AND f.Status = 'pending'
");
$requests->bind_param("s", $userId);
$requests->execute();
$requestsResult = $requests->get_result();
?>

<?php require_once 'includes/header.php'; ?>
    <h1>My Friends</h1>
    <p><a href="AddFriend.php" class="btn">Add Friends</a></p>
    
    <form method="post">
        <h2>Friends:</h2>
        <?php if ($friendsResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shared Albums</th>
                        <th>Unfriend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($friend = $friendsResult->fetch_assoc()): ?>
                        <tr>
                            <td><a href="FriendPictures.php?friend=<?php echo $friend['UserId']; ?>"><?php echo $friend['Name']; ?></a></td>
                            <td><?php echo $friend['SharedAlbums']; ?></td>
                            <td><input type="checkbox" name="friends[]" value="<?php echo $friend['UserId']; ?>"></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit" name="Unfriend" onclick="return confirm('Are you sure you want to defriend the selected friends?')">Unfriend Selected</button>
        <?php else: ?>
            <p>You have no friends yet. <a href="AddFriend.php">Add some friends</a>.</p>
        <?php endif; ?>
        
        <h2>Friend Requests:</h2>
        <?php if ($requestsResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Accept or Deny</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $requestsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $request['Name']; ?></td>
                            <td><input type="checkbox" name="friend_requests[]" value="<?php echo $request['UserId']; ?>"></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit" name="accept_friends">Accept Selected</button>
            <button type="submit" name="deny_friends">Deny Selected</button>
        <?php else: ?>
            <p>You have no pending friend requests.</p>
        <?php endif; ?>
    </form>
<?php require_once 'includes/footer.php'; ?>
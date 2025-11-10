<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

protectPage();

$userId = getUserId();
$message = '';
$error = '';

// 1. FIRST verify FriendshipStatus table has required values
$statusCheck = $conn->query("SELECT Status_Code FROM FriendshipStatus");
$validStatuses = [];
while ($row = $statusCheck->fetch_assoc()) {
    $validStatuses[] = $row['Status_Code'];
}

// If table is empty, insert default values
if (empty($validStatuses)) {
    $conn->query("INSERT IGNORE INTO FriendshipStatus (Status_Code, Description) VALUES 
        ('pending', 'Friend request pending'),
        ('accepted', 'Friend request accepted'),
        ('denied', 'Friend request denied')");
    $validStatuses = ['pending', 'accepted', 'denied'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $friendId = sanitizeInput($_POST['friend_id'], $conn);
    
    if (empty($friendId)) {
        $error = "Please enter a user ID.";
    } elseif ($friendId === $userId) {
        $error = "You cannot send a friend request to yourself.";
    } else {
        // Check if user exists
        $checkUser = $conn->prepare("SELECT UserId, Name FROM User WHERE UserId = ?");
        $checkUser->bind_param("s", $friendId);
        $checkUser->execute();
        $checkUser->store_result();
        
        if ($checkUser->num_rows === 0) {
            $error = "User not found.";
        } else {
            $checkUser->bind_result($friendUserId, $friendName);
            $checkUser->fetch();
            
            // Check existing friendship status
            $existingStatus = checkFriendship($userId, $friendId, $conn);
            
            if ($existingStatus === 'accepted') {
                $message = "You and $friendName are already friends.";
            } elseif ($existingStatus === 'pending') {
                // Check reciprocal request
                $checkReciprocal = $conn->prepare("SELECT 1 FROM Friendship 
                                                 WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
                $checkReciprocal->bind_param("ss", $friendId, $userId);
                $checkReciprocal->execute();
                $checkReciprocal->store_result();
                
                if ($checkReciprocal->num_rows > 0) {
                    // Both users sent requests - make them friends
                    $conn->begin_transaction();
                    try {
                        // Update existing request
                        $update1 = $conn->prepare("UPDATE Friendship SET Status = 'accepted' 
                                                 WHERE Friend_RequesterId = ? AND Friend_RequesteeId = ?");
                        $update1->bind_param("ss", $friendId, $userId);
                        $update1->execute();
                        
                        // Create reciprocal record
                        $insert = $conn->prepare("INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status)
                                                VALUES (?, ?, 'accepted')");
                        $insert->bind_param("ss", $userId, $friendId);
                        $insert->execute();
                        
                        $conn->commit();
                        $message = "You and $friendName are now friends!";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "Failed to establish friendship: " . $e->getMessage();
                    }
                } else {
                    $message = "You already have a pending friend request with $friendName.";
                }
            } else {
                // No existing relationship - create new request
                $insert = $conn->prepare("INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status)
                                         VALUES (?, ?, 'pending')");
                $insert->bind_param("ss", $userId, $friendId);
                
                if ($insert->execute()) {
                    $message = "Your request has been sent to $friendName (ID: $friendId). Once $friendName accepts your request, you will be friends and be able to view each other's shared albums.";
                } else {
                    $error = "Failed to send friend request. Please try again.";
                }
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>
    <h1>Add Friend</h1>
    <p>Welcome <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (not you? <a href="Logout.php">change user</a>)</p>
    
    <?php if (!empty($message)): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <p>Enter the ID of the user you want to be friend with</p>
        <div>
            <label for="friend_id">ID:</label>
            <input type="text" id="friend_id" name="friend_id" required>
        </div>
        <div>
            <button type="submit">Send Friend Request</button>
        </div>
    </form>
<?php require_once 'includes/footer.php'; ?>
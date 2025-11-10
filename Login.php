<?php 
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: MyAlbums.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = sanitizeInput($_POST['user_id'], $conn);
    $password = sanitizeInput($_POST['password'], $conn);
    
    $stmt = $conn->prepare("SELECT UserId, Name, Password FROM User WHERE UserId = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (verifyPassword($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserId'];
            $_SESSION['user_name'] = $user['Name'];
            
            $redirect_url = $_SESSION['redirect_url'] ?? 'MyAlbums.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect_url");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
    <div class="auth-container">
        <h1>Log In</h1>
        <p>You need to <a href="NewUser.php">sign up</a> if you are a new user</p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="user_id">User ID:</label>
                <input type="text" id="user_id" name="user_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Submit</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
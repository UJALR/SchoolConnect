<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    header("Location: Posts.php");
    exit();
}

$error = '';
$db = Database::getInstance();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collegeEmail = trim($_POST['college_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // echo "<script>alert('hi');</script>";
    // exit();

    if (empty($collegeEmail) || empty($password)) {
        $error = "Please enter your college email and password.";
    }
    
    if (empty($error)) {
        try {
            $sql = "SELECT user_id, username, full_name, password_hash FROM Users 
                    WHERE college_email = :email";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $collegeEmail]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (verifyPassword($password, $user['password_hash'])) {
                    

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    $redirect_url = $_SESSION['redirect_url'] ?? 'Posts.php';
                    unset($_SESSION['redirect_url']);
                    header("Location: $redirect_url");
                    exit();
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "User not found or Invalid college email."; 
            }
        } catch (PDOException $e) {
            $error = "An unexpected database error occurred. Please try again.";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>
    <div class="auth-container">
        <h1>Log In</h1>
        <p>You need to <a href="NewUser.php">sign up</a> if you are a new user</p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="college_email">College Email:</label>
                <input type="email" id="college_email" name="college_email" required>
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
<?php require_once 'includes/footer.php'; ?>
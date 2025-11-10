<?php 
require_once 'includes/config.php';
require_once 'includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = sanitizeInput($_POST['user_id'], $conn);
    $name = sanitizeInput($_POST['name'], $conn);
    $phone = sanitizeInput($_POST['phone'], $conn);
    $password = sanitizeInput($_POST['password'], $conn);
    $confirmPassword = sanitizeInput($_POST['confirm_password'], $conn);
    
    // Validation
    if (empty($userId)) {
        $errors[] = "User ID is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9]{4,16}$/', $userId)) {
        $errors[] = "User ID must be 4-16 alphanumeric characters.";
    }
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone must be 10 digits.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if user exists
    $checkUser = $conn->prepare("SELECT UserId FROM User WHERE UserId = ?");
    $checkUser->bind_param("s", $userId);
    $checkUser->execute();
    $checkUser->store_result();
    
    if ($checkUser->num_rows > 0) {
        $errors[] = "User ID already exists.";
    }
    
    if (empty($errors)) {
        $hashedPassword = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO User (UserId, Name, Phone, Password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $name, $phone, $hashedPassword);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            header("Location: MyAlbums.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>
    <div class="auth-container">
        <h1>New User Registration</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="user_id">User ID:</label>
                <input type="text" id="user_id" name="user_id" required>
                <small>4-16 alphanumeric characters</small>
            </div>
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone">
                <small>10 digits only</small>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <small>At least 6 characters</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Register</button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
<?php require_once 'includes/footer.php'; ?>
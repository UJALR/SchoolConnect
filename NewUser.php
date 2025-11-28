<?php
require_once './includes/database.php';
require_once 'includes/auth.php';

if (session_status() == PHP_SESSION_NONE)
{
    session_start();
}

$errors = [];
$db = Database::getInstance();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $collegeEmail = trim($_POST['college_email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($collegeEmail))
    {
        $errors[] = "College Email is required.";
    }
    elseif (!filter_var($collegeEmail, FILTER_VALIDATE_EMAIL))
    {
        $errors[] = "Invalid email format.";
    }

    if (empty($username))
    {
        $errors[] = "Username is required.";
    }
    elseif (!preg_match('/^[a-zA-Z0-9]{4,50}$/', $username))
    {
        $errors[] = "Username must be 4-50 alphanumeric characters.";
    }

    if (empty($fullName))
    {
        $errors[] = "Full Name is required.";
    }

    if (empty($password))
    {
        $errors[] = "Password is required.";
    }
    elseif (strlen($password) < 6)
    {
        $errors[] = "Password must be at least 6 characters.";
    }
    elseif ($password !== $confirmPassword)
    {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors))
    {
        try
        {
            $checkUserStmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username OR college_email = :email");
            $checkUserStmt->execute([':username' => $username, ':email' => $collegeEmail]);
            $existingUser = $checkUserStmt->fetch();

            if ($existingUser)
            {
                $checkUsernameStmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = :username");
                $checkUsernameStmt->execute([':username' => $username]);
                if ($checkUsernameStmt->fetch())
                {
                    $errors[] = "Username already exists.";
                }

                $checkEmailStmt = $pdo->prepare("SELECT user_id FROM Users WHERE college_email = :email");
                $checkEmailStmt->execute([':email' => $collegeEmail]);
                if ($checkEmailStmt->fetch())
                {
                    $errors[] = "College Email already exists.";
                }
            }
        }
        catch (PDOException $e)
        {
            $errors[] = "Database error during check: " . $e->getMessage();
        }
    }

    if (empty($errors))
    {
        try
        {
            $hashedPassword = hashPassword($password);

            $sql = "INSERT INTO Users (college_email, password_hash, username, full_name) 
                    VALUES (:email, :password_hash, :username, :full_name)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':email' => $collegeEmail,
                ':password_hash' => $hashedPassword,
                ':username' => $username,
                ':full_name' => $fullName
            ]);

            $newUserId = $pdo->lastInsertId();

            if ($newUserId)
            {
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $fullName;

                header("Location: Posts.php");
                exit();
            }
            else
            {
                $errors[] = "Registration failed: Could not retrieve new user ID.";
            }
        }
        catch (PDOException $e)
        {
            $errors[] = "Registration failed. Please try again. (DB Error: " . $e->getMessage() . ")";
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
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
        <div class="form-group">
            <label for="college_email">College Email:</label>
            <input type="email" id="college_email" name="college_email" required
                value="<?php echo htmlspecialchars($collegeEmail ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required
                value="<?php echo htmlspecialchars($username ?? ''); ?>">
            <small>4-50 alphanumeric characters</small>
        </div>

        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required
                value="<?php echo htmlspecialchars($fullName ?? ''); ?>">
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
<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
protectPage();

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$userId = getUserId();
$error = '';

// 1. FIRST verify the Accessibility table contents
$accessibilityCheck = $conn->query("SELECT Accessibility_Code FROM Accessibility");
$validCodes = [];
while ($row = $accessibilityCheck->fetch_assoc()) {
    $validCodes[] = $row['Accessibility_Code'];
}

// If table is empty, use these DEFAULT values that MUST exist in your database
if (empty($validCodes)) {
    $validCodes = ['private', 'shared'];
    // Emergency insert (only runs if table is empty)
    $conn->query("INSERT IGNORE INTO Accessibility (Accessibility_Code, Description) VALUES 
        ('private', 'Accessible only by owner'),
        ('shared', 'Accessible by friends')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $accessibility = $_POST['accessibility']; // Raw value for validation
    $description = trim($_POST['description']);
    
    // 2. STRICT validation against database values
    if (!in_array($accessibility, $validCodes)) {
        $error = "Invalid accessibility option selected. Please choose from: " . implode(', ', $validCodes);
    } else {
        // 3. TRANSACTION-BASED insert with error handling
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO Album 
                (Title, Description, Date_Updated, Owner_id, Accessibility_code) 
                VALUES (?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssss", 
                $title,
                $description,
                date('Y-m-d'),
                $userId,
                $accessibility
            );
            
            if ($stmt->execute()) {
                $conn->commit();
                header("Location: MyAlbums.php");
                exit();
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Database error: " . $e->getMessage();
            
            // SPECIAL CASE: If foreign key fails despite validation
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $error .= "<br><strong>System Error:</strong> The accessibility option you selected doesn't exist in the database. 
                          Please contact support.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Album</title>
    <?php require_once 'includes/header.php'; ?>
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
         /* ===== MODERN FORM-CONTROL SELECT STYLING ===== */
select.form-control {
  /* Base styling */
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  color: var(--white);
  background-color: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  transition: all 0.3s ease;
  
  /* Remove default styling */
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  
  /* Custom arrow */
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ffffff' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  background-size: 12px;
  padding-right: 40px;
  
  /* Focus state */
  &:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    background-color: rgba(255, 255, 255, 0.15);
  }
  
  /* Hover state */
  &:hover {
    border-color: rgba(255, 255, 255, 0.5);
  }
}

/* Dropdown options styling */
select.form-control option {
  padding: 12px;
  background: var(--secondary-color);
  color: var(--white);
  
  &:hover {
    background: var(--accent-color);
  }
}

/* Firefox specific fixes */
@-moz-document url-prefix() {
  select.form-control {
    padding-right: 32px;
    text-indent: 0.01px;
    text-overflow: '';
  }
}

/* IE11+ specific fixes */
select.form-control::-ms-expand {
  display: none;
}

/* ===== FORM GROUP ENHANCEMENTS ===== */
.form-group {
  margin-bottom: 24px;
  
  label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--white);
    font-size: 15px;
    
    &.required:after {
      content: " *";
      color: var(--accent-color);
    }
  }
}

/* ===== BUTTON ENHANCEMENTS ===== */
.btn {
  padding: 12px 24px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  &:active {
    transform: translateY(0);
  }
}

.btn-primary {
  background: var(--accent-color);
  color: var(--white);
  
  &:hover {
    background: #d97706;
  }
}

.btn-secondary {
  background: transparent;
  border: 2px solid var(--white);
  color: var(--white);
  
  &:hover {
    background: var(--white);
    color: var(--secondary-color);
  }
}

/* ===== FORM LAYOUT IMPROVEMENTS ===== */
.album-form-container {
  max-width: 600px;
  margin: 40px auto;
  padding: 40px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  
  h1 {
    margin-bottom: 32px;
    color: var(--white);
    text-align: center;
  }
}

.form-actions {
  display: flex;
  gap: 16px;
  margin-top: 32px;
  
  .btn {
    flex: 1;
  }
}

/* ===== RESPONSIVE ADJUSTMENTS ===== */
@media (max-width: 768px) {
  .album-form-container {
    padding: 32px 24px;
    margin: 24px 16px;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
    
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New Album</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>Title*</label>
                <input type="text" name="title" class="form-control" required 
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Accessibility*</label>
                <select name="accessibility" class="form-control" required>
                    <?php foreach ($validCodes as $code): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>"
                            <?php if (isset($_POST['accessibility']) && $_POST['accessibility'] === $code) echo 'selected'; ?>>
                            <?php echo htmlspecialchars(ucfirst($code)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"><?php 
                    echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Create Album</button>
                <a href="MyAlbums.php" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

protectPage();

// 1. ENSURE UPLOADS DIRECTORY EXISTS
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die("Failed to create uploads directory");
    }
}

// 2. VERIFY DIRECTORY PERMISSIONS
if (!is_writable($uploadDir)) {
    die("Upload directory is not writable. Please set permissions to 0755");
}

$userId = getUserId();
$error = '';
$success = '';

// Get user's albums
$albums = $conn->prepare("SELECT Album_Id, Title FROM Album WHERE Owner_id = ?");
$albums->bind_param("s", $userId);
$albums->execute();
$albumResult = $albums->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $albumId = (int)$_POST['album_id'];
    $title = sanitizeInput($_POST['title'], $conn);
    $description = sanitizeInput($_POST['description'], $conn);
    
    // Verify album ownership
    $checkAlbum = $conn->prepare("SELECT Album_Id FROM Album WHERE Album_Id = ? AND Owner_id = ?");
    $checkAlbum->bind_param("is", $albumId, $userId);
    $checkAlbum->execute();
    $checkAlbum->store_result();
    
    if ($checkAlbum->num_rows === 0) {
        $error = "Invalid album selected.";
    } elseif (empty($_FILES['pictures']['name'][0])) {
        $error = "Please select at least one picture to upload.";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $uploadSuccess = true;
        
        foreach ($_FILES['pictures']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['pictures']['error'][$key] !== UPLOAD_ERR_OK) {
                $uploadSuccess = false;
                $error = "Error uploading file: " . $_FILES['pictures']['name'][$key];
                break;
            }
            
            // Verify file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowedTypes)) {
                $uploadSuccess = false;
                $error = "Invalid file type for: " . $_FILES['pictures']['name'][$key] . 
                         ". Only JPG, PNG, GIF are allowed.";
                break;
            }
            
            // Generate unique filename
            $ext = pathinfo($_FILES['pictures']['name'][$key], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . strtolower($ext);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $uploadPath)) {
                // Store date in variable first
                $dateAdded = date('Y-m-d');
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO Picture (Album_Id, FileName, Title, Description, Date_Added) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $albumId, $fileName, $title, $description, $dateAdded);
                
                if (!$stmt->execute()) {
                    $uploadSuccess = false;
                    $error = "Failed to save picture information to database.";
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                    break;
                }
                
                // Update album's date_updated (store date in variable first)
                $currentDate = date('Y-m-d');
                $updateAlbum = $conn->prepare("UPDATE Album SET Date_Updated = ? WHERE Album_Id = ?");
                $updateAlbum->bind_param("si", $currentDate, $albumId);
                $updateAlbum->execute();
            } else {
                $uploadSuccess = false;
                $error = "Failed to move uploaded file. Check directory permissions.";
                break;
            }
        }
        
        if ($uploadSuccess) {
            $success = "Pictures uploaded successfully!";
        }
    }
}
?>

<!-- Rest of your HTML remains exactly the same -->
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Pictures</title>
    <?php require_once 'includes/header.php'; ?>
    <style>
        .alert { padding: 15px; margin-bottom: 20px; }
        .alert-success { background-color: #dff0d8; color: #3c763d; }
        .alert-danger { background-color: #f2dede; color: #a94442; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Pictures</h1>
        <p>Accepted formats: JPG, PNG, GIF</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="album_id">Album:</label>
                <select id="album_id" name="album_id" class="form-control" required>
                    <?php while ($album = $albumResult->fetch_assoc()): ?>
                        <option value="<?php echo $album['Album_Id']; ?>">
                            <?php echo $album['Title']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="pictures">Select Pictures:</label>
                <input type="file" id="pictures" name="pictures[]" multiple 
                       accept="image/jpeg,image/png,image/gif" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" class="form-control"></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Upload</button>
                <button type="reset" class="btn btn-default">Clear</button>
            </div>
        </form>
    </div>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
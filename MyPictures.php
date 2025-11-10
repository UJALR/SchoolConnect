<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

protectPage();

$userId = getUserId();

// Get selected album (default to first album if not specified)
$selectedAlbumId = isset($_GET['album']) ? (int)$_GET['album'] : null;
$selectedPictureId = isset($_GET['picture']) ? (int)$_GET['picture'] : null;

// Get user's albums for dropdown
$albums = $conn->prepare("SELECT Album_Id, Title FROM Album WHERE Owner_id = ?");
$albums->bind_param("s", $userId);
$albums->execute();
$albumResult = $albums->get_result();

// If no album selected and user has albums, select the first one
if (!$selectedAlbumId && $albumResult->num_rows > 0) {
    $firstAlbum = $albumResult->fetch_assoc();
    $selectedAlbumId = $firstAlbum['Album_Id'];
    $albumResult->data_seek(0); // Reset pointer
}

// Get pictures for selected album
$pictures = [];
if ($selectedAlbumId) {
    $picturesQuery = $conn->prepare("
        SELECT Picture_Id, Title, Description, FileName, Date_Added 
        FROM Picture 
        WHERE Album_Id = ? 
        ORDER BY Date_Added DESC
    ");
    $picturesQuery->bind_param("i", $selectedAlbumId);
    $picturesQuery->execute();
    $pictures = $picturesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no picture selected and there are pictures, select the first one
    if (!$selectedPictureId && count($pictures) > 0) {
        $selectedPictureId = $pictures[0]['Picture_Id'];
    }
} else {
    // If no album is selected, ensure no picture is selected either
    $selectedPictureId = null;
}

// Get selected picture details
$selectedPicture = null;
if ($selectedPictureId) {
    foreach ($pictures as $picture) {
        if ($picture['Picture_Id'] == $selectedPictureId) {
            $selectedPicture = $picture;
            break;
        }
    }
}

// Get comments for selected picture
$comments = [];
if ($selectedPictureId) {
    $commentsQuery = $conn->prepare("
        SELECT c.Comment_Text, c.Date, u.Name, u.UserId 
        FROM Comment c 
        JOIN User u ON c.Author_id = u.UserId 
        WHERE c.Picture_Id = ? 
        ORDER BY c.Date DESC
    ");
    $commentsQuery->bind_param("i", $selectedPictureId);
    $commentsQuery->execute();
    $comments = $commentsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && $selectedPictureId) {
    $commentText = sanitizeInput($_POST['comment_text'], $conn);
    $authorId = $userId;
    
    $insertComment = $conn->prepare("
        INSERT INTO Comment (Author_id, Picture_Id, Comment_Text) 
        VALUES (?, ?, ?)
    ");
    $insertComment->bind_param("sis", $authorId, $selectedPictureId, $commentText);
    $insertComment->execute();
    
    // Refresh page to show new comment
    header("Location: MyPictures.php?album=$selectedAlbumId&picture=$selectedPictureId");
    exit();
}
?>

<?php require_once 'includes/header.php'; ?>
    <h1>My Pictures</h1>
    
    <div class="album-selector">
        <form method="get">
            <label for="album">Select Album:</label>
            <select id="album" name="album" onchange="this.form.submit()">
                <?php while ($album = $albumResult->fetch_assoc()): ?>
                    <option value="<?php echo $album['Album_Id']; ?>" 
                        <?php if ($album['Album_Id'] == $selectedAlbumId) echo 'selected'; ?>>
                        <?php echo $album['Title']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
    
    <?php if ($selectedAlbumId && count($pictures) > 0): ?>
        <div class="picture-container">
            <div class="main-picture">
                <?php if ($selectedPicture): ?>
                    <h2><?php echo $selectedPicture['Title']; ?></h2>
                    <img src="uploads/<?php echo $selectedPicture['FileName']; ?>" alt="<?php echo $selectedPicture['Title']; ?>">
                    
                    <div class="picture-info">
                        <h3>Description:</h3>
                        <p><?php echo $selectedPicture['Description'] ?: 'No description available.'; ?></p>
                        
                        <h3>Comments:</h3>
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <strong><?php echo $comment['Name']; ?></strong> 
                                    (<?php echo date('Y-m-d H:i', strtotime($comment['Date'])); ?>):
                                    <p><?php echo $comment['Comment_Text']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>
                        
                        <form method="post">
                            <h3>Add Comment:</h3>
                            <textarea name="comment_text" required></textarea>
                            <button type="submit">Add Comment</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="thumbnail-bar">
                <?php foreach ($pictures as $picture): ?>
                    <a href="MyPictures.php?album=<?php echo $selectedAlbumId; ?>&picture=<?php echo $picture['Picture_Id']; ?>">
                        <img src="uploads/<?php echo $picture['FileName']; ?>" 
                             alt="<?php echo $picture['Title']; ?>"
                             class="<?php if ($picture['Picture_Id'] == $selectedPictureId) echo 'selected'; ?>">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($selectedAlbumId && count($pictures) === 0): ?>
        <p>This album has no pictures yet. <a href="UploadPictures.php">Upload some pictures</a>.</p>
    <?php else: ?>
        <p>You have no albums yet. <a href="AddAlbum.php">Create an album</a> first.</p>
    <?php endif; ?>
<?php require_once 'includes/footer.php'; ?>

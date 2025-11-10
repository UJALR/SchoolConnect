<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

protectPage();

$userId = getUserId();

// Handle album accessibility update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accessibility'])) {
    foreach ($_POST['accessibility'] as $albumId => $accessibilityCode) {
        $stmt = $conn->prepare("UPDATE Album SET Accessibility_code = ? WHERE Album_Id = ? AND Owner_id = ?");
        $stmt->bind_param("sis", $accessibilityCode, $albumId, $userId);
        $stmt->execute();
    }
    header("Location: MyAlbums.php");
    exit();
}

// Handle album deletion
if (isset($_GET['delete'])) {
    $albumId = (int)$_GET['delete'];
    
    // Verify ownership before deletion
    $checkOwner = $conn->prepare("SELECT Album_Id FROM Album WHERE Album_Id = ? AND Owner_id = ?");
    $checkOwner->bind_param("is", $albumId, $userId);
    $checkOwner->execute();
    $checkOwner->store_result();
    
    if ($checkOwner->num_rows === 1) {
        // Delete pictures first
        $deletePictures = $conn->prepare("DELETE FROM Picture WHERE Album_Id = ?");
        $deletePictures->bind_param("i", $albumId);
        $deletePictures->execute();
        
        // Then delete the album
        $deleteAlbum = $conn->prepare("DELETE FROM Album WHERE Album_Id = ?");
        $deleteAlbum->bind_param("i", $albumId);
        $deleteAlbum->execute();
    }
    
    header("Location: MyAlbums.php");
    exit();
}

// Get user's albums
$albums = $conn->prepare("
    SELECT a.Album_Id, a.Title, a.Date_Updated, a.Accessibility_code, 
           COUNT(p.Picture_Id) as PictureCount, ac.Description as AccessibilityDesc
    FROM Album a
    LEFT JOIN Picture p ON a.Album_Id = p.Album_Id
    JOIN Accessibility ac ON a.Accessibility_code = ac.Accessibility_Code
    WHERE a.Owner_id = ?
    GROUP BY a.Album_Id
    ORDER BY a.Date_Updated DESC
");
$albums->bind_param("s", $userId);
$albums->execute();
$albumsResult = $albums->get_result();

// Get accessibility options for dropdown
$accessibilityOptions = $conn->query("SELECT * FROM Accessibility");
?>

<?php require_once 'includes/header.php'; ?>
    <h1>My Albums</h1>
    <p><a href="AddAlbum.php" class="btn">Create a New Album</a></p>
    
    <form method="post">
        <?php if ($albumsResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date Updated</th>
                        <th>Number of Pictures</th>
                        <th>Accessibility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($album = $albumsResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="MyPictures.php?album=<?php echo $album['Album_Id']; ?>">
                                    <?php echo $album['Title']; ?>
                                </a>
                            </td>
                            <td><?php echo $album['Date_Updated']; ?></td>
                            <td><?php echo $album['PictureCount']; ?></td>
                            <td>
                                <select name="accessibility[<?php echo $album['Album_Id']; ?>]">
                                    <?php $accessibilityOptions->data_seek(0); ?>
                                    <?php while ($option = $accessibilityOptions->fetch_assoc()): ?>
                                        <option value="<?php echo $option['Accessibility_Code']; ?>" 
                                            <?php if ($option['Accessibility_Code'] == $album['Accessibility_code']) echo 'selected'; ?>>
                                            <?php echo $option['Description']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <a href="MyAlbums.php?delete=<?php echo $album['Album_Id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this album and all its pictures?')"
                                   style="color: var(--accent-color);">
                                    DELETE
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit">Save Changes</button>
        <?php else: ?>
            <p>You have no albums yet. <a href="AddAlbum.php">Create your first album</a>.</p>
        <?php endif; ?>
    </form>
<?php require_once 'includes/footer.php'; ?>
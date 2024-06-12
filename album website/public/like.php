<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Check if the ID is specified in the URL
if (isset($_GET['id'])) {
    // User must be authenticated
    if (!isset($_SESSION['account_loggedin'])) {
        exit('Please login to like this media!');
    }
    // Retrieve the media from the database
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media) {
        exit('Media no longer exists!');
    }
    // Check whether the user liked the media or not
    $stmt = $pdo->prepare('SELECT * FROM media_likes WHERE media_id = ? AND acc_id = ?');
    $stmt->execute([ $_GET['id'], $_SESSION['account_id'] ]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        // User liked the media, so unlike
        $stmt = $pdo->prepare('DELETE FROM media_likes WHERE media_id = ? AND acc_id = ?');
        $stmt->execute([ $_GET['id'], $_SESSION['account_id'] ]);       
        exit('unlike');
    }  else {
        // Like media
        $stmt = $pdo->prepare('INSERT INTO media_likes (media_id,acc_id) VALUES (?,?)');
        $stmt->execute([ $_GET['id'], $_SESSION['account_id'] ]);
        exit('like');
    }
}
?>
<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Authentication required
if (!isset($_SESSION['account_loggedin'])) {
    exit('Please login!');
}
// Ensure GET params exist
if (isset($_POST['collection'], $_POST['media_id'])) {
    // Retrieve collection associated with the GET params and account ID
    $stmt = $pdo->prepare('SELECT * FROM collections WHERE title = ? AND acc_id = ?');
	$stmt->execute([ $_POST['collection'], $_SESSION['account_id'] ]);
	$collection = $stmt->fetch(PDO::FETCH_ASSOC);
    // Ensure collection exists
    if (!$collection) {
        exit('Invalid collection!');
    }
    // Retrieve media
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ?');
	$stmt->execute([ $_POST['media_id'] ]);
	$media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media) {
        exit('Invalid media!');
    }    
    // Retrieve media collection
    $stmt = $pdo->prepare('SELECT * FROM media_collections WHERE collection_id = ? AND media_id = ?');
	$stmt->execute([ $collection['id'], $media['id'] ]);
	$media_collection = $stmt->fetch(PDO::FETCH_ASSOC);
    // If media already added to collection, output message
    if ($media_collection) {
        exit('Media already added to collection!');
    }   
    // Add media to collection
    $stmt = $pdo->prepare('INSERT INTO media_collections (collection_id,media_id) VALUES (?,?)');
	$stmt->execute([ $collection['id'], $media['id'] ]);
    exit('Media added to collection!');
}
?>
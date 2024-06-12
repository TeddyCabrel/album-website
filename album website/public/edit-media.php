<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// User must be authenticated
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: collections.php');
    exit;
}
// Make sure GET ID param exists
if (isset($_GET['id'])) {
    // Retrieve media
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ? AND acc_id = ?');
	$stmt->execute([ $_GET['id'], $_SESSION['account_id'] ]);
	$media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media) {
        exit('Invalid ID!');
    }
    // Delete media if delete button triggered
    if (isset($_POST['delete'])) {
        // Delete file
        unlink($media['filepath']);  
        // Delete thumbnail
        if ($media['thumbnail']) {
           unlink($media['thumbnail']); 
        }  
        // Delete all records associated with the media in all tables
        $stmt = $pdo->prepare('DELETE m, mc, ml FROM media m LEFT JOIN media_collections mc ON mc.media_id = m.id LEFT JOIN media_likes ml ON ml.media_id = m.id WHERE m.id = ?');
        $stmt->execute([ $_GET['id'] ]);    
        // Redirect to collections page
        header('Location: collections.php');
        exit;         
    }
    // Update media
    if (isset($_POST['title'], $_POST['description'], $_POST['public'])) {
        $stmt = $pdo->prepare('UPDATE media SET title = ?, description = ?, public = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['public'], $_GET['id'] ]);
        header('Location: collections.php');
        exit; 
    }
} else {
    exit('Invalid ID!');
}
?>
<?=template_header("Modification de l'image")?>

<div class="content edit-media">

    <div class="page-title">
		<h2>Modifier l'image</h2>
	</div>

	<form action="" method="post" class="gallery-form">

		<label for="title">Titre</label>
        <input id="title" name="title" type="text" placeholder="Collection Title..." value="<?=htmlspecialchars($media['title'], ENT_QUOTES)?>" required>

		<label for="Description">Description</label>
        <textarea id="title" name="description" placeholder="Collection Description..."><?=htmlspecialchars($media['description'], ENT_QUOTES)?></textarea>

        <label for="public">Who can view your media?</label>
        <select id="public" name="public" type="text" required>
            <option value="1"<?=$media['public']?' selected':''?>>Everyone</option> 
            <option value="0"<?=$media['public']?'':' selected'?>>Only Me</option>
        </select>

		<div class="btn_wrapper">
			<input type="submit" value="Submit" name="submit" class="btn">
            <input type="submit" value="Delete" name="delete" class="btn alt" onclick="return confirm('Are you sure you want to delete this media?')">
		</div>
		
	</form>

</div>

<?=template_footer()?>
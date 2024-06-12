<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Page title
$title = 'Create';
// User must be authenticated
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: collections.php');
    exit;
}
// Default values
$collection_defaults = [
    'title' => '',
    'description' => '',
    'public' => 0
];
// If GET ID exists, user is editing media
if (isset($_GET['id'])) {
    $title = 'Edit';
    // Get the collection details
    $stmt = $pdo->prepare('SELECT * FROM collections WHERE id = ? AND acc_id = ?');
	$stmt->execute([ $_GET['id'], $_SESSION['account_id'] ]);
	$collection_defaults = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$collection_defaults) {
        exit('Invalid collection!');
    }
    // Delete collection
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare('DELETE c, mc FROM collections c LEFT JOIN media_collections mc ON mc.collection_id = c.id WHERE c.id = ?');
        $stmt->execute([ $_GET['id'] ]);        
        header('Location: collections.php');
        exit;         
    }
    // Update collection
    if (isset($_POST['title'], $_POST['description'], $_POST['public'])) {
        $stmt = $pdo->prepare('UPDATE collections SET title = ?, description = ?, public = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['public'], $_GET['id'] ]);
        header('Location: collections.php');
        exit; 
    }
} else if (isset($_POST['title'], $_POST['description'], $_POST['public'])) {
    // Create new collection
    $stmt = $pdo->prepare('INSERT INTO collections (title,description,public,acc_id) VALUES (?,?,?,?)');
    $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['public'], $_SESSION['account_id'] ]);
    header('Location: collections.php');
    exit;    
}
?>
<?=template_header($title . ' Collection')?>

<div class="content">

	<div class="page-title">
		<h2><?=$title?> Albums</h2>
	</div>

	<form action="" method="post" class="gallery-form">

		<label for="title">Titre</label>
        <input id="title" name="title" type="text" placeholder="Titre de l'album" value="<?=htmlspecialchars($collection_defaults['title'], ENT_QUOTES)?>" pattern="[A-Za-z0-9 ]+" required>

		<label for="Description">Description</label>
        <textarea id="title" name="description" placeholder="Description de l'album"><?=htmlspecialchars($collection_defaults['description'], ENT_QUOTES)?></textarea>

        <label for="public">Visibilité</label>
        <select id="public" name="public" type="text" required>
            <option value="0"<?=$collection_defaults['public']?'':' selected'?>>Privé</option>
            <option value="1"<?=$collection_defaults['public']?' selected':''?>>Publique</option> 
        </select>

		<div class="btn_wrapper">
			<input type="submit" value="Soumettre" name="submit" class="btn">
            <?php if ($title == 'Edit'): ?>
            <input type="submit" value="Delete" name="delete" class="btn alt" onclick="return confirm('Are you sure you want to delete this collection?')">
            <?php endif; ?>
		</div>
		
	</form>

</div>

<?=template_footer()?>
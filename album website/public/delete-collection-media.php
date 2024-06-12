<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// User must be authenticated
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: collections.php');
    exit;
}
// Make sure GET params exist
if (isset($_GET['collection_id'], $_GET['media_id'])) {
    // Retrieve collection from the database
    $stmt = $pdo->prepare('SELECT mc.id FROM collections c JOIN media_collections mc ON mc.collection_id = c.id AND mc.media_id = ? WHERE c.id = ? AND c.acc_id = ?');
	$stmt->execute([ $_GET['media_id'], $_GET['collection_id'], $_SESSION['account_id'] ]);
	$collection = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$collection) {
        exit('Invalid ID!');
    }
    // Collection exists... Delete it
    $stmt = $pdo->prepare('DELETE FROM media_collections WHERE id = ?');
	$stmt->execute([ $collection['id'] ]);
    // Output response
    $msg = 'You have successfully removed the media from your collection!';
} else {
    exit('Invalid ID!');
}
?>
<?=template_header('Remove Media')?>

<div class="content edit-media">

    <div class="page-title">
		<h2>Remove Media</h2>
	</div>

	<p><?=$msg?></p>

</div>

<?=template_footer()?>
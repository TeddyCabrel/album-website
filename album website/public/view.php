<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Make sure the GET ID param exists
if (isset($_GET['id'])) {
    // Retrieve the media from the media table using the GET request ID (URL param)
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ? AND approved = 1');
    $stmt->execute([ $_GET['id'] ]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media) {
        exit('Media does not exist with this ID!');
    }
    // Check whether the media is public or private
    if (!$media['public']) {
        if (isset($_SESSION['account_id']) && $media['acc_id'] != $_SESSION['account_id'] && $_SESSION['account_role'] != 'Admin') {
            exit('Private media!');
        } else if (!isset($_SESSION['account_id'])) {
            exit('Private media!');
        }
    }
} else {
    exit('No ID specified!');
}
?>
<?=template_header(htmlspecialchars($media['title'], ENT_QUOTES))?>

<div class="content view">

    <div class="page-title">
        <h2><?=htmlspecialchars($media['title'], ENT_QUOTES)?></h2>
	</div>
	
	<p><?=htmlspecialchars($media['description'], ENT_QUOTES)?></p>

    <?php if ($media['type'] == 'image'): ?>
    <img src="<?=$media['filepath']?>" alt="<?=htmlspecialchars($media['description'], ENT_QUOTES)?>">
    <?php elseif ($media['type'] == 'video'): ?>
    <video src="<?=$media['filepath']?>" width="852" height="480" controls autoplay></video>
    <?php elseif ($media['type'] == 'audio'): ?>
    <audio src="<?=$media['filepath']?>" controls autoplay></audio>
    <?php endif; ?>

</div>

<?=template_footer()?>
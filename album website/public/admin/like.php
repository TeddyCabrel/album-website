<?php
include 'main.php';
// Retrieve media from the database
$stmt = $pdo->prepare('SELECT * FROM media');
$stmt->execute();
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve accounts from the database
$stmt = $pdo->prepare('SELECT * FROM accounts');
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Add a new media like
if (isset($_POST['submit'])) {
    $stmt = $pdo->prepare('INSERT INTO media_likes (media_id,acc_id) VALUES (?,?)');
    $stmt->execute([ $_POST['media_id'], $_POST['acc_id'] ]);
    header('Location: likes.php?success_msg=1');
    exit;
}
?>
<?=template_admin_header('Add Media Like', 'allmedia', 'likes')?>

<form action="" method="post" enctype="multipart/form-data">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100">Add Media Like</h2>
        <a href="likes.php" class="btn alt mar-right-2">Cancel</a>
        <input type="submit" name="submit" value="Add" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="media_id">Media</label>
            <select id="media_id" name="media_id" required>
                <?php foreach ($media as $m): ?>
                <option value="<?=$m['id']?>"><?=$m['id']?> - <?=htmlspecialchars($m['title'], ENT_QUOTES)?></option>
                <?php endforeach; ?>
            </select>            

            <label for="acc_id">Account</label>
            <select id="acc_id" name="acc_id" required>
                <?php foreach ($accounts as $a): ?>
                <option value="<?=$a['id']?>"><?=$a['id']?> - <?=$a['email']?></option>
                <?php endforeach; ?>
            </select>

        </div>

    </div>

</form>

<?=template_admin_footer()?>
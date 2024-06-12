<?php
include 'main.php';
// Default collection values
$collection = [
    'title' => '',
    'description' => '',
    'acc_id' => NULL,
    'public' => 1
];
// Retrieve accounts from the database
$stmt = $pdo->prepare('SELECT * FROM accounts');
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['id'])) {
    // Retrieve the collection from the database
    $stmt = $pdo->prepare('SELECT * FROM collections WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $collection = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing collection
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the collection
        $stmt = $pdo->prepare('UPDATE collections SET title = ?, description = ?, acc_id = ?, public = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['acc_id'], $_POST['public'], $_GET['id'] ]);
        header('Location: collections.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the collection
        $stmt = $pdo->prepare('DELETE c, ml FROM collections c LEFT JOIN media_collections ml ON ml.collection_id = c.id WHERE c.id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: collections.php?success_msg=3');
        exit;
    }
} else {
    // Create a new collection
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO collections (title,description,acc_id,public) VALUES (?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['acc_id'], $_POST['public'] ]);
        header('Location: collections.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' collection', 'collections', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Collection</h2>
        <a href="collections.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this collection?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="title"><i class="required">*</i> Title</label>
            <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($collection['title'], ENT_QUOTES)?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Description"><?=htmlspecialchars($collection['description'], ENT_QUOTES)?></textarea>

            <label for="acc_id">Account</label>
            <select id="acc_id" name="acc_id" required>
                <option value="NULL">(none)</option>
                <?php foreach ($accounts as $account): ?>
                <option value="<?=$account['id']?>"<?=$account['id']==$collection['acc_id']?' selected':''?>><?=$account['id']?> - <?=$account['email']?></option>
                <?php endforeach; ?>
            </select>

            <label for="public"><i class="required">*</i> Public</label>
            <select id="public" name="public" required>
                <option value="0"<?=$collection['public']==0?' selected':''?>>No</option>
                <option value="1"<?=$collection['public']==1?' selected':''?>>Yes</option>
            </select>

        </div>

    </div>

</form>

<?=template_admin_footer()?>
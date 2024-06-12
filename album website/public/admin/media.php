<?php
include 'main.php';
// disable the code execution time limit
set_time_limit(0);
// Disable the default upload file size limits
ini_set('post_max_size', '0');
ini_set('upload_max_filesize', '0');
// Default values
$media = [
    'title' => '',
    'description' => '',
    'uploaded_date' => date('Y-m-d H:i'),
    'type' => '',
    'thumbnail' => '',
    'filepath' => '',
    'approved' => 1,
    'public' => 1,
    'acc_id' => NULL
];
// Retrieve accounts from the database
$stmt = $pdo->prepare('SELECT * FROM accounts');
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve media
if (isset($_GET['id'])) {
    // Retrieve the media from the database
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
}
// Handle media upload
$media_id = md5(uniqid());
if (isset($_FILES['media']) && !empty($_FILES['media']['tmp_name'])) {
	$media['type'] = preg_match('/image\/*/',$_FILES['media']['type']) ? 'image' : $media['type'];
	$media['type'] = preg_match('/audio\/*/',$_FILES['media']['type']) ? 'audio' : $media['type'];
	$media['type'] = preg_match('/video\/*/',$_FILES['media']['type']) ? 'video' : $media['type'];
    $media_parts = explode('.', $_FILES['media']['name']);
    $ext = end($media_parts);
    $media['filepath'] = 'media/' . $media['type'] . 's/' . $media_id . '.' . $ext;
    move_uploaded_file($_FILES['media']['tmp_name'], '../' . $media['filepath']);
}
// Handle thumbnail upload
if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['tmp_name'])) {
    $media['thumbnail'] = 'media/thumbnails/' . $media_id . '.' . end(explode('.', $_FILES['thumbnail']['name']));
    move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../' . $media['thumbnail']);
}
if (isset($_GET['id'])) {
    // ID param exists, edit an existing media
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the media
        $stmt = $pdo->prepare('UPDATE media SET title = ?, description = ?, filepath = ?, uploaded_date = ?, type = ?, thumbnail = ?, approved = ?, public = ?, acc_id = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], $media['filepath'], date('Y-m-d H:i:s', strtotime($_POST['uploaded_date'])), $media['type'], $media['thumbnail'], $_POST['approved'], $_POST['public'], $_POST['acc_id'], $_GET['id'] ]);
        header('Location: allmedia.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the media
        header('Location: allmedia.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new media
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // convert svg to png
        if (convert_svg_to_png && strtolower($ext) == 'svg') {
            $media['filepath'] = convert_svg_to_png('../' . $media['filepath']);
            $media['filepath'] = str_replace('../', '', $media['filepath']);
        }
        // Compress image
        if (intval($_POST['image_quality']) < 100 && $media['type'] == 'image') {
            compress_image('../' . $media['filepath'], $_POST['image_quality']);
        }
        // Fix image orientation
        if (intval($_POST['correct_image_orientation']) && $media['type'] == 'image') {
            correct_image_orientation('../' . $media['filepath']);
        }
        // Resize image
        if (intval($_POST['image_max_width']) != -1 || intval($_POST['image_max_height']) != -1) {
            resize_image('../' . $media['filepath'], intval($_POST['image_max_width']), intval($_POST['image_max_height']));
		}
        // Insert media into database
        $stmt = $pdo->prepare('INSERT INTO media (title,description,filepath,uploaded_date,type,thumbnail,approved,public,acc_id) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['description'], $media['filepath'], date('Y-m-d H:i:s', strtotime($_POST['uploaded_date'])), $media['type'], $media['thumbnail'], $_POST['approved'], $_POST['public'], $_POST['acc_id'] ]);
        // Redirect
        header('Location: allmedia.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Media', 'allmedia', 'manage')?>

<form action="" method="post" enctype="multipart/form-data">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Media</h2>
        <a href="allmedia.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this media?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="title"><i class="required">*</i> Title</label>
            <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($media['title'], ENT_QUOTES)?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Description"><?=htmlspecialchars($media['description'], ENT_QUOTES)?></textarea>

            <?php if ($page != 'Edit'): ?>
            <label for="media"><i class="required">*</i> Media</label>
            
            <input type="file" name="media" accept="audio/*,video/*,image/*" required>

            <label for="thumbnail">Thumbnail</label>
            <input type="file" name="thumbnail" accept="image/*">
            <?php else: ?>
            <label for="media">Media</label>

            <div style="padding: 15px 0">
                <?php if ($media['type'] == 'image'): ?>
                <img src="../<?=$media['filepath']?>" alt="" style="max-width:250px;max-height:250px;">
                <?php elseif ($media['type'] == 'video'): ?>
                <video src="../<?=$media['filepath']?>" width="250" height="250" controls autoplay></video>
                <?php elseif ($media['type'] == 'audio'): ?>
                <audio src="../<?=$media['filepath']?>" controls autoplay></audio>
                <?php endif; ?>
                <div style="font-size:14px">(<?=convert_filesize(filesize('../' . $media['filepath']))?>)</div>
            </div>

            <input type="file" name="media" accept="audio/*,video/*,image/*">

            <label for="thumbnail">Thumbnail</label>

            <?php if ($media['thumbnail']): ?>
            <div style="padding: 15px 0">
                <img src="../<?=$media['thumbnail']?>" alt="" style="max-width:250px;max-height:250px;">
                <div style="font-size:14px">(<?=convert_filesize(filesize('../' . $media['thumbnail']))?>)</div>
            </div>
            <?php endif; ?>

            <input type="file" name="thumbnail" accept="image/*">
            <?php endif; ?>

            <label for="approved"><i class="required">*</i> Approved</label>
            <select id="approved" name="approved" required>
                <option value="0"<?=$media['approved']==0?' selected':''?>>No</option>
                <option value="1"<?=$media['approved']==1?' selected':''?>>Yes</option>
            </select>

            <label for="public"><i class="required">*</i> Public</label>
            <select id="public" name="public" required>
                <option value="0"<?=$media['public']==0?' selected':''?>>No</option>
                <option value="1"<?=$media['public']==1?' selected':''?>>Yes</option>
            </select>

            <label for="acc_id">Account</label>
            <select id="acc_id" name="acc_id" required>
                <option value="NULL">(none)</option>
                <?php foreach ($accounts as $account): ?>
                <option value="<?=$account['id']?>"<?=$account['id']==$media['acc_id']?' selected':''?>><?=$account['id']?> - <?=$account['email']?></option>
                <?php endforeach; ?>
            </select>

            <label for="uploaded_date">Uploaded Date</label>
            <input id="uploaded_date" type="datetime-local" name="uploaded_date" value="<?=date('Y-m-d\TH:i', strtotime($media['uploaded_date']))?>" required>

            <?php if ($page != 'Edit'): ?>
            <label for="correct_image_orientation">Correct Image Orientation?</label>
            <select id="correct_image_orientation" name="correct_image_orientation" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>

            <label for="image_quality">Image Quality %</label>
            <input id="image_quality" type="number" name="image_quality" placeholder="Image Quality" value="100" max="100" min="0" required>

            <label for="image_max_width">Image Max Width (px)</label>
            <input id="image_max_width" type="number" name="image_max_width" placeholder="Image Max Width" value="-1" required>

            <label for="image_max_height">Image Max Height (px)</label>
            <input id="image_max_height" type="number" name="image_max_height" placeholder="Image Max Height" value="-1" required>
            <?php endif; ?>

        </div>

    </div>

</form>

<?=template_admin_footer()?>
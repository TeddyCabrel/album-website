<?php
include 'functions.php';
// Remove the time limit for file uploads
set_time_limit(0);
// Output message
$msg = '';
// Check if authentication required
if (authentication_required && !isset($_SESSION['account_loggedin'])) {
	header('Location: collections.php');
	exit;
}
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Check if user has uploaded new media file
if (isset($_POST['total_files']) && (int)$_POST['total_files'] > 0) {
	// Iterate all uploaded files
	for ($i = 0; $i < (int)$_POST['total_files']; $i++) {
		// Make sure the file exists
		if (isset($_FILES['file_' . $i])) {
			// Check if uploaded media exists
			if (empty($_FILES['file_' . $i]['tmp_name'])) {
				exit('Veuillez selectionner une image!');
			}
			// Assign captured form data
			$title = isset($_POST['title_' . $i]) ? $_POST['title_' . $i] : $_FILES['file_' . $i]['name'];
			$description = isset($_POST['description_' . $i]) ? $_POST['description_' . $i] : '';
			$public = isset($_POST['public_' . $i]) ? $_POST['public_' . $i] : '';
			// Media file type (image/audio/video)
			$type = '';
			$type = preg_match('/image\/*/', $_FILES['file_' . $i]['type']) ? 'image' : $type;
			$type = preg_match('/audio\/*/', $_FILES['file_' . $i]['type']) ? 'audio' : $type;
			$type = preg_match('/video\/*/', $_FILES['file_' . $i]['type']) ? 'video' : $type;
			// The directory where media files will be stored
			$target_dir = 'media/' . $type . 's/';
			// Unique media ID
			$media_id = md5(uniqid());
			// Media parts (name, extension)
			$media_parts = explode('.', $_FILES['file_' . $i]['name']);
			// The path of the new uploaded media file
			$media_path = $target_dir . $media_id . '.' . end($media_parts);
			// Set the max upload file size for each media type (measured in bytes):
			$image_max_size = image_max_size;
			$audio_max_size = audio_max_size;
			$video_max_size = video_max_size;
			// Check to make sure the media file is valid
			if (empty($type)) {
				$msg = 'Unsupported media format!';
			} else if (!empty($_FILES['file_' . $i]['tmp_name'])) {
				// Validate media path and size
				if (file_exists($media_path)) {
					$msg = 'Media already exists! Please choose another or rename that file.';
				} else if ($_FILES['file_' . $i]['size'] > ${$type . '_max_size'}) {
					$msg = ucfirst($type) . ' file size too large! Please choose a file with a size less than ' . convert_filesize(${$type . '_max_size'}) . '.';
				} else {
					// Everything checks out, so now we can proceed to move the uploaded media file
					move_uploaded_file($_FILES['file_' . $i]['tmp_name'], $media_path);
					// convert svg to png
					if (convert_svg_to_png && strtolower(end($media_parts)) == 'svg') {
						$media_path = convert_svg_to_png($media_path);
					}
					// Compress image
					if (image_quality < 100) {
						compress_image($media_path, image_quality);
					}
					// Fix image orientation
					if (correct_image_orientation) {
						correct_image_orientation($media_path);
					}
					// Resize image
					if (image_max_width != -1 || image_max_height != -1) {
						resize_image($media_path, image_max_width, image_max_height);
					}
					// Check thumbnail input
					$thumbnail_path = '';
					if (isset($_FILES['thumbnail_' . $i]) && preg_match('/image\/*/',$_FILES['thumbnail_' . $i]['type'])) {
						if ($_FILES['thumbnail_' . $i]['size'] > $image_max_size) {
							exit('Thumbnail size too large! Please choose a file with a size less than ' . convert_filesize($image_max_size) . '.');
						} else {
							$thumbnail_parts = explode('.', $_FILES['thumbnail_' . $i]['name']);
							$thumbnail_path = 'media/thumbnails/' . $media_id . '.' . end($thumbnail_parts);
							move_uploaded_file($_FILES['thumbnail_' . $i]['tmp_name'], $thumbnail_path);
						}
					} else if (auto_generate_image_thumbnail && $type == 'image') {
						$thumbnail_path = create_image_thumbnail($media_path, $media_id);
					}
                    // Check if approval is required
                    $approved = approval_required ? 0 : 1;
					$acc_id = isset($_SESSION['account_loggedin']) ? $_SESSION['account_id'] : NULL;
					// Insert media details into the database (title, description, media file path, date added, and media type)
					$stmt = $pdo->prepare('INSERT INTO media (title, description, filepath, uploaded_date, type, thumbnail, approved, public, acc_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([ $title, $description, $media_path, date('Y-m-d H:i:s'), $type, $thumbnail_path, $approved, $public, $acc_id ]);
					// If user selected collection, add to collection
					if (isset($_POST['collection'])) {
						// Retrieve the media ID
						$media_id = $pdo->lastInsertId();
						// Ensure the collection exists
						$stmt = $pdo->prepare('SELECT * FROM collections WHERE title = ? AND acc_id = ?');
						$stmt->execute([ $_POST['collection'], $_SESSION['account_id'] ]);
						$collection = $stmt->fetch(PDO::FETCH_ASSOC);
						// If exists, insert into database
						if ($collection) {
							$stmt = $pdo->prepare('INSERT INTO media_collections (collection_id,media_id) VALUES (?, ?)');
							$stmt->execute([ $collection['id'], $media_id ]);
						}
					}
					// Output msg
					$msg = 'Terminé!';
				}
			} else {
				$msg = 'Veuillez selectionner une image!';
			}
		}
	}
	exit($msg);
}
// Retrieve the user's collections if they're logged in
$user_collections = '';
if (isset($_SESSION['account_loggedin'])) {
	$stmt = $pdo->prepare('SELECT title FROM collections WHERE acc_id = ? ORDER BY title');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$user_collections = implode(',,', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
?>
<?=template_header('Upload Media')?>

<div class="content upload">

	<div class="page-title">
		<h2>Ajouter une image</h2>
	</div>

	<form action="" method="post" enctype="multipart/form-data" class="gallery-form" data-user-collections="<?=$user_collections?>">

		<div id="drop_zone">
			<i class="fa-solid fa-upload fa-2x"></i>
			<p>Ajouter une image </p>
		</div>

		<input type="file" name="media[]" multiple accept="audio/*,video/*,image/*" id="media">

		<div class="previews"></div>

		<div class="meta"></div>

		<div class="btn_wrapper">
			<input type="submit" value="Ajouter une image" name="submit" id="submit_btn" class="btn">
			<div class="upload-result"></div>
		</div>
		
	</form>

</div>

<?=template_footer()?>
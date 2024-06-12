<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Retrieve the collections
$stmt = $pdo->prepare('SELECT * FROM collections WHERE public = 1 ORDER BY title');
$stmt->execute();
$collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve user collections
if (isset($_SESSION['account_loggedin'])) {
	$stmt = $pdo->prepare('SELECT title FROM collections WHERE acc_id = ? ORDER BY title');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$user_collections = implode(',,', $stmt->fetchAll(PDO::FETCH_COLUMN));
} else {
	$user_collections = '';
}
// Retrieve the requested collection
$collection = isset($_GET['collection']) ? $_GET['collection'] : 'all';
$collection_sql = $collection != 'all' ? 'JOIN media_collections mc ON mc.media_id = m.id AND mc.collection_id = :collection' : '';
// Sort by default is newest, feel free to change it..
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
$sort_by_sql = 'm.uploaded_date DESC';
$sort_by_sql = $sort_by == 'newest' ? 'm.uploaded_date DESC' : $sort_by_sql;
$sort_by_sql = $sort_by == 'oldest' ? 'm.uploaded_date ASC' : $sort_by_sql;
$sort_by_sql = $sort_by == 'a_to_z' ? 'm.title DESC' : $sort_by_sql;
$sort_by_sql = $sort_by == 'z_to_a' ? 'm.title ASC' : $sort_by_sql;
// Get media by the type (ignore if set to all)
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$type_sql = $type != 'all' ? 'AND m.type = :type' : '';
// Handle search query
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '';
$search_sql = $search ? 'AND m.title LIKE :search' : '';
// Limit the amount of media on each page
$media_per_page = 6;
// The current pagination page
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;
// MySQL query that selects all the media
$stmt = $pdo->prepare('SELECT 
	m.*, 
	(SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id) AS likes, 
	(SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id AND ml.acc_id = :acc_id) AS liked 
	FROM media m ' . $collection_sql . ' 
	WHERE m.approved = 1 AND m.public = 1 ' . $type_sql . ' ' . $search_sql . ' 
	ORDER BY ' . $sort_by_sql . ' 
	LIMIT :page,:media_per_page');
// Bind the account ID
$stmt->bindValue(':acc_id', isset($_SESSION['account_loggedin']) ? $_SESSION['account_id'] : -1, PDO::PARAM_INT);
// Determine which page the user is on and bind the value into our SQL statement
$stmt->bindValue(':page', ((int)$current_page-1)*$media_per_page, PDO::PARAM_INT);
// How many media will show on each page
$stmt->bindValue(':media_per_page', $media_per_page, PDO::PARAM_INT);
// Check if the type is not set to all
if ($type != 'all') $stmt->bindValue(':type', $type);
// Check search
if ($search) $stmt->bindValue(':search', $search);
// Check if the collection is not set to all
if ($collection != 'all') $stmt->bindValue(':collection', $collection);
// Execute the SQL
$stmt->execute();
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of media
$stmt = $pdo->prepare('SELECT COUNT(*) FROM media m ' . $collection_sql . ' WHERE m.approved = 1 AND m.public = 1 ' . $type_sql . ' ' . $search_sql);
if ($type != 'all') $stmt->bindValue(':type', $type);
if ($search) $stmt->bindValue(':search', $search);
if ($collection != 'all') $stmt->bindValue(':collection', $collection);
$stmt->execute();
$total_media = $stmt->fetchColumn();
?>
<?=template_header('Gallery')?>

<div class="content home">

	<div class="page-title">
		<h2>Page d'accueil</h2>
	</div>

	<?php if ($collection != 'all'): ?>
	<p><?=$collections[array_search($collection, array_column($collections, 'id'))]['description']?></p>
	<?php endif; ?>

	<div class="con">
		<a href="upload.php" class="btn">Poster une photo</a>

		<form action="" method="get">
			<label for="collection">Collection</label>
			<select id="collection" name="collection" onchange="this.form.submit()">
				<option value="all"<?=$sort_by=='all'?' selected':''?>>Tout</option>
				<?php foreach ($collections as $c): ?>
				<option value="<?=$c['id']?>"<?=$collection==$c['id']?' selected':''?>><?=$c['title']?></option>
				<?php endforeach; ?>
			</select>
			<label for="sort_by">Trier par</label>
			<select id="sort_by" name="sort_by" onchange="this.form.submit()">
				<option value="newest"<?=$sort_by=='newest'?' selected':''?>>Nouveau</option>
				<option value="oldest"<?=$sort_by=='oldest'?' selected':''?>>Vieux</option>
				<option value="a_to_z"<?=$sort_by=='a_to_z'?' selected':''?>>A-Z</option>
				<option value="z_to_a"<?=$sort_by=='z_to_a'?' selected':''?>>Z-A</option>
			</select>
			<label for="type">Type</label>
			<select id="type" name="type" onchange="this.form.submit()">
				<option value="all"<?=$type=='all'?' selected':''?>>Tout</option>
				<option value="audio"<?=$type=='audio'?' selected':''?>>Audio</option>
				<option value="image"<?=$type=='image'?' selected':''?>>Image</option>
				<option value="video"<?=$type=='video'?' selected':''?>>Video</option>
			</select>
			<label for="search">Rechercher</label>
			<input id="search" type="text" name="search" placeholder="Mots clées" value="<?=isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES) : ''?>">
		</form>
	</div>

	<div class="media-list">
		<?php foreach ($media as $i => $m): ?>
		<?php if (file_exists($m['filepath'])): ?>
		<a href="<?=media_popup ? '#' : 'view.php?id=' . $m['id']?>" style="width:<?=media_grid_default_width?>px;height:<?=media_grid_default_height?>px;" data-src="<?=$m['filepath']?>" data-id="<?=$m['id']?>" data-user-collections="<?=htmlspecialchars($user_collections, ENT_QUOTES)?>" data-title="<?=htmlspecialchars($m['title'], ENT_QUOTES)?>" data-description="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" data-type="<?=$m['type']?>" data-likes="<?=$m['likes']?>" data-liked="<?=$m['liked']?1:0?>"<?=isset($_SESSION['account_loggedin']) && $_SESSION['account_id'] == $m['acc_id']?' data-own-media':''?>>
			<?php if ($m['type'] == 'image'): ?>

			<?php if (empty($m['thumbnail'])): ?>
			<img src="<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="<?=media_grid_default_width?>" height="<?=media_grid_default_height?>">
			<?php else: ?>
			<img src="<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="<?=media_grid_default_width?>" height="<?=media_grid_default_height?>">
			<?php endif; ?>
			
			<?php elseif ($m['type'] == 'video'): ?>

			<?php if (empty($m['thumbnail'])): ?>
			<span class="placeholder">
				<i class="fas fa-film fa-4x"></i>
				<?=$m['title']?>
			</span>
			<?php else: ?>
			<img src="<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="<?=media_grid_default_width?>" height="<?=media_grid_default_height?>">
			<?php endif; ?>

			<?php elseif ($m['type'] == 'audio'): ?>

			<?php if (empty($m['thumbnail'])): ?>
			<span class="placeholder">
				<i class="fas fa-music fa-4x"></i>
				<?=$m['title']?>
			</span>
			<?php else: ?>
			<img src="<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="<?=media_grid_default_width?>" height="<?=media_grid_default_height?>">
			<?php endif; ?>

			<?php endif; ?>
			<span class="description"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></span>
		</a>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<div class="pagination">
	    <?php if ($current_page > 1): ?>
	    <a href="?page=<?=$current_page-1?>&sort_by=<?=$sort_by?>&collection=<?=$collection?>">Retour</a>
	    <?php endif; ?>
	    <div>Page <?=$current_page?></div>
	    <?php if ($current_page * $media_per_page < $total_media): ?>
	    <a href="?page=<?=$current_page+1?>&sort_by=<?=$sort_by?>&collection=<?=$collection?>">Prochain</a>
	    <?php endif; ?>
	</div>

</div>

<?php if (media_popup): ?>
<div class="media-popup"></div>
<?php endif; ?>

<?=template_footer()?>
<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Make sure GET ID exists
if (isset($_GET['id'])) {
    // Retrieve collection from database
    $stmt = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM media_collections mc WHERE mc.collection_id = c.id) AS total_media FROM collections c WHERE c.id = ?');
	$stmt->execute([ $_GET['id'] ]);
	$collection = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$collection) {
        exit('Invalid ID!');
    }
    // Check whether the collection is public or private
    if (!$collection['public']) {
        // Collection is private, make sure the user has permission
        if (isset($_SESSION['account_id']) && $collection['acc_id'] != $_SESSION['account_id'] && $_SESSION['account_role'] != 'Admin') {
            exit('Private collection!');
        } else if (!isset($_SESSION['account_id'])) {
            exit('Private collection!');
        }
    }
} else if (!isset($_GET['view'])) {
    exit('Invalid ID!');
} else if (isset($_GET['view']) && !isset($_SESSION['account_id'])) {
    exit('Invalid ID!');
}
// Retrieve user collections
if (isset($_SESSION['account_loggedin'])) {
	$stmt = $pdo->prepare('SELECT title FROM collections WHERE acc_id = ? ORDER BY title');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$user_collections = implode(',,', $stmt->fetchAll(PDO::FETCH_COLUMN));
} else {
	$user_collections = '';
}
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
if (isset($_GET['view'])) {
    $type = $_GET['view'] == 'images' ? 'image' : $type;
    $type = $_GET['view'] == 'videos' ? 'video' : $type;
    $type = $_GET['view'] == 'audios' ? 'audio' : $type;
    $type_sql = $type != 'all' ? 'AND m.type = :type AND m.acc_id = :acc_id' : '';
    $like_sql = $type == 'all' ? 'JOIN media_likes ml ON ml.media_id = m.id' : ''; 
    // MySQL query that selects all the media
    $stmt = $pdo->prepare('SELECT 
        m.*, 
        (SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id) AS likes, 
        (SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id AND ml.acc_id = :acc_id) AS liked 
        FROM media m ' . $like_sql . ' 
        WHERE m.approved = 1 ' . $type_sql . ' ' . $search_sql . ' 
        ORDER BY ' . $sort_by_sql . ' 
        LIMIT :page,:media_per_page');
} else {
    // MySQL query that selects all the media
    $stmt = $pdo->prepare('SELECT 
        m.*, 
        (SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id) AS likes, 
        (SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id AND ml.acc_id = :acc_id) AS liked 
        FROM media m 
        JOIN collections c ON c.id = :collection_id 
        JOIN media_collections mc ON mc.media_id = m.id AND mc.collection_id = c.id 
        WHERE m.approved = 1 ' . $type_sql . ' ' . $search_sql . ' 
        ORDER BY ' . $sort_by_sql . ' 
        LIMIT :page,:media_per_page');
    // Bind the collection ID
    $stmt->bindValue(':collection_id', $collection['id'], PDO::PARAM_INT);
}
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
// Execute the SQL
$stmt->execute();
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['view'])) {
    // Get the total number of media
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM media m WHERE m.acc_id = :acc_id AND m.approved = 1 ' . $type_sql . ' ' . $search_sql);
    // Bind the account ID
    $stmt->bindValue(':acc_id', $_SESSION['account_id'], PDO::PARAM_INT);
} else {
    // Get the total number of media
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM media m JOIN collections c ON c.id = :collection_id JOIN media_collections mc ON mc.media_id = m.id AND mc.collection_id = c.id WHERE m.approved = 1 ' . $type_sql . ' ' . $search_sql);
    // Bind the collection ID
    $stmt->bindValue(':collection_id', $collection['id'], PDO::PARAM_INT);
}
if ($type != 'all') $stmt->bindValue(':type', $type);
if ($search) $stmt->bindValue(':search', $search);
$stmt->execute();
$total_media = $stmt->fetchColumn();
?>
<?=template_header((isset($_GET['view']) ? ucfirst(htmlspecialchars($_GET['view'], ENT_QUOTES)) : htmlspecialchars($collection['title'], ENT_QUOTES)) . ' Collection')?>

<div class="content collection">

    <div class="page-title">
        <h2><?=isset($_GET['view']) ? 'My ' . ucfirst(htmlspecialchars($_GET['view'], ENT_QUOTES)) : htmlspecialchars($collection['title'], ENT_QUOTES)?></h2>
    </div>

    <?php if (isset($collection) && $collection['description']): ?>
    <p class="desc"><?=htmlspecialchars($collection['description'], ENT_QUOTES)?></p>
    <?php endif; ?>

    <div class="con">

        <div class="actions">
        <?php if (isset($_SESSION['account_loggedin'], $collection) && $collection['acc_id'] == $_SESSION['account_id']): ?>
        <a href="manage-collection.php?id=<?=$collection['id']?>" class="btn">Editer les albums</a>
        <?php endif; ?>
        </div>

		<form action="" method="get">
            <?php if (isset($collection)): ?>
            <input type="hidden" name="id" value="<?=$collection['id']?>">
            <?php else: ?>
            <input type="hidden" name="view" value="<?=htmlspecialchars($_GET['view'], ENT_QUOTES)?>">
            <?php endif; ?>
			<label for="sort_by">Trier par</label>
			<select id="sort_by" name="sort_by" onchange="this.form.submit()">
				<option value="newest"<?=$sort_by=='newest'?' selected':''?>>Plus récent</option>
				<option value="oldest"<?=$sort_by=='oldest'?' selected':''?>>Plus vieux</option>
				<option value="a_to_z"<?=$sort_by=='a_to_z'?' selected':''?>>A-Z</option>
				<option value="z_to_a"<?=$sort_by=='z_to_a'?' selected':''?>>Z-A</option>
			</select>
            <?php if (isset($collection)): ?>
			<label for="type">Type</label>
			<select id="type" name="type" onchange="this.form.submit()">
				<option value="all"<?=$type=='all'?' selected':''?>>All</option>
				<option value="audio"<?=$type=='audio'?' selected':''?>>Audio</option>
				<option value="image"<?=$type=='image'?' selected':''?>>Image</option>
				<option value="video"<?=$type=='video'?' selected':''?>>Vidéo</option>
			</select>
            <?php endif; ?>
			<label for="search">Rechercher</label>
			<input id="search" type="text" name="search" placeholder="Mots clées" value="<?=isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES) : ''?>">
		</form>
	</div>

	<div class="media-list">
		<?php foreach ($media as $i => $m): ?>
		<?php if (file_exists($m['filepath'])): ?>
		<a href="#" style="width:<?=media_grid_default_width?>px;height:<?=media_grid_default_height?>px;" data-src="<?=$m['filepath']?>" data-id="<?=$m['id']?>" data-user-collections="<?=htmlspecialchars($user_collections, ENT_QUOTES)?>" data-title="<?=htmlspecialchars($m['title'], ENT_QUOTES)?>" data-description="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" data-type="<?=$m['type']?>" data-likes="<?=$m['likes']?>" data-liked="<?=$m['liked']?1:0?>"<?=isset($_SESSION['account_loggedin']) && $_SESSION['account_id'] == $m['acc_id']?' data-own-media':''?><?php if (isset($_SESSION['account_loggedin'], $collection) && $collection['acc_id'] == $_SESSION['account_id']): ?> data-collection="<?=$collection['id']?>"<?php endif; ?>>
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
        <?php if (!$media): ?>
        <p class="no-media">Aucune image.</p>
        <?php endif; ?>
	</div>

	<div class="pagination">
	    <?php if ($current_page > 1): ?>

        <?php if (isset($collection)): ?>
	    <a href="?id=<?=$collection['id']?>&page=<?=$current_page-1?>&sort_by=<?=$sort_by?>">Prev</a>
        <?php else: ?>
        <a href="?view=<?=htmlspecialchars($_GET['view'], ENT_QUOTES)?>&page=<?=$current_page-1?>&sort_by=<?=$sort_by?>">Précédent</a>   
        <?php endif; ?>

	    <?php endif; ?>

	    <div>Page <?=$current_page?></div>

	    <?php if ($current_page * $media_per_page < $total_media): ?>

        <?php if (isset($collection)): ?>
	    <a href="?id=<?=$collection['id']?>&page=<?=$current_page+1?>&sort_by=<?=$sort_by?>">Suivant</a>
        <?php else: ?>
        <a href="?view=<?=htmlspecialchars($_GET['view'], ENT_QUOTES)?>&page=<?=$current_page+1?>&sort_by=<?=$sort_by?>">Précédent</a>   
        <?php endif; ?>

	    <?php endif; ?>
	</div>

</div>

<div class="media-popup"></div>

<?=template_footer()?>
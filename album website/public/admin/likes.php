<?php
include 'main.php';
// Delete media like
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM media_likes WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: likes.php?success_msg=3');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','acc_id'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (m.title LIKE :search OR ml.media_id LIKE :search OR a.email LIKE :search OR ml.acc_id LIKE :search) ' : '';
if (isset($_GET['acc_id'])) {
    $where .= $where ? ' AND ml.acc_id = :acc_id ' : ' WHERE ml.acc_id = :acc_id ';
} 
if (isset($_GET['media_id'])) {
    $where .= $where ? ' AND ml.media_id = :media_id ' : ' WHERE ml.media_id = :media_id ';
} 
// Retrieve the total number of media
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM media_likes ml JOIN media m ON m.id = ml.media_id LEFT JOIN accounts a ON a.id = ml.acc_id ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
if (isset($_GET['media_id'])) $stmt->bindParam('media_id', $_GET['media_id'], PDO::PARAM_INT);
$stmt->execute();
$media_likes_total = $stmt->fetchColumn();
// SQL query to get all media likes from the "media_likes" table
$stmt = $pdo->prepare('SELECT ml.*, m.filepath, m.title, m.type, m.description, a.email FROM media_likes ml JOIN media m ON m.id = ml.media_id LEFT JOIN accounts a ON a.id = ml.acc_id ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
if (isset($_GET['media_id'])) $stmt->bindParam('media_id', $_GET['media_id'], PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$media_likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Media like added successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Media like deleted successfully!';
    }
}
// Determine the URL
$url = 'likes.php?search=' . $search . (isset($_GET['acc_id']) ? '&acc_id=' . $_GET['acc_id'] : '') . (isset($_GET['media_id']) ? '&media_id=' . $_GET['media_id'] : '');
?>
<?=template_admin_header('Media Likes', 'allmedia', 'likes')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-heart"></i>
        <div class="txt">
            <h2>Media Likes</h2>
            <p>View image, audio, and video likes.</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>


<div class="content-header responsive-flex-column pad-top-5">
    <a href="like.php" class="btn">Add Media Like</a>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search media like..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?php if ($order_by=='id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Media<?php if ($order_by=='title'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=acc_id'?>">Account<?php if ($order_by=='acc_id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media_likes)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no media likes</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media_likes as $m): ?>
                <tr>
                    <td class="responsive-hidden"><?=$m['id']?></td>
                    <td>
                        <div class="media">
                            <a href="../<?=$m['filepath']?>" class="media-img" target="_blank" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>">
                            <?php if ($m['type'] == 'image'): ?>
                            <img src="../<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php elseif ($m['type'] == 'video'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-film"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php elseif ($m['type'] == 'audio'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-music"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php endif; ?>
                            </a>
                            <a href="../<?=$m['filepath']?>" target="_blank" class="link1" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>"><?=htmlspecialchars($m['title'], ENT_QUOTES)?></a>
                        </div>
                    </td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td>
                        <a href="likes.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media like?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($media_likes_total / $results_per_page) == 0 ? 1 : ceil($media_likes_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $media_likes_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>
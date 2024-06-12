<?php
include 'main.php';
// Delete media
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($media['thumbnail']) {
        unlink('../' . $media['thumbnail']);
    }
    unlink('../' . $media['filepath']);
    $stmt = $pdo->prepare('DELETE m, ml, mc FROM media m LEFT JOIN media_likes ml ON ml.media_id = m.id LEFT JOIN media_collections mc ON mc.media_id = m.id WHERE m.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: allmedia.php?success_msg=3');
    exit;
}
// Approve media
if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare('UPDATE media SET approved = 1 WHERE id = ?');
    $stmt->execute([ $_GET['approve'] ]);
    header('Location: allmedia.php?success_msg=4');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','description','acc_id','uploaded_date','approved','likes','type'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (title LIKE :search OR filepath LIKE :search) ' : '';
if (isset($_GET['acc_id'])) {
    $where .= $where ? ' AND acc_id = :acc_id ' : ' WHERE acc_id = :acc_id ';
} 
// Retrieve the total number of media
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM media ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
$stmt->execute();
$media_total = $stmt->fetchColumn();
// SQL query to get all media from the "media" table
$stmt = $pdo->prepare('SELECT m.*, a.email, (SELECT COUNT(*) FROM media_likes ml WHERE ml.media_id = m.id LIMIT 1) AS likes FROM media m LEFT JOIN accounts a ON a.id = m.acc_id ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Media created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Media updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Media deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Media approved successfully!';
    }
}
// Determine the URL
$url = 'allmedia.php?search=' . $search . (isset($_GET['acc_id']) ? '&acc_id=' . $_GET['acc_id'] : '');
?>
<?=template_admin_header('Media', 'allmedia', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-photo-film"></i>
        <div class="txt">
            <h2>Media</h2>
            <p>View image, audio, and video uploads.</p>
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
    <a href="media.php" class="btn">Create Media</a>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search media..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=description'?>">Description<?php if ($order_by=='description'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=acc_id'?>">Account<?php if ($order_by=='acc_id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=type'?>">Type<?php if ($order_by=='type'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=likes'?>">Likes<?php if ($order_by=='likes'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=approved'?>">Approved<?php if ($order_by=='approved'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=uploaded_date'?>">Date<?php if ($order_by=='uploaded_date'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no media</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media as $m): ?>
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
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td class="responsive-hidden"><?=ucfirst($m['type'])?></td>
                    <td class="responsive-hidden"><a href="likes.php?media_id=<?=$m['id']?>" class="link1"><?=number_format($m['likes'])?></a></td>
                    <td style="font-weight:500;color:<?=$m['approved']?'green':'red'?>"><?=$m['approved']?'Yes':'No'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($m['uploaded_date']))?></td>
                    <td>
                        <a href="media.php?id=<?=$m['id']?>" class="link1">Edit</a>
                        <a href="allmedia.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media?')">Delete</a>
                        <?php if (!$m['approved']): ?>
                        <a href="allmedia.php?approve=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this media?')">Approve</a>
                        <?php endif; ?>
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
    <span>Page <?=$pagination_page?> of <?=ceil($media_total / $results_per_page) == 0 ? 1 : ceil($media_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $media_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>
<?php
include 'main.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','description','acc_id','public','total_media'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (c.title LIKE :search OR c.acc_id = :search) ' : '';
// Retrieve the total number of collections
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM collections c ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$collections_total = $stmt->fetchColumn();
// SQL query to get all collections from the "collections" table
$stmt = $pdo->prepare('SELECT c.*, a.email, (SELECT COUNT(*) FROM media_collections mc WHERE mc.collection_id = c.id LIMIT 1) AS total_media FROM collections c LEFT JOIN accounts a ON a.id = c.acc_id ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Collection created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Collection updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Collection deleted successfully!';
    }
}
// Determine the URL
$url = 'collections.php?search=' . $search;
?>
<?=template_admin_header('collections', 'collections', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-list-ul"></i>
        <div class="txt">
            <h2>Collections</h2>
            <p>View and manage collections.</p>
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
    <a href="collection.php" class="btn">Create Collection</a>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search collection..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?php if ($order_by=='title'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=description'?>">Description<?php if ($order_by=='description'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_media'?>">Total Media<?php if ($order_by=='total_media'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=acc_id'?>">Account<?php if ($order_by=='acc_id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=public'?>">Public<?php if ($order_by=='public'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($collections)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;">There are no collections</td>
                </tr>
                <?php else: ?>
                <?php foreach ($collections as $c): ?>
                <tr>
                    <td class="responsive-hidden"><?=$c['id']?></td>
                    <td><?=htmlspecialchars($c['title'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($c['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=number_format($c['total_media'])?></td>
                    <td class="responsive-hidden"><?=$c['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($c['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($c['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($c['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td style="font-weight:500;color:<?=$c['public']?'green':'red'?>"><?=$c['public']?'Yes':'No'?></td>
                    <td><a href="../collection.php?id=<?=$c['id']?>" class="link1" target="_blank">View</a> <a href="collection.php?id=<?=$c['id']?>" class="link1">Edit</a></td>
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
    <span>Page <?=$pagination_page?> of <?=ceil($collections_total / $results_per_page) == 0 ? 1 : ceil($collections_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $collections_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>
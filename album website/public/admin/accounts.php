<?php
include 'main.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','email','display_name','role','total_media','total_collections','total_likes'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (email LIKE :search OR display_name LIKE :search) ' : '';
// Retrieve the total number of accounts
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$accounts_total = $stmt->fetchColumn();
// SQL query to get all accounts from the "accounts" table
$stmt = $pdo->prepare('SELECT a.*, (SELECT COUNT(*) FROM media m WHERE m.acc_id = a.id LIMIT 1) AS total_media, (SELECT COUNT(*) FROM collections c WHERE c.acc_id = a.id LIMIT 1) AS total_collections, (SELECT COUNT(*) FROM media_likes ml WHERE ml.acc_id = a.id LIMIT 1) AS total_likes FROM accounts a ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Account created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Account updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Account deleted successfully!';
    }
}
// Determine the URL
$url = 'accounts.php?search=' . $search;
?>
<?=template_admin_header('Accounts', 'accounts', 'view')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-users"></i>
        <div class="txt">
            <h2>Accounts</h2>
            <p>View and manage accounts.</p>
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
    <a href="account.php" class="btn">Create Account</a>
    <form action="" method="get">
        <input type="hidden" name="page" value="accounts">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search account..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?php if ($order_by=='email'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=display_name'?>">Display Name<?php if ($order_by=='display_name'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_media'?>">Total Media<?php if ($order_by=='total_media'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_collections'?>">Total Collections<?php if ($order_by=='total_collections'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_likes'?>">Total Likes<?php if ($order_by=='total_likes'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=role'?>">Role<?php if ($order_by=='role'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no accounts</td>
                </tr>
                <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td class="responsive-hidden"><?=$account['id']?></td>
                    <td><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($account['display_name'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><a href="allmedia.php?acc_id=<?=$account['id']?>" class="link1"><?=$account['total_media']?></a></td>
                    <td class="responsive-hidden"><a href="collections.php?acc_id=<?=$account['id']?>" class="link1"><?=$account['total_collections']?></a></td>
                    <td class="responsive-hidden"><a href="likes.php?acc_id=<?=$account['id']?>" class="link1"><?=$account['total_likes']?></a></td>
                    <td class="responsive-hidden"><?=$account['role']?></td>
                    <td><a href="account.php?id=<?=$account['id']?>" class="link1">Edit</a></td>
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
    <span>Page <?=$pagination_page?> of <?=ceil($accounts_total / $results_per_page) == 0 ? 1 : ceil($accounts_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $accounts_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>
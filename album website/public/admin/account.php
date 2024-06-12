<?php
include 'main.php';
// Default account values
$account = [
    'email' => '',
    'password' => '',
    'role' => 'Member',
    'display_name' => ''
];
if (isset($_GET['id'])) {
    // Retrieve the account from the database
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing account
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the account
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $account['password'];
        $stmt = $pdo->prepare('UPDATE accounts SET email = ?, password = ?, role = ?, display_name = ? WHERE id = ?');
        $stmt->execute([ $_POST['email'], $password, $_POST['role'], $_POST['display_name'], $_GET['id'] ]);
        header('Location: accounts.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the account
        $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: accounts.php?success_msg=3');
        exit;
    }
} else {
    // Create a new account
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO accounts (email,password,role,display_name) VALUES (?,?,?,?)');
        $stmt->execute([ $_POST['email'], $password, $_POST['role'], $_POST['display_name'] ]);
        header('Location: accounts.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Account', 'accounts', 'manage')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Account</h2>
        <a href="accounts.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this account?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="email"><i class="required">*</i> Email</label>
            <input id="email" type="email" name="email" placeholder="Email" value="<?=htmlspecialchars($account['email'], ENT_QUOTES)?>" required>

            <label for="password"><?=$page == 'Edit' ? 'New ' : ''?>Password</label>
            <input type="text" id="password" name="password" placeholder="<?=$page == 'Edit' ? 'New ' : ''?>Password" value=""<?=$page == 'Edit' ? '' : ' required'?>>

            <label for="display_name">Display Name</label>
            <input id="display_name" type="text" name="display_name" placeholder="Joe Bloggs" value="<?=htmlspecialchars($account['display_name'], ENT_QUOTES)?>">

            <label for="role"><i class="required">*</i> Role</label>
            <select id="role" name="role" required>
                <option value="Member"<?=$account['role']=='Member'?' selected':''?>>Member</option>
                <option value="Admin"<?=$account['role']=='Admin'?' selected':''?>>Admin</option>
            </select>

        </div>

    </div>

</form>

<?=template_admin_footer()?>
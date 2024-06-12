<?php
// Include the configuration file
include_once '../config.php';
include_once '../functions.php';
// Check if admin is logged in
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../collections.php');
    exit;
}
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to database!');
}
// If the user is not admin redirect them back to the collections page
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['account_id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Ensure account is an admin
if (!$account || $account['role'] != 'Admin') {
    header('Location: ../collections.php');
    exit;
}
// Template admin header
function template_admin_header($title, $selected = 'orders', $selected_child = 'view') {
    $admin_links = '
        <a href="index.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . '><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="allmedia.php"' . ($selected == 'allmedia' ? ' class="selected"' : '') . '><i class="fa-solid fa-photo-film"></i>Media</a>
        <div class="sub">
            <a href="allmedia.php"' . ($selected == 'allmedia' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Media</a>
            <a href="media.php"' . ($selected == 'allmedia' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Media</a>
            <a href="likes.php"' . ($selected == 'allmedia' && $selected_child == 'likes' ? ' class="selected"' : '') . '><span>&#9724;</span>View Likes</a>
        </div>
        <a href="collections.php"' . ($selected == 'collections' ? ' class="selected"' : '') . '><i class="fa-solid fa-list-ul"></i>Collections</a>
        <div class="sub">
            <a href="collections.php"' . ($selected == 'collections' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Collections</a>
            <a href="collection.php"' . ($selected == 'collections' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Collection</a>
        </div>
        <a href="accounts.php"' . ($selected == 'accounts' ? ' class="selected"' : '') . '><i class="fas fa-users"></i>Accounts</a>
        <div class="sub">
            <a href="accounts.php"' . ($selected == 'accounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Accounts</a>
            <a href="account.php"' . ($selected == 'accounts' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Account</a>
        </div>
        <a href="settings.php"' . ($selected == 'settings' ? ' class="selected"' : '') . '><i class="fas fa-tools"></i>Settings</a>
    ';
// DO NOT INDENT THE BELOW CODE
echo <<<EOT
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>$title</title>
        <link rel="icon" type="image/png" href="../favicon.png">
		<link href="admin.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
	</head>
	<body class="admin">
        <aside class="responsive-width-100 responsive-hidden">
            <h1>Admin</h1>
            $admin_links
            <div class="footer">
                <a href="https://codeshack.io/package/php/advanced-gallery-system/" target="_blank">Advanced Gallery System</a>
                Version 2.1.0
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#">
                    <i class="fas fa-bars"></i>
                </a>
                <div class="space-between"></div>
                <div class="dropdown right">
                    <i class="fas fa-user-circle"></i>
                    <div class="list">
                        <a href="account.php?id={$_SESSION['account_id']}">Edit Profile</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
            </header>
EOT;
}
// Template admin footer
function template_admin_footer($js_script = '') {
        $js_script = $js_script ? '<script>' . $js_script . '</script>' : '';
// DO NOT INDENT THE BELOW CODE
echo <<<EOT
        </main>
        <script src="admin.js"></script>
        {$js_script}
    </body>
</html>
EOT;
}
?>
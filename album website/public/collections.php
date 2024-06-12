<?php
include 'functions.php';
// Connect to MySQL
$pdo = pdo_connect_mysql();
// Output messages
$login_msg = '';
$register_msg = '';
// Check whether the user us logged in or not
if (isset($_SESSION['account_loggedin'])) {
	// Retrieve the user's collections
	$stmt = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM media_collections mc WHERE mc.collection_id = c.id) AS total_media FROM collections c WHERE c.acc_id = ? ORDER BY c.title');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
	// Retrieve likes
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM media_likes WHERE acc_id = ?');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$total_media_likes = $stmt->fetchColumn();	
	// Retrieve total images
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM media WHERE type = "image" AND acc_id = ?');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$total_images = $stmt->fetchColumn();	
	// Retrieve total audios
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM media WHERE type = "audio" AND acc_id = ?');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$total_audios = $stmt->fetchColumn();	
	// Retrieve total videos
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM media WHERE type = "video" AND acc_id = ?');
	$stmt->execute([ $_SESSION['account_id'] ]);
	$total_videos = $stmt->fetchColumn();	
} else {
	// Login form: Authenticate the user
	if (isset($_POST['login'], $_POST['email'], $_POST['password'])) {
		// Retrieve the account associated with the captured email
		$stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
		$stmt->execute([ $_POST['email'] ]);
		$account = $stmt->fetch(PDO::FETCH_ASSOC);
		// Validate password
		if ($account && password_verify($_POST['password'], $account['password'])) {
			// Declare session data
			$_SESSION['account_loggedin'] = true;
			$_SESSION['account_id'] = $account['id'];
			$_SESSION['account_role'] = $account['role'];
			// Redirect to collections page
			header('Location: collections.php');
			exit;
		} else {
			// Ouput login error
			$login_msg = 'Incorrect email and/or password!';
		}
	}
	// Registration form: Register new user
	if (isset($_POST['register'], $_POST['display_name'], $_POST['email'], $_POST['password'])) {
		// Make sure the submitted registration values are not empty.
		if (empty($_POST['display_name']) || empty($_POST['password']) || empty($_POST['email'])) {
			// One or more values are empty.
			$register_msg = 'Veuillez compléter le formulaire!';
		} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$register_msg = 'Veuillez entrer un email valide!';
		} else if (!preg_match('/^[a-zA-Z0-9 ]+$/', $_POST['display_name'])) {
			$register_msg = 'Nom invalide!';
		} else if (strlen($_POST['password']) > 20 || strlen($_POST['password']) < 5) {
			$register_msg = 'Le mode de passe doit être plus long que 5 lettres!';
		} else {
			// Check if the account with that email already exists
			$stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
			$stmt->execute([ $_POST['email'] ]);
			$account = $stmt->fetch(PDO::FETCH_ASSOC);
			// Store the result, so we can check if the account exists in the database.
			if ($account) {
				// Email already exists
				$register_msg = 'Cet email existe déjà!';
			} else {
				// Email doesn't exist, insert new account
				// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
				$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
				// Default role
				$role = 'Membre';
				// Prepare query; prevents SQL injection
				$stmt = $pdo->prepare('INSERT INTO accounts (email, password, display_name, role) VALUES (?, ?, ?, ?)');
				$stmt->execute([ $_POST['email'], $password, $_POST['display_name'], $role ]);
				// Output response
				$register_msg = 'Vous êtes maintenant enregistré!';
			}
		}
	}	
}
?>
<?=template_header('My Collections')?>

<div class="content collections">

	<?php if (!isset($_SESSION['account_loggedin'])): ?>
	
	<div class="login-register">

		<form action="" method="post" class="gallery-form login">

			<div class="page-title">
				<h2>Se connecter</h2>
			</div>

			<label for="email">Email</label>
			<input id="email" name="email" type="email" placeholder="Email" required>

			<label for="password">Mot de passe</label>
			<input id="password" name="password" type="password" placeholder="Mot de passe" required>

			<div class="btn_wrapper">
				<input type="submit" value="Se connecter" name="login" class="btn">
				<div class="result"><?=$login_msg?></div>
			</div>

		</form>

		<form action="" method="post" class="gallery-form register">
			
			<div class="page-title">
				<h2>Créer un compte</h2>
			</div>

			<label for="display_name">Nom d'utilisateur</label>
			<input id="display_name" name="display_name" type="text" placeholder="Nom d'utilisateur" pattern="[A-Za-z0-9 ]+" required>

			<label for="email2">Email</label>
			<input id="email2" name="email" type="email" placeholder="Email" required>

			<label for="password2">Mot de passe</label>
			<input id="password2" name="password" type="password" placeholder="Mot de passe" required>

			<div class="btn_wrapper">
				<input type="submit" value="Créer un compte" name="register" class="btn">
				<div class="result"><?=$register_msg?></div>
			</div>

		</form>

	</div>

	<?php else: ?>
	<div class="page-title">
		<h2>Mes albums</h2>
	</div>
	
	<div class="actions">
		<a href="manage-collection.php" class="btn">Créer un album</a>
	</div>

	<div class="collection-list">
		<?php foreach ($collections as $collection): ?>
		<a href="collection.php?id=<?=$collection['id']?>">
			<i class="fa-solid fa-folder-open fa-3x"></i>
			<span class="title"><?=htmlspecialchars($collection['title'], ENT_QUOTES)?></span>
			<span class="num"><?=number_format($collection['total_media'])?> Files</span>
		</a>
		<?php endforeach; ?>
		<a href="collection.php?view=images">
			<i class="fa-solid fa-image fa-3x"></i>
			<span class="title">Images</span>
			<span class="num"><?=number_format($total_images)?> Files</span>
		</a>
		<a href="collection.php?view=videos">
			<i class="fa-solid fa-film fa-3x"></i>
			<span class="title">Vidéos</span>
			<span class="num"><?=number_format($total_videos)?> Files</span>
		</a>
		<a href="collection.php?view=audios">
			<i class="fa-solid fa-music fa-3x"></i>
			<span class="title">Audios</span>
			<span class="num"><?=number_format($total_audios)?> Files</span>
		</a>
		<a href="collection.php?view=likes">
			<i class="fa-solid fa-heart fa-3x"></i>
			<span class="title">Likes</span>
			<span class="num"><?=number_format($total_media_likes)?> Files</span>
		</a>
	</div>

	<?php endif; ?>

</div>

<?=template_footer()?>
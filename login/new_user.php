<?php
	session_start();
	if($return) $_SESSION['return_to'] = $return;
	if(!$return) $return = $_SESSION['return_to'];
	if($photo) $_SESSION['photo'] = $photo;
	if(!$photo) $photo = $_SESSION['photo'];
	if($identity_url) $_SESSION['identity_url'] = $identity_url;
	if(!$identity_url) $identity_url = $_SESSION['identity_url'];

	if(isset($_POST['email']) && isset($_POST['nickname'])) {
   		require_once(dirname(__FILE__).'/../include/connectDB.php');//connect to database
		if($_POST['email']) $email = $_POST['email'];
		if($_POST['nickname']) $nickname = $_POST['nickname'];
		if($email && !$nickname) {
			$nickname = explode('@', $email);
			$nickname = $nickname[0];
		}
		if($email && $nickname) {
			if(strstr($email, '@')) {
				if(mysql_query("INSERT INTO users (nickname,email,photo) VALUES ('$nickname','$email','$photo')", $db)) { //insert new user
					$userid = mysql_insert_id();
					mysql_query("INSERT INTO login_ids (user_id,login_id) VALUES ($userid,'".mysql_real_escape_string($identity_url,$db)."')", $db) or die(mysql_error());//insert user's OpenID
					$session_id = sha1('a'.$userid.microtime(true).rand(-999999,999999).'theveeb');
					mysql_query("UPDATE users SET session_id='$session_id', session_timeout=".(time()+60*60*25)." WHERE user_id=".$userid,$db) or die(mysql_error());
					setcookie("theveeb_session",$session_id,0,'/');//set cookie
					@mysql_close($db);
					header('Location: '.$return,true,303);//redirect
				} else {
					if(mysql_errno() == 1062) {
						$error = 'Someone has already taken that nickname.';
					} else {
						die(mysql_error());
					}
				}
			} else {
				$error = 'Email address invalid.';
			}
		} else {
			$error = 'Must have both a nickname, and an email address.';
		}
	}

	header('Content-Type: application/xhtml+xml');
	echo '<?xml version="1.0" encoding="utf-8" ?>'."\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>The Veeb Ecosystem - Register</title>
		<link rel="stylesheet" media="screen" type="text/css" href="../resources/main.css" />
		<link rel="shortcut icon" type="image/png" href="../resources/favicon.png" />
		<style type="text/css">
			fieldset {
				position: relative;
				width: 70%;
				margin: auto;
			}
			label, input, select {
				font-size: 100%;
				height: 1.5em;
				width: 45%;
				margin-bottom: 0.5em;
				vertical-align: top;
			}
			label {
				float: left;
				clear: left;
			}
			input[type=submit] {
				margin-left: 45%;
			}
			#error {
				text-align: center;
				color: white;
				background-color: #f66;
				padding: 1em;
				<?php if(!$error): ?>
				display: none;
				<?php endif; ?>
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<p id="error"><?php echo $error; ?></p>
		<form method="post" action="<?php echo APPROOT.'login/new_user.php'; ?>"><fieldset>
			<h2>We need the following information</h2>
			<label for="nickname">Nickname</label>
			<input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($nickname); ?>" />
			<label for="email">Email</label>
			<input type="text" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" />
			<input type="submit" value="Proceed" />
		</fieldset></form>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>

	</body>
</html>

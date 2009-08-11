<?php

	require_once dirname(__FILE__).'/../include/check_method.php';
	$method = check_method(array('GET','PUT', 'POST'));

	require_once dirname(__FILE__).'/../include/setup.php';
	require_once dirname(__FILE__).'/../include/connectDB.php';

	$nickname = mysql_real_escape_string($_GET['nickname'], $db);
	unset($_GET['nickname']);

	require_once dirname(__FILE__).'/../include/processCookie.php';
	if(strtolower($nickname) == 'me') {
		if(!$LOGIN_DATA['user_id']) {
			header('HTTP/1.1 401 Unauthorized', true, 401);
			header('Content-Type: text/plain; charset=utf-8');
			die("You are not logged in.\n");
		}
		$nickname = $LOGIN_DATA['user_id'];
	}

	if(is_numeric($nickname)) {
		$user =mysql_query("SELECT nickname FROM users WHERE user_id=$nickname") or die(mysql_error());
		if($user = mysql_fetch_assoc($user)) {
			if($_REQUEST['oauth_signature'] || !($method == 'GET' || $method == 'HEAD')) {
				$nickname = mysql_real_escape_string($user['nickname'], $db);
			} else {
				header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'users/'.urlencode($user['nickname']), true, 302);
				exit;
			}
		}
	}

	/* Edit */
	if($method == 'PUT') {
		parse_str(file_get_contents('php://input'), $_PUT);
		if($_PUT['contact']) {
			$contact = (int)$_PUT['contact'];
			if($contact == $LOGIN_DATA['user_id']) {
				header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'users/'.urlencode($user['nickname']).'?msg=Cannot%20add%20self%20as%20contact.', true, 302);
				exit;
			}
			$private = mysql_query("SELECT private FROM users WHERE user_id=$contact") or die(mysql_query());
			$private = mysql_fetch_assoc($private);
			if($private['private']) {
				mysql_query("INSERT IGNORE INTO user_contact_requests (user_id, contact_id) VALUES ({$LOGIN_DATA['user_id']}, $contact)", $db) or die(mysql_error());
				header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'users/'.urlencode($user['nickname']).'?msg=Contact%20request%20sent%20successfully.', true, 303);
			} else {
				mysql_query("INSERT IGNORE INTO user_contacts (user_id, contact_id) VALUES ({$LOGIN_DATA['user_id']}, $contact)", $db) or die(mysql_error());
				header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'users/'.urlencode($user['nickname']).'?msg=Contact%20added%20successfully.', true, 303);
			}
			exit;
		}
		if($_PUT['email']) {
			if($LOGIN_DATA['list']) {
				$email = escapeshellarg($LOGIN_DATA['email']);
				shell_exec("/usr/sbin/remove_members discuss $email");
				$email = escapeshellarg($_PUT['email']);
				shell_exec("echo $email | /usr/sbin/add_members -r - discuss");
			}
			$email = mysql_real_escape_string($_PUT['email']);
			mysql_query("UPDATE users SET email='$email' WHERE user_id={$LOGIN_DATA['user_id']}", $db) or die(mysql_error());
		}
		if($_PUT['photo']) {
			$photo = mysql_real_escape_string($_PUT['photo']);
			mysql_query("UPDATE users SET photo='$photo' WHERE user_id={$LOGIN_DATA['user_id']}", $db) or die(mysql_error());
		}
		if($_PUT['remove_identity']) {
			$login_id = mysql_real_escape_string($_PUT['remove_identity']);
			mysql_query("DELETE FROM login_ids WHERE login_id='$login_id' AND user_id={$LOGIN_DATA['user_id']} LIMIT 1") or die(mysql_error());
		}
		if($_PUT['public_key']) {
			$public_key = escapeshellarg($_PUT['public_key']);
			$keyid = trim(`echo -n $public_key | GNUPGHOME="/home/apt/.gnupg" gpg --import 2>&1 | head -n1 | cut -d' ' -f3 | cut -d':' -f1`);
			mysql_query("UPDATE users SET public_key='$keyid' WHERE user_id={$LOGIN_DATA['user_id']}", $db) or die(mysql_error());
		}
		header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'settings/', true, 303);
		exit;
	}

	if($method == 'POST') {
		if($_POST['contact_request']) {
			$contact = (int)$_POST['contact_request'];
			if($_POST['action'] == 'Authorize') {
				mysql_query("INSERT IGNORE INTO user_contacts (user_id, contact_id) VALUES ($contact, {$LOGIN_DATA['user_id']})", $db) or die(mysql_error());
			}
			mysql_query("DELETE FROM user_contact_requests WHERE user_id=$contact AND contact_id={$LOGIN_DATA['user_id']}", $db) or die(mysql_error());
		}
		header('Location: http://'.$_SERVER['HTTP_HOST'].APPROOT.'apps/', true, 303);
		exit;
	}

	if($nickname) {
		$user = mysql_query("SELECT user_id,nickname,photo,email,balance FROM users WHERE nickname='$nickname' LIMIT 1") or die(mysql_error());
		if(!($user = mysql_fetch_assoc($user))) {
			header('HTTP/1.1 404 Not Found', true, 404);
			header('Content-Type: text/plain; charset=utf-8');
			die('That user does not exist.');
		}
		$user['photo'] = $user['photo'] ? $user['photo'] : 'http://gravatar.com/avatar/'.md5($user['email']).'?s=80&d=wavatar';
	} else {
		header('HTTP/1.1 400 Bad Request', true, 400);
		header('Content-Type: text/plain; charset=utf-8');
		die("Must pass a nickname.\n");
	}

	require dirname(__FILE__).'/../include/activity.php';
	require_once dirname(__FILE__).'/../include/use_type.php';

	switch(use_type(array('application/xhtml+xml','text/html','text/directory','text/vcard','text/x-vcard','application/rss+xml','text/plain'), true)) :

	case 'text/plain':
	header('Content-Type: text/plain; charset=utf-8');
	$fields = array();
	foreach($_GET as $k => $v) {
		if(!preg_match('/^x?oauth/', $k) && $k != 'callback' && $k != 'accept') {
			$fields[] = $k;
		}
	}
	if($LOGIN_DATA['user_id'] != $user['user_id']) {
		unset($user['balance']);
		unset($user['email']);
	} else {
		if(count($fields) == 1 && $fields[0] == 'packages') {
			$packages = mysql_query("SELECT package, rating, version FROM user_packages WHERE user_id={$user['user_id']}") or die(mysql_error());
			while($package = mysql_fetch_assoc($packages)) {
				echo "Package: {$package['package']}\n";
				echo "UserRating: {$package['rating']}\n";
				echo "UserOwns: {$package['version']}\n";
				echo "\n";
			}
		}
	}
	if(count($fields) == 1) {
		exit("{$user[$fields[0]]}\n");
	}
	foreach($user as $k => $v) {
		if(count($fields) > 0 && array_search($k, $fields) === FALSE) continue;
		$k = explode('_', $k);
		foreach($k as $i => $tmp) {
			$k[$i]{0} = strtoupper($k[$i]{0});
		}
		$k = implode('-', $k);
		$v = str_replace("\n\n","\n.\n",$v);
		$v = str_replace("\n","\\\n",$v);
		echo "$k: $v\n";
	}
	break;

	case 'application/rss+xml':

	header('Content-Type: application/rss+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8" ?>';
	?>
<rss version="2.0" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
	<title>Recent Activity from <?php echo $user['nickname']; ?></title>
	<link>http://<?php echo $_SERVER['HTTP_HOST'].APPROOT; ?>users/<?php echo htmlspecialchars($user['nickname']); ?></link>
	<?php
	activity_rss('user_activity.private=0 AND user_activity.user_id='.$user['user_id'], false);
	break;

	case 'text/vcard':
	case 'text/x-vcard':
	case 'text/directory':
	
	header('Content-Type: text/directory; profile=vCard; charset=utf-8');
	echo "BEGIN:VCARD\nVERSION:3.0\n";
	echo "UID:http://theveeb.com/users/{$user['user_id']}\n";
	echo "FN:{$user['nickname']}\n";
	echo "NICKNAME:{$user['nickname']}\n";
	echo "PHOTO;VALUE=uri:{$user['photo']}\n";
	if($LOGIN_DATA['user_id'] == $user['user_id']) {
		echo "X-BALANCE:{$user['balance']}\n";
	}
	echo "END:VCARD\n";
	break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':

	require dirname(__FILE__).'/../include/processCookie.php';
	$title = $user['nickname'];
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<link rel="alternate" type="application/rss+xml" title="Actionstream Feed" href="?accept=application/rss+xml" />
		<style type="text/css">
			#contacts {
				float: right;
				clear: both;
			}
			#contacts ul {
				padding: 0;
			}
			#contacts li {
				list-style-type: none;
				float: left;
				margin-right: 0.2em;
			}
			ol.activity, ol.activity li {
				list-style-type: none;
				padding-left: 1em;
			}
			h2.nickname {
				display: inline;
			}
			.vcard .photo {
				max-height: 80px;
				margin-bottom: -0.5em;
			}
			.button {
				display: inline-block;
				font-size: 1em;
				padding: 0.5em;
				margin-top: 0.5em;
				cursor: pointer;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<div class="vcard">
			<img class="photo" src="<?php echo htmlspecialchars($user['photo']); ?>" alt=""<?php if(!$noxml) echo ' /'; ?>>
			<h2 class="fn nickname"><?php echo htmlspecialchars($user['nickname']); ?></h2>
		</div>

		<?php
		require dirname(__FILE__).'/../include/contacts.php';
		$contacts = contacts($user['user_id']);
		?>

		<?php
		if($LOGIN_DATA['user_id'] && $LOGIN_DATA['user_id'] != $user['user_id']) :
		$iz_contact = mysql_query("SELECT user_id FROM user_contacts WHERE user_id={$LOGIN_DATA['user_id']} AND contact_id={$user['user_id']}") or die(mysql_error());
		if(!mysql_fetch_assoc($iz_contact)) :
		$iz_contact = mysql_query("SELECT user_id FROM user_contact_requests WHERE user_id={$LOGIN_DATA['user_id']} AND contact_id={$user['user_id']}") or die(mysql_error());
		if(!mysql_fetch_assoc($iz_contact)) :
		?>
		<form method="post" action="<?php echo APPROOT; ?>users/me"><div>
			<input type="hidden" name="contact" value="<?php echo $user['user_id']; ?>" />
			<input type="hidden" name="_method" value="PUT" />
			<input class="button" type="submit" value="Follow" />
		</div></form>
		<?php
		endif; endif; endif;
		?>

		<h2>Recent Activity</h2>
		<?php
		activity('user_activity.private=0 AND user_activity.user_id='.$user['user_id'], false);
		?>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>
	</body>
</html>
<?php endswitch; ?>

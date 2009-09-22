<?php
	require_once dirname(__FILE__).'/../include/use_type.php';
	require_once dirname(__FILE__).'/../include/check_method.php';
	$method = check_method(array('GET', 'POST'));

	require dirname(__FILE__).'/../include/processCookie.php';

	if(!$LOGIN_DATA['user_id']) {
		header('HTTP/1.1 401 Unauthorized', true, 401);
		header('Content-Type: text/plain; charset=utf-8');
		die("You are not logged in.\n");
	}

	if($method == 'POST') {
		if($_POST['remove_token']) {
			$token = mysql_real_escape_string($_POST['remove_token']);
			mysql_query("DELETE FROM user_tokens WHERE request_shared_key='$token'") or die(mysql_error());
			mysql_query("DELETE FROM access_tokens WHERE request_shared_key='$token'") or die(mysql_error());
		}
		if($_POST['toggle_privacy']) {
			$LOGIN_DATA['private'] = (int)!$LOGIN_DATA['private'];
			mysql_query("UPDATE users SET private={$LOGIN_DATA['private']} WHERE user_id={$LOGIN_DATA['user_id']}") or die(mysql_error());
		}
		if($_POST['toggle_list']) {
			$LOGIN_DATA['list'] = (int)!$LOGIN_DATA['list'];
			$email = escapeshellarg($LOGIN_DATA['email']);
			if($LOGIN_DATA['list']) {
				shell_exec("whoami; echo $email | /usr/sbin/add_members -r - discuss");
			} else {
				shell_exec("/usr/sbin/remove_members discuss $email");
			}
			mysql_query("UPDATE users SET list={$LOGIN_DATA['list']} WHERE user_id={$LOGIN_DATA['user_id']}") or die(mysql_error());
		}
	}

	switch(use_type(array('application/xhtml+xml', 'text/html'), true)) :

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':
	$title = 'Settings';
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<script type="text/javascript" src="../resources/login.js"></script>
		<script type="text/javascript">
			function load_bind() {
				document.getElementById('openid_form').addEventListener('submit', login_submit, false);
			}
			window.addEventListener('load', load_bind, false);
		</script>
		<style type="text/css">
			form, fieldset {
				display: inline;
				border: none;
				padding: 0;
			}
			#settings fieldset {
				width: 40em;
			}
			#settings label, #settings input {
				font-size: 100%;
				height: 1.5em;
				width: 45%;
				margin-bottom: 0.5em;
				vertical-align: top;
			}
			#settings label {
				float: left;
				clear: left;
			}
			#settings textarea {
				width: 45%;
				margin-bottom: 0.5em;
			}
			#settings input[type=submit] {
				width: 10em;
				margin-left: 45%;
			}
			input[type=image] {
				vertical-align: bottom;
			}
			li.openid, #openid_identifier {
				background: url("../resources/openid16.png") no-repeat left center;
				padding-left: 20px;
			}
			#openid_identifier {
				color: black;
				background-color: white;
			}
			li.email {
				background: transparent url("../resources/email.png") no-repeat left center;
				padding-left: 20px;
			}
			li.primary {
				font-weight: bold;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<h2>Settings</h2>

		<form id="settings" method="post" action="<?php echo APPROOT; ?>users/me"><fieldset>
			<input type="hidden" name="_method" value="PUT" />
			<label for="email">Email</label> <input type="text" name="email" value="<?php echo $LOGIN_DATA['email']; ?>" />
			<label for="photo">Avatar URL</label> <input type="text" name="photo" value="<?php echo $LOGIN_DATA['photo']; ?>" />
			<label for="public_key">OpenPGP Key</label> <textarea name="public_key" rows="4" cols="20"></textarea>
			<input type="submit" value="Save" />
		</fieldset></form>

		<form style="display:block;" method="post" action=""><fieldset>
			<input type="submit" name="toggle_privacy" value="<?php echo $LOGIN_DATA['private'] ? 'Show future app installs and ratings in public timelines' : 'Hide future app installs and ratings from public timelines'; ?>" />
		</fieldset></form>

		<form style="display:block;" method="post" action=""><fieldset>
			<input type="submit" name="toggle_list" value="<?php echo $LOGIN_DATA['list'] ? 'Unsubscribe from mailing list' : 'Subscribe to mailing list'; ?>" />
		</fieldset></form>

		<h3 id="balance">Balance</h3>
		<p>Your balance is currently <?php echo (int)$LOGIN_DATA['balance']; ?> ¤</p>
		<p><a href="/paypal/start"><img src="https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif" alt="Pay Now" /></a></p>

		<h3 id="identities">Email Addresses and OpenIDs</h3>
		<ul>
		<?php
			$login_ids = mysql_query("SELECT login_id FROM login_ids WHERE user_id={$LOGIN_DATA['user_id']}") or die(mysql_error());
			while($login_id = mysql_fetch_assoc($login_ids)) :
				$class = 'openid';
				$full_login_id = $login_id['login_id'];
				$login_id = htmlspecialchars($login_id['login_id']);
				if(preg_match('/^http:\/\/email-verify\.appspot\.com\/id\/([^\/]+)$/', $login_id, $match)) {
					$class = 'email';
					$login_id = urldecode($match[1]);
				}
				if($login_id == $LOGIN_DATA['email']) {
					$class .= ' primary';
				}
		?>
			<li class="<?php echo $class; ?>">
				<?php echo $login_id; ?>
				<form method="post" action="<?php echo APPROOT; ?>users/me"><fieldset>
					<input type="hidden" name="_method" value="PUT" />
					<input type="image" src="../resources/delete.png" alt="Remove" title="Remove" name="remove_identity" value="<?php echo htmlspecialchars($full_login_id); ?>" />
				</fieldset></form>
				<?php if($class == 'email') : ?>
				<form method="post" action="<?php echo APPROOT; ?>users/me"><fieldset>
					<input type="hidden" name="_method" value="PUT" />
					<input type="image" src="../resources/accept.png" alt="Make Primary" title="Make Primary" name="email" value="<?php echo $login_id; ?>" />
				</fieldset></form>
				<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>

		<form id="openid_form" method="get" action="<?php echo APPROOT; ?>login/try_auth.php"><fieldset>
			<div id="openid_form_error"></div>
			<input type="hidden" name="action" value="add" />
			<input type="hidden" name="return_to" value="<?php echo htmlentities($_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING']); ?>" />
   			<input type="text" class="openid" id="openid_identifier" name="openid_identifier" value="" />
			<input type="submit" id="openid_form_submit" value="Add Email or OpenID" />
		</fieldset></form>

		<h3 id="authorizations">Application Authorizations</h3>
		<ul>
		<?php
			$tokens = mysql_query("SELECT request_shared_key, consumer_label FROM user_tokens WHERE user_id={$LOGIN_DATA['user_id']}") or die(mysql_error());
			while($token = mysql_fetch_assoc($tokens)) :
				$label = $token['consumer_label'] ? htmlspecialchars($token['consumer_label']) : htmlspecialchars($token['request_shared_key']);
		?>
			<li> <?php echo $label; ?>
			<form method="post" action=""><fieldset>
				<input type="image" src="../resources/delete.png" alt="Revoke access" title="Revoke access" name="remove_token" value="<?php echo htmlspecialchars($token['request_shared_key']); ?>" />
			</fieldset></form></li>
		<?php endwhile; ?>
		</ul>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>

	</body>
</html>
<?php endswitch; ?>

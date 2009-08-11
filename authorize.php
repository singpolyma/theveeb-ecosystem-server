<?php

require dirname(__FILE__).'/include/connectDB.php';
require_once dirname(__FILE__).'/include/processCookie.php';

if(!$LOGIN_DATA['user_id']) {
	header('Location: http://'.$_SERVER['HTTP_HOST'].'/'.dirname($_SERVER['PHP_SELF']).'/login?return_to='.urlencode($_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING']),true,303);
	exit;
}


	header('Content-Type: application/xhtml+xml');
	echo '<?xml version="1.0" encoding="utf-8" ?>'."\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>The Veeb Ecosystem - Authorize Access</title>
		<link rel="stylesheet" media="screen" type="text/css" href="resources/main.css" />
		<link rel="shortcut icon" type="image/png" href="resources/favicon.png" />
		<style type="text/css">
			p, form {
				text-align: center;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/include/visible_header.php'; ?>
<?php

if(!$_REQUEST['oauth_token']) die('You must provide a token to authorize!');

$key = mysql_real_escape_string($_REQUEST['oauth_token'], $db);

$request_token = mysql_query("SELECT consumer_shared_key FROM request_tokens WHERE shared_key='$key' LIMIT 1",$db) or die(mysql_error());
$request_token = mysql_fetch_assoc($request_token);
if(!$request_token) die("No request token with key $key found.");

$consumer = mysql_query("SELECT name,callback FROM consumers WHERE shared_key='{$request_token['consumer_shared_key']}' LIMIT 1",$db) or die(mysql_error());
$consumer = mysql_fetch_assoc($consumer);
if(!$consumer) die("No consumer with key {$request_token['consumer_shared_key']} found.");

if($_POST['deny']) {
	mysql_query("DELETE FROM request_tokens WHERE shared_key='$key' LIMIT 1",$db) or die(mysql_error());
	header('Location: http://'.$_SERVER['HTTP_HOST'].'/'.dirname($_SERVER['PHP_SELF']).'/?oauth_denied', true, 303);
	exit;
}

if($_POST['authorize']) {
	mysql_query("UPDATE request_tokens SET authorized=1 WHERE shared_key='$key'");
	$label = $consumer['name'];
	if($label && $label != 'anonymous') {
		if($_REQUEST['xoauth_consumer_label']) {
			$label .= ' ('.$_REQUEST['xoauth_consumer_label'].')';
		}
	} else {
		$label = $_REQUEST['xoauth_consumer_label'];
	}
	$label = mysql_real_escape_string($label);
	mysql_query("INSERT INTO user_tokens (request_shared_key, user_id, consumer_label) VALUES ('$key', {$LOGIN_DATA['user_id']}, '$label')");
	if($consumer['callback']) {
		header('Location: '.$consumer['callback'], true, 303);
		exit;
	} else {
		echo '<p>You have authorized access to your account.  You may now close this browser window.</p>';
	}
} else {

echo '<p>';
if($consumer['name']) {
	if($consumer['name'] == 'anonymous') {
		echo 'A consumer claiming to be '.htmlspecialchars($_REQUEST['xoauth_consumer_label']).' ';
	} else {
		echo $consumer['name'].' ';
	}
} else {
	echo 'A consumer ';
}
if($consumer['callback']) echo '('.$consumer['callback'].')';
echo ' would like to authorize access to your account with The Veeb.</p>';

?>
<form action="" method="post"><div>
	<input type="submit" name="authorize" value="Authorize" />
	<input type="submit" name="deny" value="Deny" />
</div></form>
	<?php } //end if-else if POST authorize ?>
		<?php require dirname(__FILE__).'/include/visible_footer.php' ?>

	</body>
</html>

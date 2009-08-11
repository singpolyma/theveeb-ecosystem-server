<?php

	require_once dirname(__FILE__).'/../include/check_method.php';

	check_method(array('GET'));

	require dirname(__FILE__).'/../include/use_type.php';
	require dirname(__FILE__).'/../include/connectDB.php';

	$range = '';
	if(preg_match('/^lines=/', $_SERVER['HTTP_RANGE'])) {
		$range = explode('=', $_SERVER['HTTP_RANGE']);
		$range = explode(',', $range[1]);
		$range = ' LIMIT '.((int)$range[0]).','.((int)$range[1]);
	}

	$users = mysql_query("SELECT nickname FROM users $range") or die(mysql_error());

	switch(use_type(array('text/plain', 'application/json'), true)) {

	case 'application/json':
		header('Content-Type: application/json; charset=utf-8');
		echo '[';
		$first = true;
		while($user = mysql_fetch_assoc($users)) {
			if(!$first) {
				echo ',';
			} else {
				$first = false;
			}
			echo '"'.addslashes($user['nickname']).'"';
		}
		echo ']'."\n";
		break;

	case 'text/plain': /* This is a problem if we don't support text/html. Old browsers may get this */
		header('Content-Type: text/plain; charset=utf-8');
		while($user = mysql_fetch_assoc($users)) {
			echo $user['nickname']."\n";
		}
		break;

	}

?>

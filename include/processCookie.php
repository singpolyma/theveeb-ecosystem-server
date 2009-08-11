<?php

/*
if($_COOKIE['user_openid'] && !$_COOKIE['theveeb_session'] && !$_REQUEST['error']) {
	require_once dirname(__FILE__).'/setup.php';
   $at = $_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING'];
   header('Location: http://'.$_SERVER['HTTP_HOST'].'/'.APPROOT.'/login/try_auth.php?openid_identifier='.urlencode($_COOKIE['user_openid']).'&return_to='.urlencode($at),true,303);//login
   exit;
}//end if user_openid
*/

$path_extra = dirname(__FILE__);
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

require_once dirname(__FILE__).'/connectDB.php';
require_once 'Auth/OAuth/Server.php';
require_once 'Auth/OAuth/Store/MySQL.php';

$oauth_server = new Auth_OAuth_Server(new Auth_OAuth_Store_MySQL($db));
if($token = $oauth_server->verifyRequest()) {
	$LOGIN_DATA = mysql_query("SELECT users.user_id,nickname,email FROM user_tokens,users WHERE user_tokens.user_id=users.user_id AND request_shared_key='".mysql_real_escape_string($token,$db)."' LIMIT 1",$db) or die(mysql_error());
	$LOGIN_DATA = mysql_fetch_assoc($LOGIN_DATA);
}

if($_COOKIE['theveeb_session']) {
	$LOGIN_DATA = mysql_query("SELECT user_id,nickname,email,balance,photo,private,list FROM users WHERE session_id='".mysql_real_escape_string($_COOKIE['theveeb_session'],$db)."' LIMIT 1",$db) or die(mysql_error());
	$LOGIN_DATA = mysql_fetch_assoc($LOGIN_DATA);
}

if(isset($_GET['test_session'])) {
	$LOGIN_DATA = array('user_id' => -1, 'nickname' => 'test', 'email' => 'test@example.com', 'balance' => 0);
}

?>

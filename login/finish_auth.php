<?php

require_once dirname(__FILE__).'/common.php';
session_start();

$return = $_SESSION['return_to']; unset($_SESSION['return_to']);
$action = $_SESSION['action']; unset($_SESSION['action']);
if(!$return) $return = 'https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
if(!strstr($return, '?')) $return .= '?';

$process_url = sprintf("https://%s%s/finish_auth.php",
                       $_SERVER['HTTP_HOST'],
                       dirname($_SERVER['PHP_SELF']));

// Complete the authentication process using the server's response.
$response = $consumer->complete($process_url);

if($action == 'add')
	require_once dirname(dirname(__FILE__)).'/include/processCookie.php';

if ($response->status == Auth_OpenID_CANCEL) {
    // This means the authentication was cancelled.
    if($action == 'add')
	    header('Location: https://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['PHP_SELF'])),true,303);//redirect to home
    else
	    header('Location: https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/out.php',true,303);//logout, redirect to home
} else if ($response->status == Auth_OpenID_FAILURE) {
	$msg = "OpenID authentication failed: " . $response->message;
	header('Location: https://theveeb.com/login?error='.urlencode($msg),true,303);
	die;
} else if ($response->status == Auth_OpenID_SUCCESS) {
   require(dirname(__FILE__).'/../include/connectDB.php');//connect to database

	/* Set identity cookie */
	if($_REQUEST['email'])
	   setcookie("user_openid",$_REQUEST['email'],time()+(3600*1000),'/');//set cookie
	else
	   setcookie("user_openid",$response->identity_url,time()+(3600*1000),'/');//set cookie

	/* Get sreg resp or defaults */
	$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
	$sreg = $sreg_resp->contents();
	$fullname = explode(' ', mysql_real_escape_string(@$sreg['fullname'], $db));
	$email = @$sreg['email'] ? mysql_real_escape_string(@$sreg['email'],$db) : mysql_real_escape_string($_REQUEST['email'], $db);
	$nickname = @$sreg['nickname'] ? mysql_real_escape_string(@$sreg['nickname'],$db) : false;

	/* Augment with any AX response there may have been */
	$ax_resp = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response);
	if(($ax_email = $ax_resp->getSingle('http://axschema.org/contact/email')) && !is_a($ax_email, 'Auth_OpenID_AX_Error'))
		$email = mysql_real_escape_string($ax_email, $db);
	if(($ax_nickname = $ax_resp->getSingle('http://axschema.org/namePerson/friendly')) && !is_a($ax_nickname, 'Auth_OpenID_AX_Error'))
		$nickname = mysql_real_escape_string($ax_nickname, $db);
	if(($ax_fullname = $ax_resp->getSingle('http://axschema.org/namePerson')) && !is_a($ax_fullname, 'Auth_OpenID_AX_Error'))
		$fullname = explode(' ', mysql_real_escape_string($ax_fullname, $db));
	
	/* And with any hCard */
	if(!$email || !$nickname || !$photo) {
		$r = explode("\n",shell_exec("representative_hcard.rb '".escapeshellcmd($response->identity_url)."'"));
		if($r[0]) $nickname = mysql_real_escape_string($r[0], $db);
		if($r[1]) $email = mysql_real_escape_string($r[1], $db);
		if($r[2]) $photo = mysql_real_escape_string($r[2], $db);
	}

	if(!$nickname && $email) {
		$nickname = explode('@',$email);
		$nickname = $nickname[0];
	}

	$user = mysql_query("SELECT user_id FROM login_ids WHERE login_id='".mysql_real_escape_string($response->identity_url,$db)."' LIMIT 1", $db) or die(mysql_error());//get user_id
	$user = mysql_fetch_assoc($user);

	if($user && $action == 'add') {
		$msg = 'That OpenID is already in the system!';
		header('Location: '.$return.'&error='.urlencode($msg),true,303);
		die;
	}//end if user && add

	if(!$user) {//non-existant user, create
		if($action != 'add') {
			$identity_url = $response->identity_url;
			if(!$nickname || !$email) {
				require dirname(__FILE__).'/new_user.php';
				exit;
			}
			if(!mysql_query("INSERT INTO users (nickname,email,photo) VALUES ('$nickname','$email','$photo')", $db)) { //insert new user
				if(mysql_errno() == 1062) {
					require dirname(__FILE__).'/new_user.php';
					exit;
				} else {
					die(mysql_error());
				}
			}
			$userid = mysql_insert_id();
		} else $userid = $LOGIN_DATA['user_id'];
		mysql_query("INSERT INTO login_ids (user_id,login_id) VALUES ($userid,'".mysql_real_escape_string($response->identity_url,$db)."')", $db) or die(mysql_error());//insert user's OpenID
		$session_id = sha1('a'.$userid.microtime(true).rand(-999999,999999).'theveeb');
		mysql_query("UPDATE users SET session_id='$session_id', session_timeout=".(time()+60*60*25)." WHERE user_id=".$userid,$db) or die(mysql_error());
		setcookie("theveeb_session",$session_id,0,'/','.theveeb.com');//set cookie
		@mysql_close($db);
		header('Location: '.$return,true,303);//redirect
		exit;
	}//end if user

	$session_id = sha1('a'.$userid.microtime(true).rand(-999999,999999).'theveeb');
	$expire_session = time() + (60*60*24*3);
	mysql_query("UPDATE users SET session_id='$session_id', session_timeout=$expire_session WHERE user_id=".$user['user_id'],$db) or die(mysql_error());
	setcookie("theveeb_session",$session_id,$expire_session,'/','.theveeb.com');//set cookie
	@mysql_close($db);
	header('Location: '.$return,true,303);//redirect
	exit;

}//end if-elses OpenID status

?>

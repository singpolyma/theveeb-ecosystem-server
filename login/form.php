<?php

	require_once dirname(__FILE__).'/../include/setup.php';
	require_once dirname(__FILE__).'/../include/processCookie.php';

	if(!$LOGIN_DATA['user_id']) : ?>
   <form id="login" method="get" action="<?php echo APPROOT; ?>login/try_auth.php"><div>

		<div>Login<sup id="openid-help">(<a href="http://openid.net/what/" rel="help">?</a>)</sup></div>

		<input type="hidden" name="action" value="<?php echo $login_action ? $login_action : 'verify'; ?>" />
		<input type="hidden" name="return_to" value="<?php echo $_REQUEST['return_to'] ? htmlentities($_REQUEST['return_to']) : htmlentities($_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING']); ?>" />
   	<input type="text" onclick="this.select();" class="openid" id="openid_identifier" name="openid_identifier" value="<?php echo $_REQUEST['openid_identifier'] ? htmlentities($_REQUEST['openid_identifier']) : ''; ?>" />
   	<input type="submit" value="&raquo;" />

   </div></form>
<?php else : ?>
	<div id="login">
		Logged in as <?php echo htmlentities($LOGIN_DATA['nickname']); ?><br />
		<a href="<?php echo APPROOT; ?>login/out.php?return_to=<?php echo htmlentities($_SERVER['SCRIPT_URI']); ?>" rel="logout">Logout &raquo;</a>
	</div>
<?php endif; ?>

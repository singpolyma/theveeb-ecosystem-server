		<?php require_once dirname(__FILE__).'/setup.php'; ?>

		<div id="footer">
			<a href="http://creativecommons.org/licenses/by/3.0/" rel="license"><img src="<?php echo APPROOT; ?>resources/by.png" alt="Creative Commons Attribution"<?php if(!$noxml) echo ' /'; ?>></a>
			<a href="<?php echo APPROOT; ?>developers/">Developers</a>
			<a href="http://wiki.theveeb.com/Terms%20of%20Service" rel="terms">Terms and Privacy</a>
			<?php
				require_once dirname(__FILE__).'/processCookie.php';
				if($LOGIN_DATA['user_id']) {
			?>
			<a href="<?php echo APPROOT; ?>settings">Settings</a>
			<a href="<?php echo APPROOT; ?>login/out.php" rel="logout">Logout</a>
			<span>Balance: <?php echo (int)$LOGIN_DATA['balance']; ?> ¤</span>
			<?php
				} else {
			?>
			<a href="<?php echo APPROOT; ?>login/<?php if($_SERVER['REQUEST_URI'] != APPROOT) echo '?return_to='.urlencode($_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING']); ?>" rel="login">Login</a>
			<?php
				}
			?>
			<!--[if IE]>
			<a href="http://www.mozilla.com/?from=sfx&amp;uid=119619&amp;t=306"><img src="http://sfx-images.mozilla.org/affiliates/Buttons/firefox3/110x32_get_ffx.png" alt="Download Firefox" border="0"<?php if(!$noxml) echo ' /'; ?>></a>
			<![endif]-->
		</div>

<?php
	if(!$error && ($_GET['openid_url'] || $_GET['openid_identifier'])) {
		include dirname(__FILE__).'/try_auth.php';
		exit;
	}

	if(!$_REQUEST['return_to'] && stristr($_SERVER['HTTP_REFERER'],'wiki.theveeb.com')) {
		$_REQUEST['return_to'] = $_SERVER['HTTP_REFERER'];
	}

	require_once dirname(__FILE__).'/../include/use_type.php';
	require_once dirname(__FILE__).'/../include/check_method.php';
	check_method(array('GET'));

	require dirname(__FILE__).'/../include/processCookie.php';
	if(!$error) $error = $_GET['error'];

	switch(use_type(array('application/xhtml+xml', 'text/html'), true)) :

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':
	$title = 'Login';
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<style type="text/css">
			#openid_form {
				text-align: center;
			}
			#openid_form_submit {
				font-size: 1.2em;
				margin-top: 1em;
			}
			label {
				display: block;
				font-size: 1.4em;
				margin-bottom: 1em;
			}
			#openid_form_error {
			text-align: center;
			color: white;
			background-color: #f66;
			padding: 1em;
			<?php if(!$error): ?>
			display: none;
			<?php endif; ?>
			}
		</style>
		<script type="text/javascript" src="../resources/login.js"></script>
		<script type="text/javascript">
			function load_bind() {
				document.getElementById('openid_form').addEventListener('submit', login_submit, false);
			}
			window.addEventListener('load', load_bind, false);
		</script>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<div id="openid_form_error"><?php echo htmlentities($error); ?></div>
		<form id="openid_form" method="get" action="">
			<label for="openid_identifier">Login with email address or OpenID</label>
			<div><input style="min-height:33px;font-size:1.6em;padding-left:35px;color:black;background:white url('../resources/openid32.png') no-repeat 1px 1px;" type="text" id="openid_identifier" name="openid_identifier" value="<?php echo addslashes($_COOKIE['user_openid']); ?>" /></div>
			<div><input type="submit" id="openid_form_submit" value="Proceed" /><input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_REQUEST['return_to'] ? $_REQUEST['return_to'] : 'http://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['PHP_SELF'])).'/apps'); ?>" /></div>
		</form>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>

	</body>
</html>
<?php endswitch; ?>

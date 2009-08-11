<?php
	require_once dirname(__FILE__).'/../include/use_type.php';
	require_once dirname(__FILE__).'/../include/check_method.php';
	require dirname(__FILE__).'/../include/processCookie.php';

	if($LOGIN_DATA['user_id'] && check_method(array('GET', 'POST')) == 'POST') {
		$successful_submit = false;
		if($_POST['name'] && $_POST['price'] && $_POST['source_license'] && $_POST['content_license'] && ($_POST['source_file'] || $_POST['source_control'])) {
			require dirname(__FILE__).'/../include/emailclass.php';
			$mail = new sendmail;
			$mail->gpg_add_key('0x0DD626E6');
			$mail->gpg_set_type(GPG_ASYMMETRIC);
			$mail->gpg_set_sign(1);
			$mail->gpg_set_signing_key('0x0DD626E6');
			$mail->gpg_set_homedir('/home/apt/.gnupg/');
			$mail->sender("contact@theveeb.com");
			$mail->from($LOGIN_DATA['email']);
			$mail->add_to('contact@theveeb.com');

			$mail->subject('TVE Application Submission: '.$_POST['name']);

			if($_FILES['source_file']) {
				$mail->attachment($_FILES['source_file']['tmp_name'], $_FILES['source_file']['type'], $_FILES['source_file']['name']);
			}

			foreach($_POST as $key => $val) {
				if($key == 'source_file') continue;
				$mail->body($key.': '.$val."\n");
			}
			$mail->body('User: '.$LOGIN_DATA['user_id']);

			$successful_submit = $mail->send();
		}
	}

	switch(use_type(array('application/xhtml+xml', 'text/html', 'text/plain'), true)) :

	case 'text/plain':
	$text = @file_get_contents(dirname(__FILE__).'/faq.html');
	header('Content-Type: text/plain; charset=utf-8');
	echo wordwrap(strip_tags(str_replace('<li>','* ',$text)));
	break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':
	$title = 'Developers';
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<style type="text/css">
			fieldset {
				position: relative;
				width: 70%;
				margin: auto;
				margin-top: 3em;
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
			input[type=file] {
				height: auto;
			}
			input[type=submit] {
				width: 10em;
				margin-left: 45%;
			}
			textarea {
				width: 45%;
				margin-bottom: 0.5em;
				vertical-align: top;
			}
			#source_license_help, #content_license_help {
				width: 4%;
			}
			.error,.success {
				text-align: center;
				color: black;
				background-color: #f66;
				padding: 1em;
			}
			.success {
				background-color: #6f6;
			}
		</style>
		<script type="text/javascript" src="../resources/login.js"></script>
		<script type="text/javascript" src="../resources/join_now.js"></script>
		<script type="text/javascript">
			function content_license(self) {
				var url = ({
					'by'      : 'http://creativecommons.org/licenses/by/3.0/',
					'by-sa'   : 'http://creativecommons.org/licenses/by-sa/3.0/',
					'by-nc-sa': 'http://creativecommons.org/licenses/by-nc-sa/3.0/',
					'by-nc-nd': 'http://creativecommons.org/licenses/by-nc-nd/3.0/',
					'arr'     : 'http://en.wikipedia.org/wiki/All_Rights_Reserved',
				})[(self || this).value];
				document.getElementById('content_license_help').href = url;
			}
			function source_license(self) {
				var url = ({
					'art2': 'http://www.perlfoundation.org/artistic_license_2_0',
					'isc' : 'https://www.isc.org/software/license',
					'cddl': 'http://www.sun.com/cddl/cddl.html',
					'gpl3': 'http://www.gnu.org/licenses/gpl-3.0.txt'
				})[(self || this).value];
				document.getElementById('source_license_help').href = url;
			}
			function register_handlers() {
				if(document.getElementById('joinbutton'))
					document.getElementById('joinbutton').addEventListener('click', function(e){join_now_button(e, "<?php echo addslashes($_COOKIE['user_openid']); ?>", window.location.href);}, false);
				document.getElementById('source_license').addEventListener('change', source_license, false);
				source_license(document.getElementById('source_license'));
				document.getElementById('content_license').addEventListener('change', content_license, false);
				content_license(document.getElementById('content_license'));
			}
			window.addEventListener('load', register_handlers, false);
		</script>
	</head>

	<body>
		
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<?php require dirname(__FILE__).'/faq.html'; ?>

		<?php if($LOGIN_DATA['user_id']) : ?>
		<form id="submit_app" method="post" enctype="multipart/form-data" action="#submit_app"><fieldset>
			<?php if($successful_submit === FALSE) :  ?>
			<p class="error">There was a problem with your submission.  Did you leave a field blank?</p>
			<?php endif; ?>
			<?php if($successful_submit === TRUE) :  ?>
			<p class="success">Your submission has been sent for consideration.  You will hear from us shortly.</p>
			<?php endif; ?>
			<h2>Submit a Program to the Ecosystem</h2>
			<p>If the licenses in this list will not work for your project, or you have other issues with this form, please write us at <a href="mailto:contact@theveeb.com">contact@theveeb.com</a> and we will try to work something out.</p>
			<label for="name">Name</label> <input type="text" name="name" id="name" />
			<label for="price">Minimum price</label> <input type="text" name="price" id="price" />
			<label for="version">Version</label> <input type="text" name="version" id="version" />
			<label for="source_license">Source License</label>
				<select name="source_license" id="source_license">
					<option value="art2">Artistic License 2.0</option>
					<option value="isc">ISC</option>
					<option value="cddl">CDDL</option>
					<option value="gpl3">GPL 3.0</option>
				</select> <a id="source_license_help" href="#source_licenses">(?)</a>
			<label for="content_license">Content License</label>
				<select name="content_license" id="content_license">
					<option value="by">Creative Commons Attribution</option>
					<option value="by-sa">Creative Commons Attribution ShareAlike</option>
					<option value="by-nc-sa">Creative Commons Attribution NonCommercial ShareAlike</option>
					<option value="by-nc-nd">Creative Commons Attribution NonCommercial NoDerivs</option>
					<option value="arr">All Rights Reserved</option>
				</select> <a id="content_license_help" href="#content_licenses">(?)</a>
			<label for="dependencies">Dependencies</label> <input type="text" name="dependencies" id="dependencies" />
			<label for="description">Description</label> <textarea name="description" id="description" rows="4" cols="20"></textarea>
			<label for="source_file">Source file</label> <input type="file" name="source_file" id="source_file" />
			<label for="source_control">or <abbr title="Version Control System (Git, SVN, etc)">VCS</abbr> URL</label> <input type="text" name="source_control" id="source_control" />
			<input type="submit" value="Submit" />
		</fieldset></form>
		<?php else : ?>
			<a class="button" id="joinbutton" href="<?php echo APPROOT; ?>login">Join Now!</a>
		<?php endif; ?>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>

	</body>
</html>
<?php endswitch; ?>

<?php
	require_once dirname(__FILE__).'/../include/use_type.php';
	require_once dirname(__FILE__).'/../include/check_method.php';
	check_method(array('GET'));

	$other_platforms = array(
			'windows' => '',
			'apple' => '',
			'ubuntu' => ''
		);
	if(stristr($_SERVER['HTTP_USER_AGENT'], 'Ubuntu')) {
		$platform = 'ubuntu';
	} else if(stristr($_SERVER['HTTP_USER_AGENT'], 'Linux')) {
		$platform = 'ubuntu'; // Kinda a hack for now, better than suggesting Windows
	} else if(stristr($_SERVER['HTTP_USER_AGENT'], 'Apple')) {
		$platform = 'apple';
	} else {
		$platform = 'windows';
	}
	unset($other_platforms[$platform]);

	switch(use_type(array('application/xhtml+xml', 'text/html'), true)) :

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':
	$title = 'Download';
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<style type="text/css">
			ul#platforms, ul#platforms li {
				text-align: center;
				margin: 0;
				padding: 0;
				list-style-type: none;
			}
			ul#platforms li {
				font-size: 0.8em;
				display: inline-block;
				margin-right: 2em;
			}
			ul#platforms li a {
				padding-left: 20px;
			}
			a.button {
				margin: 0 auto;
				margin-bottom: 1em;
				width: 7em;
				font-size: 1.2em;
				background-position: 0.7em 1.1em;
			}
			ul#platforms li#current_platform {
				display: block;
			}
			.ubuntu a {
				background-image: url("../resources/ubuntu.png");
				background-repeat: no-repeat;
			}
			.windows a {
				background-image: url("../resources/windows.png");
				background-repeat: no-repeat;
			}
			.apple a {
				background-image: url("../resources/apple.png");
				background-repeat: no-repeat;
			}
			#more_platforms, #tos {
				font-size: 0.8em;
				text-align: center;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<div style="float:right;width:50%;height:200px;border:1px solid black;">Screenshot</div>

		<div style="display:inline-block;width:49%;"> <!-- ugly hack -->
			<p>
				Download and install The Veeb Ecosystem on your computer to get the full benefits of the platform.
				Purchase and install applications, check for updates, and submit feedback to developers.
			</p>

			<ul id="platforms">
				<li id="current_platform" class="<?php echo $platform; ?>"><a href="#<?php echo $platform; ?>" class="button">Download for <?php echo ucfirst($platform); ?></a></li>
				<?php foreach($other_platforms as $platform => $val) : ?>
					<li class="<?php echo $platform; ?>"><a href="#<?php echo $platform; ?>">for <?php echo ucfirst($platform); ?></a></li>
				<?php endforeach; ?>
			</ul>

			<p id="tos">By downloading this application you agree to our <a href="<?php echo APPROOT; ?>terms/" rel="terms">Terms of Service and Privacy Policy</a>.</p>
			<p id="more_platforms">To request support for another platform, <a href="mailto:contact@theveeb.com">email us</a>.</p>
		</div>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>

	</body>
</html>
<?php endswitch; ?>

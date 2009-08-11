<?php
	require_once dirname(__FILE__).'/include/processCookie.php';
	require_once dirname(__FILE__).'/include/use_type.php';
	require_once dirname(__FILE__).'/include/check_method.php';
	check_method(array('GET'));

	switch(use_type(array('application/xhtml+xml', 'text/html', 'text/plain', 'application/rss+xml'), true)) :
	
	case 'application/rss+xml':
	header('Location: https://identi.ca/api/statuses/user_timeline/theveeb.rss', true, 302);
	break;

	case 'text/plain':
	$text = @file_get_contents(dirname(__FILE__).'/home.html');
	header('Content-Type: text/plain; charset=utf-8');
	echo wordwrap(strip_tags(str_replace('<li>','* ',$text)));
	break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml';
	require dirname(__FILE__).'/include/invisible_header.php';
?>
		<link rel="alternate" type="application/rss+xml" title="Microblog Feed" href="?accept=application/rss+xml" />
		<script type="text/javascript" src="resources/login.js"></script>
		<script type="text/javascript" src="resources/join_now.js"></script>
		<script type="text/javascript">
		//<![CDATA[
			function show_feature_paragraph(para) {
				document.getElementById('drm').style.display = para == 'drm' ? 'block' : 'none';
				document.getElementById('drm_heading').style.background = para == 'drm' ? 'url("resources/dot.png") no-repeat right' : 'none';
				document.getElementById('malware').style.display = para == 'malware' ? 'block' : 'none';
				document.getElementById('malware_heading').style.background = para == 'malware' ? 'url("resources/dot.png") no-repeat right' : 'none';
				document.getElementById('updates').style.display = para == 'updates' ? 'block' : 'none';
				document.getElementById('updates_heading').style.background = para == 'updates' ? 'url("resources/dot.png") no-repeat right' : 'none';
			}
			function feature_paragraphs() {
				document.getElementById('drm').style.display = 'none';
				document.getElementById('drm_heading').addEventListener('mouseover', function(e){show_feature_paragraph('drm');}, false);
				document.getElementById('malware').style.display = 'none';
				document.getElementById('malware_heading').addEventListener('mouseover', function(e){show_feature_paragraph('malware');}, false);
				document.getElementById('updates').style.display = 'none';
				document.getElementById('updates_heading').addEventListener('mouseover', function(e){show_feature_paragraph('updates');}, false);
				show_feature_paragraph('drm');
				// doesn't really belong here, but need to fire on load
				if(document.getElementById('joinbutton'))
					document.getElementById('joinbutton').addEventListener('click', function(e){join_now_button(e, "<?php echo addslashes($_COOKIE['user_openid']); ?>", "<?php echo APPROOT; ?>download");}, false);
			}
			function ugly_resize_hack() {
				document.getElementById('drm').style.position = 'absolute';
				document.getElementById('drm').style.overflow = 'auto';
				document.getElementById('drm').style.marginLeft = '36em';
				document.getElementById('drm').style.maxHeight = '14em';
				document.getElementById('drm').style.width = 'auto';
				document.getElementById('malware').style.position = 'absolute';
				document.getElementById('malware').style.overflow = 'auto';
				document.getElementById('malware').style.marginLeft = '36em';
				document.getElementById('malware').style.maxHeight = '14em';
				document.getElementById('malware').style.width = 'auto';
				document.getElementById('updates').style.position = 'absolute';
				document.getElementById('updates').style.overflow = 'auto';
				document.getElementById('updates').style.marginLeft = '36em';
				document.getElementById('updates').style.maxHeight = '14em';
				document.getElementById('updates').style.width = 'auto';

				if((document.getElementById('drm').clientWidth && document.getElementById('drm').clientWidth < 290)
				   || (document.getElementById('malware').clientWidth && document.getElementById('malware').clientWidth < 290)
				   || (document.getElementById('updates').clientWidth && document.getElementById('updates').clientWidth < 290)
				   ) {
					document.getElementById('drm').style.position = 'static';
					document.getElementById('drm').style.overflow = 'visible';
					document.getElementById('drm').style.marginLeft = '0px';
					document.getElementById('drm').style.maxHeight = 'none';
					document.getElementById('drm').style.width = document.getElementById('drm_heading').clientWidth+'px';
					document.getElementById('malware').style.position = 'static';
					document.getElementById('malware').style.overflow = 'visible';
					document.getElementById('malware').style.marginLeft = '0px';
					document.getElementById('malware').style.maxHeight = 'none';
					document.getElementById('malware').style.width = document.getElementById('malware_heading').clientWidth+'px';
					document.getElementById('updates').style.position = 'static';
					document.getElementById('updates').style.overflow = 'visible';
					document.getElementById('updates').style.marginLeft = '0px';
					document.getElementById('updates').style.maxHeight = 'none';
					document.getElementById('updates').style.width = document.getElementById('updates_heading').clientWidth+'px';
				}
			}
			window.addEventListener('load', ugly_resize_hack, false);
			window.addEventListener('load', feature_paragraphs, false);
			window.addEventListener('resize', ugly_resize_hack, false);
		//]]>
		</script>
	</head>

	<body>
		<?php require dirname(__FILE__).'/include/visible_header.php'; ?>
		<?php require dirname(__FILE__).'/home.html'; ?>
		<?php require dirname(__FILE__).'/include/visible_footer.php'; ?>
	</body>
</html>
<?php endswitch; ?>

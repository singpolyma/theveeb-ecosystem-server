<?php

	require_once dirname(__FILE__).'/include/processCookie.php';
	require_once dirname(__FILE__).'/include/setup.php';
	require_once dirname(__FILE__).'/include/use_type.php';
	require_once dirname(__FILE__).'/include/check_method.php';

	check_method(array('GET'));
	$d = '';
	$f = explode('/', $_GET['f']);
	if(count($f) == 2 && !strstr($f[0],'.')) $d = $f[0].'/';
	$f = basename($_GET['f']);

	$text = @file_get_contents(dirname(__FILE__).'/'.$d.$f.'.html');
	if(!$text) {
		header('Location: http://wiki.theveeb.com/'.$d.$f);
		exit;
		//header('Content-Type: text/html; charset=utf-8',true,404);
		//die('Not found.');
	}
	$title = explode("\n",$text,2);
	$title = strip_tags($title[0]);

	switch(use_type(array('application/xhtml+xml', 'text/html', 'text/plain'), true)) :

	case 'text/plain':

	header('Content-Type: text/plain; charset=utf-8');
	echo wordwrap(strip_tags(str_replace('<li>','* ',$text)));
	break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':

	require dirname(__FILE__).'/include/invisible_header.php';
?>
	</head>

	<body>
		<?php require dirname(__FILE__).'/include/visible_header.php'; ?>
		<?php require dirname(__FILE__).'/'.$d.$f.'.html'; ?>
		<?php require dirname(__FILE__).'/include/visible_footer.php' ?>
	</body>
</html>
<?php endswitch; ?>

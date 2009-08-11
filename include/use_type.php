<?php

function use_type($out_types, $else_error=false) {
	header('Vary: Accept-Encoding, Accept'); // We're switching based on Accept, so yeah

	/* JSONP */
	if(array_search('text/javascript', $out_types) !== FALSE) {
		if($_GET['callback']) {
			return 'text/javascript';
		}
	}

	/* OVERRIDE */
	if($_GET['accept']) {
		$_SERVER['HTTP_ACCEPT'] = str_replace(' ','+',$_GET['accept']);
	}

	$accept = preg_split('/\s*,\s*/', $_SERVER['HTTP_ACCEPT']);
	$c = count($accept);
	if($c < 1 || ($c == 1 && !$accept[0])) {
		$accept = array('*/*');
		$c = 1;
	}

	$accepts = array();
	foreach($accept as $i => $type) {
		$arr = explode(';', $type);
		$type = trim(array_shift($arr));
		$q = 2.0;
		foreach($arr as $a) {
			$a = explode('=',$a,2);
			if(trim($a[0]) == 'q') {
				$q = (float)trim($a[1]);
				if($q < 0) $q = 0;
				if($q > 1) $q = 1;
				break;
			}
		}
		$q -= (float)substr_count($type, '*');
		$q *= 1000;
		$pattern = '/'.str_replace('\*','.+',preg_quote($type, '/')).'/';
		if($accepts[$q]) {
			$accepts[$q][] = $pattern;
		} else {
			$accepts[$q] = array($pattern);
		}
	}

	krsort($accepts);
	foreach($accepts as $patterns) {
		foreach($out_types as $otype) {
			foreach($patterns as $pattern) {
				if(preg_match($pattern, $otype)) {
					return $otype;
				}
			}
		}
	}

	if($else_error) {
		header('Content-Type: text/plain; charset=utf-8', true, 406);
		echo implode("\n",$out_types)."\n";
		exit;
	} else {
		return NULL;
	}
}

?>

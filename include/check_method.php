<?php

function check_method($methods) {
	$method = $_SERVER['REQUEST_METHOD'];
	if($method == 'POST' && $_POST['_method']) $method = strtoupper($_POST['_method']);
	if(array_search('GET', $methods) !== FALSE) {
		$methods[] = 'HEAD';
	}
	if(array_search($method, $methods) === FALSE) {
		if($method != 'OPTIONS') {
			header('Allow: '.implode(',', $methods), true, 405);
		} else {
			header('Allow: '.implode(',', $methods));
		}
		header('Content-Type: text/plain; charset=utf-8');
		if($method != 'HEAD') {
			echo implode("\n", $methods);
		}
		die;
	}
	return $method;
}

?>

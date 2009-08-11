<?php

require_once dirname(__FILE__).'/setup.php';

if($noxml) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
} else {
	header('Content-Type: application/xhtml+xml; charset=utf-8');
	echo '<?xml version="1.0" encoding="utf-8" ?>'."\n";
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
}

?>

<html<?php if(!$noxml) echo ' xmlns="http://www.w3.org/1999/xhtml"'; ?> id="theveeb">
	<head>
		<title>The Veeb Ecosystem<?php if($title) echo ' - '.htmlspecialchars($title); ?></title>
		<link rel="stylesheet" media="screen" type="text/css" href="<?php echo APPROOT; ?>resources/main.css"<?php if(!$noxml) echo ' /'; ?>>
		<link rel="shortcut icon" type="image/png" href="<?php echo APPROOT; ?>resources/favicon.png"<?php if(!$noxml) echo ' /'; ?>>


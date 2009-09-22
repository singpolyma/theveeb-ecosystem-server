<?php

if($_POST['test_ipn']) {
	exit;
}

// post back to PayPal system to validate
$req = 'cmd=_notify-validate';
foreach ($_POST as $key => $value) {
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);

if (!$fp) {
	die('ERROR');
} else {
	fputs($fp, $header . $req);
	while(!feof($fp)) {
		$res = fgets($fp, 1024);
		if($res == 'INVALID') {
			mail('contact@theveeb.com', 'PayPal ERROR', print_r($res,true)."\n\n\n".print_r($_REQUEST, true)."\n\n\n".$header.$req);
			die('INVALID');
		}
	}
	fclose($fp);
}

// check that txn_id has not been previously processed
if($_POST['txn_type'] == 'web_accept') {
	if(!$_POST['custom'] || $_POST['receiver_email'] != 'contact@theveeb.com' || $_POST['quantity'] < 1 || $_POST['mc_currency'] != 'CAD') {
		die('INVALID');
	}

	$amount = ($_POST['mc_gross']-($_POST['shipping']+$_POST['handling_amount']+$_POST['tax']))/0.50;
	$quantity = intval($_POST['quantity']);
	if($quantity > $amount) {
		die('INVALID');
	}

	if($_POST['payment_status'] == 'Completed') {
		require_once dirname(__FILE__).'/../../include/connectDB.php';
		$invoice = mysql_real_escape_string($_POST['custom'], $db);
		$user = mysql_query("SELECT user_id FROM paypal_transactions WHERE invoice=$invoice", $db) or die(mysql_error());
		$user = mysql_fetch_assoc($user);
		if($user) {
			$user = intval($user['user_id']);
			if(!$user) exit;
			mysql_query("DELETE FROM paypal_transactions WHERE invoice=$invoice", $db) or die(mysql_error());
			mysql_query("UPDATE users SET balance=balance+$quantity WHERE user_id=$user") or die(mysql_error());
		}
	}
}

?>

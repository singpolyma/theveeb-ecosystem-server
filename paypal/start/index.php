<?php

require dirname(__FILE__).'/../../include/processCookie.php';
if(!$LOGIN_DATA['user_id']) {
	header('Location: /login');
	exit;
}

mysql_query("INSERT INTO paypal_transactions (user_id) VALUES (".$LOGIN_DATA['user_id'].")", $db) or die(mysql_error());
$invoice = mysql_insert_id($db);

$quantity = $_GET['quantity'] ? intval($_GET['quantity']) : 10;
$undefq = isset($_GET['defined_quantity']) ? '' : '&undefined_quantity=1';
$page_style = isset($_GET['style2']) ? 'TVE2' : 'TVE1';

header('Location: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=contact@theveeb.com&item_name=Credit&amount=0.50&quantity='.$quantity.$undefq.'&page_style='.$page_style.'&no_note=1&no_shipping=1&return=https://theveeb.com/settings&cancel_return=https://theveeb.com/settings&notify_url=https://theveeb.com/paypal/ipn&currency_code=CAD&charset=utf-8&custom='.$invoice);

?>

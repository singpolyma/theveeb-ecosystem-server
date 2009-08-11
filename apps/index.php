<?php

	require_once dirname(__FILE__).'/../include/check_method.php';
	check_method(array('GET'));

	require dirname(__FILE__).'/../include/use_type.php';
	require dirname(__FILE__).'/../include/connectDB.php';
	require dirname(__FILE__).'/../include/activity.php';

	switch(use_type(array('application/xhtml+xml', 'text/html', 'application/json', 'text/plain', 'text/javascript', 'application/rss+xml'), true)) :

	case 'application/rss+xml':
		require dirname(__FILE__).'/../include/processCookie.php';
		header('Content-Type: application/rss+xml; charset=utf-8');
		$contacts = array();
		$constrain = 'user_activity.private=0';
		if($LOGIN_DATA['user_id']) {
			$contacts_result = mysql_query("SELECT contact_id FROM user_contacts WHERE user_id={$LOGIN_DATA['user_id']}");
			while($contact = mysql_fetch_assoc($contacts_result)) {
				$contacts[] = (int)$contact['contact_id'];
			}
		}
		if(count($contacts) > 0) {
			$constrain = ' IN ('.implode(',', $contacts).')';
		}
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		?>
<rss version="2.0" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title>Recent Activity<?php if(count($contacts) > 0) echo ' from '.htmlspecialchars($LOGIN_DATA['nickname']).'\'s contacts'; ?></title>
		<link>http://<?php echo $_SERVER['HTTP_HOST'].APPROOT; ?>apps/</link>
		<?php
		activity_rss($constrain);
		break;

	case 'text/javascript':
		$js = true;
		header('Content-Type: text/javascript; charset=utf-8');
		$callback = $_GET['callback'];
		if($callback) echo $callback.'(';
	case 'application/json':
		if(!$js) header('Content-Type: application/json; charset=utf-8');
		$packages = mysql_query("SELECT package FROM packages") or die(mysql_error());
		echo '[';
		$first = true;
		while($package = mysql_fetch_assoc($packages)) {
			if(!$first) {
				echo ',';
			} else {
				$first = false;
			}
			echo '"'.addslashes($package['package']).'"';
		}
		echo ']';
		if($callback) echo ')';
		break;

	case 'text/plain': /* This is a problem if we don't support text/html. Old browsers may get this */
		header('Content-Type: text/plain; charset=utf-8');
		$packages = mysql_query("SELECT package FROM packages") or die(mysql_error());
		while($package = mysql_fetch_assoc($packages)) {
			echo $package['package']."\n";
		}
		break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':

	require dirname(__FILE__).'/../include/processCookie.php';
	$title = 'Apps';
	require dirname(__FILE__).'/../include/invisible_header.php';
?>
		<link rel="alternate" type="application/rss+xml" title="Actionstream Feed" href="?accept=application/rss+xml" />
		<style type="text/css">
			ul#featured, #contacts {
				float: right;
				clear: both;
			}
			#contacts ul {
				padding: 0;
			}
			#contacts li, ul#featured li {
				list-style-type: none;
				float: left;
				margin-right: 0.2em;
			}
			ol.activity, ol.activity li {
				list-style-type: none;
				padding-left: 1em;
			}
			form, fieldset {
				display: inline;
				border: none;
				padding: 0;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<ul id="featured">
		<?php
		$apps = mysql_query("SELECT * FROM (SELECT packages.package, AVG(rating) as avg_rating FROM packages LEFT JOIN user_packages ON packages.package=user_packages.package GROUP BY user_packages.package) as t WHERE avg_rating > 3 ORDER BY rand() LIMIT 5") or die(mysql_error());
		while($app = mysql_fetch_assoc($apps)) :
		?>
			<li>
				<img src="/images/apps/<?php echo htmlspecialchars($app['package']); ?>"
				     alt="<?php echo htmlspecialchars($app['package']); ?>" />
			</li>
		<?php endwhile; ?>
		</ul>

		<?php
		$contacts = array();
		if($LOGIN_DATA['user_id']) {
			require dirname(__FILE__).'/../include/contacts.php';
			$contacts = contacts($LOGIN_DATA['user_id']);

			$contact_requests = mysql_query("SELECT users.user_id,nickname FROM user_contact_requests,users WHERE user_contact_requests.user_id=users.user_id AND contact_id={$LOGIN_DATA['user_id']}") or die(mysql_error());
			if(mysql_num_rows($contact_requests) > 0) {
				echo '<h2>Contact Requests</h2><ul>';
				while($contact_request = mysql_fetch_assoc($contact_requests)) : ?>
				<li>
					<a href="<?php echo APPROOT; ?>users/<?php echo htmlspecialchars($contact_request['nickname']); ?>"><?php echo htmlspecialchars($contact_request['nickname']); ?></a>
					<form method="post" action="<?php echo APPROOT; ?>users/me"><fieldset>
						<input type="hidden" name="contact_request" value="<?php echo $contact_request['user_id']; ?>" />
						<input type="submit" name="action" value="Authorize" />
						<input type="submit" name="action" value="Deny" />
					</fieldset></form>
				</li>
				<?php
				endwhile;
				echo '</ul>';
			}
		}
		?>

		<h2>Recent Activity<?php if(count($contacts) > 0) echo ' From Your Contacts'; ?></h2>
		<?php
		$constrain = 'user_activity.private=0';
		if(count($contacts) > 0) {
			$constrain = ' user_activity.user_id IN (';
			foreach($contacts as $contact) {
				$constrain .= $contact['contact_id'].',';
			}
			$constrain .= '-1)';
		}
		activity($constrain);
		?>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>
	</body>
</html>
<?php endswitch; ?>

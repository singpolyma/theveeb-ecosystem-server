<?php

	require dirname(__FILE__).'/../include/connectDB.php';

	$package = mysql_real_escape_string($_GET['package'],$db);
	$package = mysql_query("SELECT name, packages.package, price, packages.version, AVG(rating) AS rating, count(user_packages.user_id) AS installs, developer_user_id, users.nickname AS developer_nickname, users.email AS developer_email, users.public_key AS developer_public_key FROM packages LEFT JOIN user_packages ON packages.package=user_packages.package LEFT JOIN users ON packages.developer_user_id=users.user_id WHERE packages.package='$package' GROUP BY user_packages.package LIMIT 1") or die(mysql_error());
	if(!($package = mysql_fetch_assoc($package))) {
		header('HTTP/1.1 404 Not Found', true, 404);
		header('Content-Type: text/plain; charset=utf-8');
		die('That app does not exist.');
	}

	unset($_GET['package']);
	require dirname(__FILE__).'/../include/processCookie.php';

	$user_rating = FALSE;
	if($LOGIN_DATA['user_id']) {
		$user_rating = mysql_query("SELECT rating from user_packages WHERE package='{$package['package']}' AND user_id={$LOGIN_DATA['user_id']} LIMIT 1") or die(mysql_error());
		$user_rating = mysql_fetch_assoc($user_rating);
		if($user_rating) {
			$user_rating = $user_rating['rating'];
		} else {
			$user_rating = NULL;
		}
	}

	require_once dirname(__FILE__).'/../include/check_method.php';
	switch(check_method(array('GET', 'PURCHASE', 'POST', 'PUT'))) :

	case 'PUT':
		if(!$LOGIN_DATA['user_id']) {
			header('HTTP/1.1 401 Unauthorized', true, 401);
			header('Content-Type: text/plain; charset=utf-8');
			die("You are not logged in.\n");
		}
		parse_str(file_get_contents('php://input'), $_PUT);
		if($_PUT['rating']) {
			if($user_rating === FALSE) {
				header('HTTP/1.1 401 Unauthorized', true, 401);
				header('Content-Type: text/plain; charset=utf-8');
				die("You have not purchased or installed that app.\n");
			}
			$rating = (float)$_PUT['rating'];
			mysql_query("UPDATE user_packages SET rating=$rating WHERE user_id={$LOGIN_DATA['user_id']} AND package={$package['package']}") or die(mysql_error());
			mysql_query("INSERT INTO user_activity (user_id, verb, item, private, timestamp) VALUES ({$LOGIN_DATA['user_id']}, 'rated', '{$package['package']}', {$LOGIN_DATA['private']}, ".time().")") or die(mysql_error());
		}
		echo "OK\n";
		break;

	case 'POST':
		if(!$LOGIN_DATA['user_id']) {
			header('HTTP/1.1 401 Unauthorized', true, 401);
			header('Content-Type: text/plain; charset=utf-8');
			die("You are not logged in.\n");
		}
		if($user_rating === FALSE) {
			header('HTTP/1.1 401 Unauthorized', true, 401);
			header('Content-Type: text/plain; charset=utf-8');
			die("You have not purchased or installed that app.\n");
		}
		switch($_POST['item']) {
			case 'feedback':
				$body = <<<BODY
{$LOGIN_DATA['nickname']} has sent you {$_POST['item']} feedback about your app "{$package['name']}" version {$_POST['version']}.

They are using {$_POST['os']} (platform: {$_POST['platform']}) version {$_POST['osVersion']} on {$_POST['machine']}.

{$_POST['body']}
BODY;
			require dirname(__FILE__).'/../include/emailclass.php';
			if($package['developer_public_key']) {
				$mail = new sendmail;
				$mail->gpg_add_key("0x{$package['developer_public_key']}");
				$mail->gpg_set_type(GPG_ASYMMETRIC);
				$mail->gpg_set_sign(1);
			} else {
				$mail = new sendmail_gpgsign;
			}
			$mail->gpg_set_signing_key('0x0DD626E6'); // Verifies it was sent by our server
			$mail->gpg_set_homedir('/home/apt/.gnupg/');
			$mail->sender("contact@theveeb.com");
			$mail->from('"'.$LOGIN_DATA['nickname'].'" <'.$LOGIN_DATA['email'].'>');
			$mail->add_to($package['developer_email']);
			$mail->subject("[TVE] {$_POST['type']} feedback for {$package['package']}");
			$mail->body($body);
			if($mail->send()) {
				header('Content-Type: text/plain; charset=utf-8');
				echo "OK\n";
			} else {
				header('HTTP/1.1 500 Internal Error', true, 500);
				header('Content-Type: text/plain; charset=utf-8');
				die("Could not send message.\n");
			}
			
			default:
				header('HTTP/1.1 400 Bad Request', true, 400);
				header('Content-Type: text/plain; charset=utf-8');
				die("Unrecognised item or no item specified.\n");
		}
		break;

	case 'PURCHASE':
		if(!$LOGIN_DATA['user_id']) {
			header('HTTP/1.1 401 Unauthorized', true, 401);
			header('Content-Type: text/plain; charset=utf-8');
			die("You are not logged in.\n");
		}
		// Check if the user has already bought this, or it's free
		// XXX if we ever allow charging for upgrades, this logic will have to do magic with version numbers matching
		$gratis = $package['price'] == 0 || $user_rating !== FALSE;
		// Make sure they have enough
		if(!$gratis && $package['price'] > $LOGIN_DATA['balance'] && $package['developer_user_id'] != $LOGIN_DATA['user_id']) {
			header('HTTP/1.1 403 Forbidden', true, 403);
			header('Content-Type: text/plain; charset=utf-8');
			die("Balance too low.\n");
		}
		if(!$gratis) {
			mysql_query("UPDATE users SET balance=balance-{$package['price']} WHERE user_id={$LOGIN_DATA['user_id']}") or die(mysql_error()); // Reduce their balance
			$transfer = (int)($package['price']*0.7);
			mysql_query("UPDATE users SET balance=balance+{$transfer} WHERE user_id={$package['developer_user_id']}") or die(mysql_error()); // Transfer funds to developer
		}
		mysql_query("INSERT INTO user_packages (user_id, package, version) VALUES ({$LOGIN_DATA['user_id']}, '{$package['package']}', '{$package['version']}') ON DUPLICATE KEY UPDATE version='{$package['version']}'") or die(mysql_error()); // Transfer funds to developer
		if($user_rating === FALSE && $package['price'] > 0) {
			mysql_query("INSERT INTO user_activity (user_id, verb, item, private, timestamp) VALUES ({$LOGIN_DATA['user_id']}, 'purchased', '{$package['package']}', {$LOGIN_DATA['private']}, ".time().")") or die(mysql_error());
		}
		header('Content-Type: text/plain; charset=utf-8');
		echo "OK\n";
		break;

	case 'HEAD':
	case 'GET':

	$fields = array();
	foreach($_GET as $k => $v) {
		if(!preg_match('/^x?oauth/', $k) && $k != 'callback' && $k != 'accept') {
			$fields[] = $k;
		}
	}
	$title = $package['package'];

	require dirname(__FILE__).'/../include/use_type.php';
	switch(use_type(array('application/xhtml+xml', 'text/html', 'application/json', 'text/plain', 'text/javascript', 'application/rss+xml'), true)) :

	case 'application/rss+xml':
		header('Content-Type: application/rss+xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="utf-8" ?>';
		?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title>Recent Reviews for <?php echo htmlspecialchars($package['name']); ?></title>
		<link>http://<?php echo $_SERVER['HTTP_HOST'].APPROOT; ?>apps/<?php $package['package']; ?></link>
		<?php
		$reviews = mysql_query("SELECT nickname, photo, review, rating FROM user_reviews,user_packages,users WHERE user_packages.user_id=user_reviews.user_id AND user_packages.package=user_reviews.package AND users.user_id=user_reviews.user_id AND user_reviews.package='{$package['package']}' ORDER BY timestamp DESC LIMIT 10") or die(mysql_error());
		while($review = mysql_fetch_assoc($reviews)) : ?>
		<item>
			<title><?php echo htmlspecialchars($review['nickname'].' ('); for($i = 0; $i < $review['rating']; $i++) { echo '*'; } echo ')'; ?></title>
			<link>http://<?php echo $_SERVER['HTTP_HOST'].APPROOT; ?>apps/<?php $package['package']; ?>#review-<?php echo md5($review['review'].$review['timestamp']); ?></link>
			<description><?php echo htmlspecialchars($review['review']); ?></description>
			<dc:creator><?php echo htmlspecialchars($review['nickname']); ?></dc:creator>
			<pubDate><?php echo date('r', $review['timestamp']); ?></pubDate>
		</item>
		<?php endwhile; ?>
	</channel>
</rss>
		<?php
		break;

	case 'text/plain':
		header('Content-Type: text/plain; charset=utf-8');
		unset($package['developer_email']);
		unset($package['developer_public_key']);
		if($user_rating !== FALSE) $package['user_rating'] = $user_rating;
		if(count($fields) == 1) {
			exit("{$package[$fields[0]]}\n");
		}
		foreach($package as $k => $v) {
			if(count($fields) > 0 && array_search($k, $fields) === FALSE) continue;
			$k = explode('_', $k);
			foreach($k as $i => $tmp) {
				$k[$i]{0} = strtoupper($k[$i]{0});
			}
			$k = implode('-', $k);
			$v = str_replace("\n\n","\n.\n",$v);
			$v = str_replace("\n","\\\n",$v);
			echo "$k: $v\n";
		}
		break;

	case 'text/javascript':
		$js = true;
		header('Content-Type: text/javascript; charset=utf-8');
		$callback = $_GET['callback'];
		if($callback) echo $callback.'(';
	case 'application/json':
		if(!$js) header('Content-Type: application/json; charset=utf-8');
		unset($package['developer_email']);
		unset($package['developer_public_key']);
		if($user_rating !== FALSE) $package['user_rating'] = $user_rating;
		if(count($fields) > 0) {
			$t = array();
			foreach($fields as $field) {
				$t[$field] = $package[$field];
			}
			echo json_encode($t);
		} else {
			echo json_encode($package);
		}
		if($callback) echo ')';
		break;

	case 'text/html':
	$noxml = true;

	case 'application/xhtml+xml':
	require dirname(__FILE__).'/../include/invisible_header.php';

?>
		<link rel="alternate" type="application/rss+xml" title="Reviews Feed" href="?accept=application/rss+xml" />
		<style type="text/css">
			#app_photo {
				float: left;
				margin-right: 1em;
			}
			dl {
				margin-left: 3em;
			}
			dt {
				float: left;
				width: 10em;
			}
			#reviews {
				margin-top: 1em;
				border-top: 1px dotted #ccc;
				list-style-type: none;
				padding-left: 3em;
			}
			#reviews .author {
				font-size: 0.8em;
				padding-left: 3em;
			}
		</style>
	</head>

	<body>
		<?php require dirname(__FILE__).'/../include/visible_header.php'; ?>

		<div id="app_photo"><img class="photo" src="<?php echo APPROOT; ?>resources/app-images/<?php echo htmlspecialchars($package['package']); ?>.png" alt=""<?php if(!$noxml) echo ' /'; ?>></div>
		<h2>
			<span id="fn" class="fn"><?php echo htmlspecialchars($package['name'] ? $package['name'] : $package['package']); ?></span>
			by <span class="vcard developer author"><a class="url fn nickname" href="<?php echo APPROOT; ?>users/<?php echo htmlspecialchars($package['developer_nickname']); ?>"><?php echo htmlspecialchars($package['developer_nickname']); ?></a></span>
		</h2>
		<dl>
			<dt>Installs</dt>
				<dd><?php echo (int)$package['installs']; ?></dd>
			<dt>Price</dt>
				<dd><span class="price"><?php echo htmlspecialchars($package['price']); ?></span> ¤</dd>
			<dt>Rating</dt>
				<dd class="rating">
					<span class="value-title" title="<?php echo (float)$package['rating']; ?>"> </span>
					<?php for($i = 0; $i < $package['rating']; $i++) : ?>
					<img src="<? echo APPROOT; ?>resources/star-yellow.png" alt="*"<?php if(!$noxml) echo ' /'; ?>>
					<?php endfor; ?>
					<?php for($i = 0; $i < 5-$package['rating']; $i++) : ?>
					<img src="<? echo APPROOT; ?>resources/star-white.png" alt=""<?php if(!$noxml) echo ' /'; ?>>
					<?php endfor; ?>
				</dd>
			<?php if($user_rating) : ?>
			<dt>Your rating</dt>
				<dd>
					<span class="value-title" title="<?php echo (float)$user_rating; ?>"> </span>
					<?php for($i = 0; $i < $user_rating; $i++) : ?>
					<img src="<? echo APPROOT; ?>resources/star-red.png" alt="*"<?php if(!$noxml) echo ' /'; ?>>
					<?php endfor; ?>
					<?php for($i = 0; $i < 5-$user_rating; $i++) : ?>
					<img src="<? echo APPROOT; ?>resources/star-white.png" alt=""<?php if(!$noxml) echo ' /'; ?>>
					<?php endfor; ?>
				</dd>
			<?php endif; ?>
		</dl>

		<ul id="reviews" class="hfeed">
		<?php
		$reviews = mysql_query("SELECT nickname, photo, review, rating, timestamp FROM user_reviews,user_packages,users WHERE user_packages.user_id=user_reviews.user_id AND user_packages.package=user_reviews.package AND users.user_id=user_reviews.user_id AND user_reviews.package='{$package['package']}' ORDER BY user_packages.rating DESC") or die(mysql_error());
		while($review = mysql_fetch_assoc($reviews)) :
		$review['photo'] = $review['photo'] ? $review['photo'] : 'http://gravatar.com/avatar/'.md5($review['email']).'?s=20&d=wavatar';
		?>
		<li class="hentry hreview" id="review-<?php echo md5($review['review'].$review['timestamp']); ?>">
			<p class="entry-content description"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
			<span class="entry-title">
			<span class="vcard author reviewer">
				<img src="<?php echo $review['photo']; ?>" style="max-height:1em;" alt="" class="photo"<?php if(!$noxml) echo ' /'; ?>>
				<a class="url fn nickname" href="<?php echo APPROOT.'users/'.htmlspecialchars($review['nickname']); ?>">
				<?php echo htmlspecialchars($review['nickname']); ?></a>
			</span>
			<span class="rating">
				<span class="value-title" title="<?php echo $review['rating']; ?>"> </span>
				<?php for($i = 0; $i < $review['rating']; $i++) : ?>
				<img src="<? echo APPROOT; ?>resources/star-red.png" style="height:0.5em;" alt="*"<?php if(!$noxml) echo ' /'; ?>>
				<?php endfor; ?>
				<?php for($i = 0; $i < 5-$review['rating']; $i++) : ?>
				<img src="<? echo APPROOT; ?>resources/star-white.png" style="height:0.5em;" alt=""<?php if(!$noxml) echo ' /'; ?>>
				<?php endfor; ?>
			</span>
			</span>
			<span class="published dtreviewed"><span class="value-title" title="<?php echo date('c', $review['timestamp']); ?>"> </span></span>
			<a class="include item fn" href="#fn"></a>
		</li>
		<?php endwhile; ?>
		</ul>

		<?php require dirname(__FILE__).'/../include/visible_footer.php' ?>
	</body>
</html>
<?php endswitch; ?>
<?php endswitch; ?>

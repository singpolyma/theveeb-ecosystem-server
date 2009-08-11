<?php

require dirname(__FILE__).'/../include/setup.php';

function single_activity($item, $showauthor=true) {
		$r = array();
		$photo = $item['photo'] ? $item['photo'] : 'http://gravatar.com/avatar/'.md5($item['email']).'?s=40&d=wavatar';
		?>
			<span class="entry-title entry-content">
				<span class="vcard author actor" <?php if(!$showauthor) echo 'style="display:none;"'; ?>>
					<a class="url fn nickname" href="http://<? echo $_SERVER['HTTP_HOST'].APPROOT; ?>users/<?php echo htmlspecialchars($item['nickname']); ?>">
						<img src="<?php echo htmlspecialchars($photo); ?>"
						     alt="" style="max-height:1.5em;"
						     class="photo" />
						<?php echo htmlspecialchars($item['nickname']); ?>
					</a>
				</span>
				<span class="verb"><?php echo htmlspecialchars($item['verb']); ?></span>
				<?php
					switch($item['verb']) {
						case 'purchased':
						case 'installed':
							$r['bookmark'] = 'http://'.$_SERVER['HTTP_HOST'].APPROOT.'apps/'.$item['item'];
							$r['comments'] = 'http://'.$_SERVER['HTTP_HOST'].APPROOT.'apps/'.$item['item'].'#reviews';
							$r['commentRss'] = 'http://'.$_SERVER['HTTP_HOST'].APPROOT.'apps/'.$item['item'].'?accept=application/rss+xml';
							echo '<a class="object" rel="bookmark" href="'.htmlspecialchars($r['bookmark']).'">'.htmlspecialchars($item['item']).'</a>';
							break;
						default:
							echo '<span class="object">'.$item['item'].'</span>';
					}
				?>
			</span>
				<span class="published">
					<span class="value-title" title="<?php echo date('c', $item['timestamp']); ?>"> </span>
					<?php
						$diff = (time() - $item['timestamp'])/60;
						if($diff < 60) {
							echo 'minutes';
						} else if(($diff=$diff/60) < 24) {
							echo 'hours';
						} else if(($diff=$diff/24) < 7) {
							echo 'days';
						} else if(($diff=$diff/7) < 4) {
							echo 'weeks';
						} else {
							echo 'months';
						}
					?> ago
				</span>
<?php
	return $r; 
}

function activity_rss($constrain, $showauthor=true) {
		$activities = mysql_query("SELECT nickname,photo,email,verb,item,timestamp FROM user_activity,users WHERE user_activity.user_id=users.user_id AND $constrain ORDER BY timestamp DESC LIMIT 10") or die(mysql_error());
		?>

		<?php while($activity = mysql_fetch_assoc($activities)) :
			ob_start();
			$r = single_activity($activity, $showauthor);
			$text = ob_get_contents();
			ob_end_clean();
		?>
		<item>
			<title><?php echo htmlspecialchars(strip_tags($text)); ?></title>
			<description><?php echo htmlspecialchars($text); ?></description>
			<?php if($r['bookmark']) : ?>
			<link><?php echo htmlspecialchars($r['bookmark']); ?></link>
			<?php endif; ?>
			<?php if($r['comments']) : ?>
			<comments><?php echo htmlspecialchars($r['comments']); ?></comments>
			<?php endif; ?>
			<?php if($r['commentRss']) : ?>
			<wfw:commentRss><?php echo htmlspecialchars($r['commentRss']); ?></wfw:commentRss>
			<?php endif; ?>
			<dc:creator><?php echo htmlspecialchars($activity['nickname']); ?></dc:creator>
			<category><?php echo htmlspecialchars($activity['verb']); ?></category>
			<pubDate><?php echo date('r', $activity['timestamp']); ?></pubDate>
		</item>
		<?php endwhile; ?>
	</channel>
</rss>
<?php
}

function activity($constrain='1=1',$showauthor=true) {
	$activity = mysql_query("SELECT nickname,photo,email,verb,item,timestamp FROM user_activity,users WHERE user_activity.user_id=users.user_id AND $constrain ORDER BY timestamp DESC LIMIT 10") or die(mysql_error());
?>
		<ol class="activity hfeed">
		<?php
		while($item = mysql_fetch_assoc($activity)) {
			echo '<li class="hentry">';
			single_activity($item, $showauthor);
			echo '</li>';
		}
		?>
		</ol>
<?php } ?>

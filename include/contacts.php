<?php function contacts($userid) { $contacts = array(); ?>
		<?php
		$contact_result = mysql_query("SELECT contact_id,photo,nickname,email FROM user_contacts,users WHERE user_contacts.contact_id=users.user_id AND user_contacts.user_id=$userid") or die(mysql_error());
		if(mysql_num_rows($contact_result) > 0) :
		?>
			<div id="contacts">
			<h2>Contacts</h2>
			<ul>
		<?php
		while($contact = mysql_fetch_assoc($contact_result)) :
			$photo = $contact['photo'] ? $contact['photo'] : 'http://gravatar.com/avatar/'.md5($contact['email']).'?s=50&d=wavatar';
			$contacts[] = $contact;
		?>
				<li class="contact vcard">
					<a class="url" re="contact" href="/users/<?php echo htmlspecialchars($contact['nickname']); ?>">
						<img src="<?php echo htmlspecialchars($photo); ?>"
						     alt="<?php echo htmlspecialchars($contact['nickname']); ?>"
						     style="max-height:50px;" class="photo fn nickname" />
					</a>
				</li>
		<?php endwhile; ?>
			</ul>
			</div>
<?php endif; return $contacts; } ?>

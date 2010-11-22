<?php

if (sizeof($_POST)) {
	$this->set('share_twitter', isset($_POST['share_twitter']));
	$this->set('share_facebook', isset($_POST['share_facebook']));
}

$share_with_friends = array();
if ($this->get('share_twitter'))
	$share_with_friends['share_twitter'] = true;
if ($this->get('share_facebook'))
	$share_with_friends['share_facebook'] = true;

?><div id='shareWithFriends_admin'>
	<div>
		<input type='checkbox' id='share_twitter_enable' name='share_twitter' value='yes'<?php echo (isset($share_with_friends['share_twitter']) ? ' checked=\'checked\'' : ''); ?> %XHTML_CLOSE_TAG%>
		<label for='share_twitter_enable'><?php echo htmlspecialchars(_t('Enable share on Twitter.')); ?></label>
	</div>
	<div>
		<input type='checkbox' id='share_facebook_enable' name='share_facebook' value='yes'<?php echo (isset($share_with_friends['share_facebook']) ? ' checked=\'checked\''  : ''); ?> %XHTML_CLOSE_TAG%>
		<label for='share_facebook_enable'><?php echo htmlspecialchars(_t('Enable share on Facebook.')); ?></label>
	</div>
</div>
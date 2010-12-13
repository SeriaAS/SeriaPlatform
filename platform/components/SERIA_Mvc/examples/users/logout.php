<?php
	$action = SERIA_User::logoutAction();
	if($action->success)
	{ // a user was logged out
		header('Location: http://some/url');
		die();
	}
?>
Please <a href='<?php echo $action->url; ?>'>click here</a> to logout.

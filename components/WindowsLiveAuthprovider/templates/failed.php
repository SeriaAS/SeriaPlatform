<?php
	ob_start();
?>
<form method='get'>
	<div>
		<h1 class='legend'><?php echo _t('Login failed.'); ?></h1>
		<p><?php echo _t('Unfortuantely the login failed. Please try again or try to log in using a different provider or username and password.'); ?></p>
		<p><?php echo _t('The provider returned this error message: %ERROR%', array('ERROR' => $this->message)); ?></p>
		<div>
			<button type='button' onclick="location.href = {{loginOptionsString|toJson|htmlspecialchars}};"><?php echo _t('Return to login page'); ?></button>
			<button type='submit' onclick="location.href = {{retryString|toJson|htmlspecialchars}}; return false;"><?php echo _t('Retry'); ?></button>
		</div>
	</div>
</form>
<?php
	$contents = ob_get_clean();

	$title = _t('Login failed.');
	$gui = new SERIA_Gui($title);
	$gui->title($title);
	$gui->contents($contents);
	echo $gui->output();
?>
<?php

$gui = new SERIA_Gui(_t('Create or link to existing user'));

ob_start();
?><h1><?php echo htmlspecialchars(_t('First login')); ?></h1>
<p><?php echo htmlspecialchars(_t('You appear not to have logged in on this system before. If you have an account from before you can link this identity to that by clicking on the \'Link to existing account\'-button. Otherwise check that the information below is filled correctly in, and then create a new user.')); ?></p>
<button onclick="<?php echo htmlspecialchars('location.href = '.SERIA_Lib::toJSON($linktoUrl)); ?>" type='button'><?php echo htmlspecialchars(_t('Link to existing account')); ?></button>
<h2><?php echo htmlspecialchars(_t('Register as a new user')); ?></h2>
<?php
	echo $form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/displayTableForm.php');
	$gui->contents(ob_get_clean());
	echo $gui->output();
?>
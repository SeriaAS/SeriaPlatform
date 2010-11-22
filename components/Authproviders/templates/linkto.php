<?php
	$gui = new SERIA_Gui(_t('Link ID to a previously created account'));

	ob_start();

	if ($linked) {
		?>
		<form method='post'>
			<input type='hidden' name='goahead' value='yes' %XHTML_CLOSE_TAG%>
			<div>
				<h1><?php echo htmlspecialchars(_t('Successfully linked with previously created account')); ?></h1>
				<p><?php echo htmlspecialchars(_t('You have successfully proved your identity and "%PROVIDER%" has been added to your identity. You can log in using this method in addition to previously registered methods.', array('PROVIDER' => $providerName))); ?></p>
				<button type='submit'><?php echo htmlspecialchars(_t('Continue')); ?></button>
			</div>
		</form>
		<?php
		return;
	}
	$title = _t('Link this ID to a previously created account');
	$explain = _t('To link this ID to a previously created account you have to prove that you own this account. Please log in using your previously registered ID.')
?>
<h1><?php echo htmlspecialchars($title); ?></h1>
<p><?php echo htmlspecialchars($explain); ?></p>
<h2><?php echo htmlspecialchars(_t('Alternative login mechanisms'))?></h2>
<ul>
<?php
	foreach ($secondaryLogin as $provider) {
		?>
			<li><a href="<?php echo htmlspecialchars($provider['url']); ?>"><?php echo htmlspecialchars($provider['name']); ?></a></li>
		<?php
	}
?>
</ul>
<?php
	$gui->contents(ob_get_clean());

	echo $gui->output();

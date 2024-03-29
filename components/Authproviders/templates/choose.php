<h1><?php echo htmlspecialchars(_t('Login using one of the methods below')); ?></h1>
<p><?php echo htmlspecialchars(_t('This site supports several authentication methods. Please choose one of these:')); ?></p>
<ul>
<?php
foreach ($providers as $provider) {
	$state = false;
	if (SERIA_AuthenticationState::available())
		$state = new SERIA_AuthenticationState();
	$url = SERIA_Authproviders::getProviderUrl($provider, isset($_GET['continue']) ? $_GET['continue'] : false, SERIA_Url::current(), $state);
	?>
		<li><a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($provider->getName()); ?></a></li>
	<?php
}
?>
</ul>
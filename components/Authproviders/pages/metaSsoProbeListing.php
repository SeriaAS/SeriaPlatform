<?php
	SERIA_Authproviders::loadProviders();
	$providers = SERIA_Authproviders::getProviders();

	$map = array();
	foreach ($providers as $provider) {
		if ($provider->isAvailable() && $provider->isEnabled(SERIA_IAuthprovider::LOGIN_GUEST))
			$map[$provider->getProviderId()] = get_class($provider);
	}
?><s:gui title="{'SSO Probe url listing'}">
	<h1 class='legend'>{{'SSO Probe url listing'}}</h1>
	<ul>
	<?php
		foreach ($map as $id => $name) {
			?>
			<li>
				<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/?route='.rawurlencode('seria/Authproviders/metaSsoProbe').'&id='.rawurlencode($id).'&class='.rawurlencode($name)); ?>"><?php echo htmlspecialchars($name); ?></a>
			</li>
			<?php
		}
	?>
	</ul>
</s:gui>
<s:gui title="{'Authprovider(s) configuration needed'|_t|htmlspecialchars}">
	<?php
		$listContents = null;
		$configureProvider = null;
		ob_start();
		$count = 0;
		SERIA_Authproviders::loadProviders();
		$providers = SERIA_Authproviders::getProviders();
		foreach ($providers as $provider) {
			$providerName = SERIA_AuthprovidersConfiguration2::getLocalProviderName($provider);
			if ($providerName === null) {
				$classname = get_class($providerName);
				if ($classname == 'SERIA_ExternalAuthprovider')
					$providerName = _t('External: %SITE%', array('SITE' => $provider->get('remote')));
				else
					$providerName = $provider->getName();
			}
			if (SERIA_AuthprovidersConfiguration2::providerIsEnabled($provider) &&
			    SERIA_AuthprovidersConfiguration2::needToBeConfigured($provider)) {
			    if (isset($_GET['configureProviders']) && $_GET['configureProviders'] && !$configureProvider)
			    	$configureProvider = $provider;
				?>
					<li>{{$providerName}}</li>
				<?php
				$count++;
			}
		}
		if ($configureProvider) {
		   	ob_end_clean();
		   	SERIA_AuthprovidersConfiguration2::callProviderConfiguration(
		   		$configureProvider,
		   		SERIA_Url::current()->setParam('configureProviders', mt_rand())->__toString(),
		   		SERIA_HTTP_ROOT.'?route=components/authproviders/config',
		   		$count > 1 ? _t('Next >>') : _t('Finish')
		   	);
		} else
			$listContents = ob_get_clean();
		if (!$count) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/');
			die();
		}
		if ($listContents) {
			?>
				<form method='GET'>
					<?php
						$params = $_GET;
						$params['configureProviders'] = mt_rand();
						foreach ($params as $name => $value) {
							?>
								<input type='hidden' name="{{$name|htmlspecialchars}}" value="{{$value|htmlspecialchars}}">
							<?php
						}
					?>
					<h1 class='legend'>{{'Authprovider(s) configuration needed'|_t}}</h1>
					<p>{{'Some authentication providers need to be configured to work. They are shown below, click continue to be guided through the configuration now.'|_t}}</p>
					<ul>
						<?php echo $listContents; ?>
					</ul>
					<input type='submit' value="{{'Next >>'|_t|htmlspecialchars}}">
					<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=components/authproviders/config')); ?>;" value="{{'Cancel'|htmlspecialchars}}">
				</form>
			<?php
		}
	?>
</s:gui>
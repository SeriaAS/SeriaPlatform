<s:gui title="{'Authproviders configuration'|_t|htmlspecialchars}">
	<h1 class='legend'>{{'Authproviders configuration'|_t}}</h1>
	<?php
		$action = SERIA_AuthprovidersConfiguration2::getConfigurationForm();
		$providers = SERIA_Authproviders::getProviders();
		$enabled = array();
		$disabled = array();
		$providerIds = array();
		foreach ($providers as $provider) {
			$providerName = SERIA_AuthprovidersConfiguration2::getLocalProviderName($provider);
			if ($providerName === null)
				continue;
			if (SERIA_AuthprovidersConfiguration2::providerIsEnabled($provider))
				$enabled[] = array('name' => $providerName, 'provider' => $provider);
			else
				$disabled[] = array('name' => $providerName, 'provider' => $provider);
			$providerIds[] = $provider->getProviderId();
		}
		if ($action->success) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=components/authproviders/configcheck');
			die();
		} else {
			$authsource = $action->get('authsource');
			echo $action->begin();
			?>
				<div id='hideElementsHereByJs'></div>
				<script type='text/javascript'>
					<!--
						<?php
							ob_start();
							foreach ($providerIds as $id) {
								echo $action->field($id);
							}
							$hideFields = ob_get_clean();
						?>
						(function () {
							var hideFields = <?php echo SERIA_Lib::toJSON($hideFields); ?>;
							var container = document.getElementById('hideElementsHereByJs');

							container.style.display = 'none';
							container.innerHTML = hideFields;
						})(); 
					-->
				</script>
				<div style='overflow: hidden;'>
					<input style='float: left;' id='authsource_local' name='authsource' value='local' type='radio'<?php if ($authsource == 'local') echo ' checked=\'checked\''; ?>>
					<fieldset id='settings_local' style='float: left; width: 750px; overflow: hidden;'>
						<legend><label for='authsource_local'>{{'Local login'|_t}}</label></legend>

						<div id='local_login'>
							<p>{{'This is the default. This works by asking for a username and password when you try to log in (like before). If you need to extend this by allowing users to log in by using Google, Facebook, Twitter, Windows Live or another external identity provider, you can enable that below. That is for the more advanced user. Click on the add-button below to enable external identity providers. Most of them do require additional configuration.'|_t}}</p>
							<h2>{{'Add authentication providers (forexample Google)'|_t}}</h2>
							<p>{{"You don't need any providers here to use username and password authentication."|_t}}</p>
							<?php
								if (count($enabled)) {
									?>
										<ul id='enabledProviders'>
									<?php
									foreach ($enabled as $prov) {
										?>
											<li id="<?php echo htmlspecialchars('prov_'.$prov['provider']->getProviderId()); ?>">{{$prov.name}} (<a onclick="disableProvider(<?php echo htmlspecialchars(SERIA_Lib::toJSON($prov['provider']->getProviderId())); ?>, {{$prov.name|toJson|htmlspecialchars}}); return false;" href='#'>{{'Delete'|_t}}</a>)</li>
										<?php
									}
									?>
										</ul>
									<?php
								} else {
									?>
									<div id='noEnabledProviders'>{{'Currently using username and password authentication.'|_t}}</div>
									<?php
								}
								if (count($disabled)) {
									?>
										<input id='addLocalButton' type='button' onclick='showAddProvider();' value="{{'Add'|_t|htmlspecialchars}}">
										<fieldset id='addLocalProviders'>
											<legend>{{'Add'|_t}}</legend>
											<ul id='disabledProviders'>
												<script type='text/javascript'>
													<!--
														function enableProvider(providerId, name)
														{
															var providerCheckbox = document.getElementsByName(providerId)[0];
															var providerListItem = document.getElementById('prov_' + providerId);
															var enabledProviders = document.getElementById('enabledProviders');
															var noEnabledProviders = document.getElementById('noEnabledProviders');
															var addButton = document.getElementById('addLocalButton');
															var addList = document.getElementById('addLocalProviders');

															addButton.disabled = false;
															addList.style.display = 'none';
															providerCheckbox.checked = 'checked';
															providerListItem.parentNode.removeChild(providerListItem);
															providerListItem.innerHTML = name;
															(function () {
																var textBlock1 = document.createTextNode(' (');
																var a = document.createElement('a');
																var textBlock2 = document.createTextNode(')');

																a.setAttribute('href', '#');
																a.onclick = function () {
																	disableProvider(providerId, name);
																	return false;
																};
																a.innerHTML = {{'Delete'|_t|toJson}};
																providerListItem.appendChild(textBlock1);
																providerListItem.appendChild(a);
																providerListItem.appendChild(textBlock2);
															})();
															if (noEnabledProviders) {
																enabledProviders = document.createElement('ul');
																enabledProviders.id = 'enabledProviders';
																$(noEnabledProviders).replaceWith(enabledProviders);
															}
															enabledProviders.appendChild(providerListItem);
														}
														function disableProvider(providerId, name)
														{
															var providerCheckbox = document.getElementsByName(providerId)[0];
															var providerListItem = document.getElementById('prov_' + providerId);
															var disabledProviders = document.getElementById('disabledProviders');
															var a = document.createElement('a');

															providerCheckbox.checked = false;
															providerListItem.parentNode.removeChild(providerListItem);
															providerListItem.innerHTML = '';
															a.setAttribute('href', '#');
															a.onclick = function () {
																enableProvider(providerId, name);
																return false;
															};
															a.innerHTML = name;
															providerListItem.appendChild(a);
															disabledProviders.appendChild(providerListItem);
														}
													-->
												</script>
									<?php
									foreach ($disabled as $prov) {
										?>
											<li id="<?php echo htmlspecialchars('prov_'.$prov['provider']->getProviderId()); ?>">
												<a onclick="<?php echo htmlspecialchars('enableProvider('.SERIA_Lib::toJSON($prov['provider']->getProviderId()).', '.SERIA_Lib::toJSON($prov['name']).'); return false;'); ?>" href='#'>{{$prov.name}}</a>
											</li>
										<?php
									}
									?>
											</ul>
										</fieldset>
										<script type='text/javascript'>
											<!--
												(function () {
													var addButton = document.getElementById('addLocalButton');
													var addList = document.getElementById('addLocalProviders');

													addList.style.display = 'none';
													addButton.onclick = function () {
														addButton.disabled = 'disabled';
														addList.style.display = 'block';
													}
												})();
											-->
										</script>
									<?php
								}
							?>
						</div>
					</fieldset>
				</div>
				<div style='overflow: hidden;'>
					<input style='float: left;' id='authsource_external' name='authsource' value='external' type='radio'<?php if ($authsource == 'external') echo ' checked=\'checked\''; ?>>
					<fieldset style='float: left; width: 750px; overflow: hidden;' id='settings_external'>
						<legend><label for='authsource_external'>{{'Log in using an external Seria Platform'}}</label></legend>
						<p>{{'You can delegate the authentication task to another Seria Platform instance. To enable this you have to enter RPC-keys. Just enter the hostname here and proceed by clicking the save-button. You will then be asked for the RPC-key if you have not done that before.'|_t}}</p>

						<div id='external_login'>
							<label>{{'Enter hostname:'|_t}} <?php echo $action->field('external_hostname'); ?></label>
						</div>
					</fieldset>
				</div>
				<script type='text/javascript'>
					<!--
						(function () {
							var addEvent = function (obj, evType, fn){ 
								if (obj.addEventListener){ 
									obj.addEventListener(evType, fn, false); 
									return true; 
								} else if (obj.attachEvent){ 
									var r = obj.attachEvent("on"+evType, fn); 
									return r; 
								} else { 
									return false; 
								} 
							};
							var enableFieldset = function (obj) {
								if (obj.blockage) {
									obj.blockage.parentNode.removeChild(obj.blockage);
									obj.blockage = false;
								}
							};
							var disableFieldset = function (obj) {
								if (!obj.blockage) {
									obj.style.position = 'relative';
									obj.blockage = document.createElement('div');
									obj.blockage.setAttribute('style', 'position: absolute; top: 0px; left: 0px; opacity: 0.5; filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; background-color: white; width: 100%; height: 100%;');
									obj.appendChild(obj.blockage);
								}
							};
							(function () {
								var button = document.getElementById('authsource_local');
								var buttons = document.getElementsByName(button.name);
								var settings = document.getElementById('settings_local');
								var updateSettings = function () {
									if (button.checked)
										enableFieldset(settings);
									else
										disableFieldset(settings);
								}
								for (var i = 0; buttons[i]; i++) {
									addEvent(buttons[i], 'change', updateSettings);
									addEvent(buttons[i], 'click', updateSettings);
								}
								updateSettings();
							})();
							(function () {
								var button = document.getElementById('authsource_external');
								var buttons = document.getElementsByName(button.name);
								var settings = document.getElementById('settings_external');
								var updateSettings = function () {
									if (button.checked)
										enableFieldset(settings);
									else
										disableFieldset(settings);
								}
								for (var i = 0; buttons[i]; i++) {
									addEvent(buttons[i], 'change', updateSettings);
									addEvent(buttons[i], 'click', updateSettings);
								}
								updateSettings();
							})();
						})();
					-->
				</script>
				<?php
			echo $action->submit(_t('Save'));
			echo $action->end();
		}
	?>
</s:gui>
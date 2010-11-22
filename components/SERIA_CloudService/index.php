<?php
	/**
	*	The purpose of this page is to provide a common place for service APIs to be available for the user.
	*/
	require('common.php');
	$gui->activeMenuItem('controlpanel/settings/services/cloud');

	$contents = '<h1 class="legend">'._t("Seria Platform Cloud Service Providers").'</h1>';

	$cloudProviders = SERIA_Hooks::dispatch(SERIA_CloudServiceHooks::GET_PROVIDERS);

	if(sizeof($cloudProviders))
	{
		$contents .= '<ul>';
		foreach($cloudProviders as $providers)
		{
			if(is_array($providers))
			{
				foreach($providers as $provider)
				{
					$info = $provider->getInfo();
					$contents .= '<li><a href="'.$info['url'].'">'.$info['accountName'].' ('.$info['serviceName'].')</a></li>';
				}
			}
		}
		$contents .= '</ul>';
	}
	else
		$contents .= '<p><em>No cloud APIs available</em></p>

TODO: Allow user to add an arbitrary number of clouds, of any type. Two different Amazon clouds should be possible using different credentials.
';

	$gui->contents($contents);
	$gui->output();

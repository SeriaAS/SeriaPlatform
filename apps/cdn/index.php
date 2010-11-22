<?php
	require(dirname(__FILE__).'/common.php');

	$gui->title(_t('Dashboard'));
	$gui->activeMenuItem('cdn');

	$gui->contents('<h1 class="legend">'._t('Seria Content Delivery Network manager').'</h1>
<p>'._t('Seria CDN is a web application that work together with any caching reverse proxy to deliver high performance Content Delivery services for multiple sites, trough one installation.').'</p>
<p>'._t('Please access the tools you require from the menu above.').'</p>
');

	$gui->output();

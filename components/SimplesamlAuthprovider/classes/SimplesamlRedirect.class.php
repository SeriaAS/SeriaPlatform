<?php

class SimplesamlRedirect extends SERIA_Url
{
	public function __construct($url)
	{
		if (!session_id())
			session_start();
		if (!isset($_SESSION['simplesaml_redirect']))
			$_SESSION['simplesaml_redirect'] = array();
		$key = 'k'.mt_rand().mt_rand();
		$_SESSION['simplesaml_redirect'][$key] = $url;
		parent::__construct(SERIA_HTTP_ROOT.'/seria/components/SimplesamlAuthprovider/pages/sessredirect.php?key='.urlencode($key));
	}
}

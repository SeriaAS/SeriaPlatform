<?php
	require_once(dirname(__FILE__)."/main.php");

	function seria_pageNotFound($url)
	{
		SERIA_Base::displayErrorPage(404, _t("Page not found"), _t("The page '%URL%' that you requested could not be found.", array("URL" => $url)));
	}

	SERIA_Hooks::listen(SERIA_ROUTER_HOOK, 'seria_pageNotFound', 1000000);

//	$q = ltrim(rawurldecode($_GET['q']), '/');
	$q = ltrim($_GET['q'], '/');

	$url = parse_url($_SERVER['REQUEST_URI']);
	if ($url['query']) {
		parse_str($url['query'], $_GET);

		/*
		 * Compatibility: Code should assume that magic quotes is off.
		 * Therefore we have to stripslashes since parse_str has magic q.
		 */
		if (get_magic_quotes_gpc()) {
			$process = array(&$_GET);
			while (list($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
	} else
		$_GET = array();

	SERIA_Hooks::dispatchToFirst(SERIA_ROUTER_HOOK, $q);

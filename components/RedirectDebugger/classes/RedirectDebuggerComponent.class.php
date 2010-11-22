<?php

class RedirectDebuggerComponent extends SERIA_Component
{
	function getId()
	{
		return 'redirect_debugger';
	}
	function getName()
	{
		return _t('Redirect debugger');
	}
	function embed()
	{
		SERIA_Hooks::listen(SERIA_Base::REDIRECT_DEBUG_HOOK, array($this, 'redirect'));
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	public function redirect($url)
	{
		ob_start();
		debug_print_backtrace();
		$bt = ob_get_clean();
		if (strpos($bt, "\r") !== false && strpos($bt, "\n") === false)
			$bt = str_replace("\r", "\n", $bt);
		$bt = str_replace("\r", '', $bt);
		$bt = explode("\n", $bt);
		/*
		 * Check for invalid location hdr
		 */
		$location = $url;
		$colon = strpos($url, ':');
		$slash = strpos($url, '/');
		$dblslash = strpos($url, '//');
		if ($colon === false || ($slash !== false && $slash < $colon)) {
			if ($slash === 0) {
				/*
				 * Invalid:
				 * Location: /....
				 */
				$url = SERIA_HTTP_ROOT.$url;
			} else {
				/*
				 * Invalid: Even relative path
				 * Location: .../...
				 */
				$url = SERIA_HTTP_ROOT.$_SERVER['REQUEST_URI'].$url;
			}
		} else if ($colon != ($dblslash - 1)) {
			/*
			 * Invalid: Even relative path
			 * Location: .../...
			 */
			$url = SERIA_HTTP_ROOT.$_SERVER['REQUEST_URI'].$url;
		}
		$template = new SERIA_MetaTemplate();
		$template->addVariable('location', $location);
		$template->addVariable('url', $url);
		$template->addVariable('backtrace', $bt);
		echo $template->parse($this->getTemplate('redirect'));
		die();
	}
	public function getTemplate($name)
	{
		$search_order = array(
			SERIA_ROOT.'/templates/RedirectDebugger/'.$name.'.php',
			SERIA_ROOT.'/seria/components/RedirectDebugger/templates/'.$name.'.php'
		);
		foreach ($search_order as $search) {
			if (file_exists($search))
				return $search;
		}
		return null;
	}
}
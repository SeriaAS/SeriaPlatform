<?php

class SERIA_AuthproviderActions
{
	public static function getDirectGuestLoginAction()
	{
		$action = new SERIA_ActionUrl('directGuestLogin');
		if ($action->invoked()) {
			$pu =& SERIA_Authproviders::getAllProviderUrls(
				SERIA_Url::current()->unsetParam('directGuestLogin')->__toString(), /* continue */
				SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/guestLogin.php', /* loginPage */
				SERIA_IAuthprovider::LOGIN_GUEST
			);
			if (count($pu) < 1)
				throw new SERIA_Exception('No guest providers available.');
			if (count($pu) > 1)
				throw new SERIA_Exception('This action works only for one single guest provider.');
			$ka = array_keys($pu);
			$providerId = $ka[0];
			$provider =& $pu[$providerId];
			$url = $provider['url'];
			unset($provider);
			unset($pu);
			SERIA_Base::redirectTo($url);
			die();
		}
		return $action;
	}
	public static function getGuestLoginActions()
	{
		$state = new SERIA_AuthenticationState();
		$actions = array();
		$pu =& SERIA_Authproviders::getAllProviderUrls(
			$state->stampUrl(SERIA_Url::current()->unsetParam('guestProvider'))->__toString(), /* continue */
			SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/guestLogin.php', /* loginPage */
			SERIA_IAuthprovider::LOGIN_GUEST
		);
		foreach ($pu as $providerId => &$provider)
			$actions[$providerId] = array(
				'class' => $provider['class'],
				'name' => $provider['name'],
				'action' => new SERIA_ActionAuthenticationStateUrl('guestProvider', $providerId, $state)
			);
		unset($provider);
		foreach ($actions as $providerId => $action) {
			if ($action['action']->invoked()) {
				$provider =& $pu[$providerId];
				unset($pu);
				$url = $provider['url'];
				unset($provider);
				SERIA_Base::redirectTo($url);
				die();
			}
		}
		unset($pu);
		return $actions;
	}

	/**
	 * 
	 * Returns a login action object if the user is not logged in and has system access. Null otherwise.
	 * @return SERIA_ActionAuthenticationStateUrl
	 */
	public static function getLoginAction()
	{
		if (SERIA_Base::hasSystemAccess())
			return null;
		$state = new SERIA_AuthenticationState();
		$action = new SERIA_ActionAuthenticationStateUrl('login', 'system', $state);
		if ($action->invoked()) {
			SERIA_Base::pageRequires('logout'); /* Logout guest */
			if(SERIA_CUSTOM_PAGES_ROOT && file_exists(SERIA_CUSTOM_PAGES_ROOT."/login.php"))
			{
				SERIA_Base::redirectTo($state->stampUrl(SERIA_CUSTOM_PAGES_HTTP_ROOT."/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"])));
				die();
			}
			SERIA_Base::redirectTo($state->stampUrl(SERIA_HTTP_ROOT."/seria/platform/pages/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"])));
			die();
		}
		return $action;
	}
	/**
	 * 
	 * Returns a login action object if the user is not logged in and has guest access. Null otherwise.
	 * @return SERIA_ActionAuthenticationStateUrl
	 */
	public static function getGuestLoginAction()
	{
		if (SERIA_Base::user() && !SERIA_Base::hasSystemAccess())
			return null;
		$state = new SERIA_AuthenticationState();
		$action = new SERIA_ActionAuthenticationStateUrl('login', 'guest', $state);
		if ($action->invoked()) {
			SERIA_Base::pageRequires('logout'); /* Logout guest */
			if (file_exists(SERIA_ROOT.'/login.php')) {
				SERIA_Base::redirectTo($state->stampUrl(SERIA_HTTP_ROOT."/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"])));
				die();
			}
			SERIA_Base::redirectTo($state->stampUrl(SERIA_HTTP_ROOT."/seria/components/Authproviders/pages/guestLogin.php?continue=".rawurlencode($_SERVER["REQUEST_URI"])));
			die();
		}
		return $action;
	}
}
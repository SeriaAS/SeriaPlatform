<?php

interface SERIA_IAuthprovider
{
	const LOGIN_SYSTEM = 0;
	const LOGIN_GUEST = 1;
	const LOGIN_AUTO = 2;

	public static function loadProviders();

	/**/
	public function getProviderId();
	public function getName();
	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM);
	public function isAvailable();
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM);
	public function authenticate($interactive=true, $reset=false, $guestLogin=false);
	public static function automaticDiscoveryPreCheck(); 
	public function automaticDiscovery();
	public function logout();
}
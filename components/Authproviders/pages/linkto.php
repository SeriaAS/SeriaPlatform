<?php

require_once(dirname(__FILE__).'/../../../main.php');

$component = SERIA_Components::getComponent('seria_authproviders');
if (!$component)
	throw new SERIA_Exception('Authproviders component is not enabled!');

if (!isset($_GET['id']))
	throw new SERIA_Exception('No id supplied for tracking.');
$state = new SERIA_AuthenticationState();

if (!$state->exists($_GET['id'])) {
	unset($_SESSION['authproviders_return_url'][$_GET['proceed']]);
	$state->terminate('abort');
}

$linkdata = $state->get($_GET['id']);

$backtomeParams = array(
	'id' => $_GET['id'],
	'proceed' => $_GET['proceed'],
	'returned' => 'yes'
);
$backtomeUrl = $state->stampUrl(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/linkto.php?'.http_build_query($backtomeParams));

$attributes = $linkdata['attributes'];
$unique = $attributes['unique'];
unset($attributes['unique']);
$providerClass = $linkdata['providerClass'];
$authproviderId = $linkdata['provider'];
$identityPropertyName = $linkdata['identityPropertyName'];

SERIA_Authproviders::loadProviders($providerClass);
$providerObj = SERIA_Authproviders::getProvider($authproviderId);

if (isset($_GET['returned'])) {
	if (isset($_GET['allcompleted'])) {
		if (isset($_REQUEST['goahead']))
			$state->terminate('continue');
		$component->parseTemplate('linkto', array(
			'linked' => true,
			'providerName' => $providerObj->getName()
		));
		return;
	}
	if (!($user = SERIA_Base::user()))
		$state->terminate('abort');

	$email = '';
	if ($attributes['email'])
		$email = $attributes['email'];
	$refparams = array(
		'search' => array(
			'key' => $identityPropertyName.$authproviderId,
			'value' => $unique
		),
		'search2' => array(
			'key' => sha1($identityPropertyName.$authproviderId.'_'.$unique),
			'value' => $unique
		),
		'safeEmail'=> isset($params['safeEmail']) ? $params['safeEmail'] : false,
		'params' => $params,
		'attributes' => $attributes
	);
	$ref = new SERIA_UserAuthenticationProviders($user);
	$ref->setProvider($providerClass, $unique, $email, $refparams);

	$propertylist = SERIA_PropertyList::createObject($user);

	/*
	 * Compat!
	 */
	$propertylist->set($identityPropertyName.$authproviderId, $unique);

	$propertylist->set(sha1($identityPropertyName.$authproviderId.'_'.$unique), $unique);
	if (!($list = $propertylist->get($identityPropertyName.$authproviderId.'_list')))
		$list = array();
	if (!in_array($unique, $list))
		$list[] = $unique;
	$propertylist->set($identityPropertyName.$authproviderId.'_list', $list);
	$propertylist->save();
	SERIA_Base::redirectTo($backtomeUrl.'&allcompleted=yes');
	die();
}


$providers = SERIA_Authproviders::getAllProviderUrls($backtomeUrl, false, ((isset($_GET['guest']) && $_GET['guest']) ? SERIA_IAuthprovider::LOGIN_GUEST : SERIA_IAuthprovider::LOGIN_SYSTEM));
if (isset($providers[$authproviderId]) && count($providers) > 1)
	unset($providers[$authproviderId]);
foreach ($providers as $providerId => &$provider) {
	$action = new SERIA_ActionUrl('linktoProvider', $providerId);
	if ($action->invoked()) {
		$state = new SERIA_AuthenticationState();
		$state->assert();
		$state->push('continue', $linkedUrl);
		SERIA_Base::redirectTo($provider['url']);
		die();
	}
	$provider['url'] = $action->__toString();
}
unset($provider);

$component->parseTemplate('linkto', array(
	'linked' => false,
	'secondaryLogin' => $providers,
	'providerName' => $providerObj->getName()
));

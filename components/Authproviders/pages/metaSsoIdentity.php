<?php

$roamAuthUrl = $_POST['roamauthurl'];
$userChange = $_POST['userChange'];

if (empty($roamAuthUrl) || empty($userChange)) {
	if (isset($_SESSION['SERIA_Authproviders_xmlsso_XMLUrl']) && SERIA_Base::user()) {
		$component = SERIA_Components::getComponent('seria_authproviders');
		$component->loggedInByProvider($provider);
		SERIA_Base::user(NULL);
		die(SERIA_Lib::toJSON(array('reload' => true)));
	}
	die(SERIA_Lib::toJSON(array('reload' => false, 'error' => 'NotExt')));
}

/*
 * Login:
 */
if (isset($_SESSION['SERIA_Authproviders_xmlsso_UserChange']) && $_SESSION['SERIA_Authproviders_xmlsso_UserChange'] == $userChange && SERIA_Base::user()) {
	/* Ok */
	die(SERIA_Lib::toJSON(array('reload' => false)));
}

if (!class_exists('RoamAuthprovider')) {
	die(SERIA_Lib::toJSON(array('reload' => false, 'error' => 'Depends on RoamAuthprovider')));
}

$provider = null;
$data = RoamAuthprovider::getRoamAuthUrlData($roamAuthUrl);
if ($data) {
	$provider = RoamAuthprovider::findExternalAuthproviderByRoamAuthData($data);
	if ($provider)
		$user = RoamAuthprovider::findUserByRoamAuthData($data);
	else
		$user = null;
} else
	$user = null;
if (!$user) {
	die(SERIA_Lib::toJSON(array('reload' => false, 'error' => 'GenError')));
}

if (SERIA_Base::user() && SERIA_Base::user()->get('id') == $user->get('id')) {
	$_SESSION['SERIA_Authproviders_xmlsso_XMLUrl'] = $roamAuthUrl;
	$_SESSION['SERIA_Authproviders_xmlsso_UserChange'] = $userChange;
	die(SERIA_Lib::toJSON(array('reload' => false, 'error' => 'DupLogin')));
}

SERIA_Base::user($user);
$component = SERIA_Components::getComponent('seria_authproviders');
$component->loggedInByProvider($provider);
$_SESSION['SERIA_Authproviders_xmlsso_XMLUrl'] = $roamAuthUrl;
$_SESSION['SERIA_Authproviders_xmlsso_UserChange'] = $userChange;

die(SERIA_Lib::toJSON(array('reload' => true)));

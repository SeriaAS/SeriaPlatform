<?php

require_once(dirname(__FILE__).'/../../../main.php');

$component = SERIA_Components::getComponent('seria_authproviders');
if (!$component)
	throw new SERIA_Exception('Authproivders component is not enabled!');

$state = new SERIA_AuthenticationState();

if (!isset($_GET['id']))
	throw new SERIA_Exception('No id supplied for tracking.');
if (!$state->exists($_GET['id']))
	$state->terminate('abort');

$linkdata = $state->get($_GET['id']);

$attributes = $linkdata['attributes'];
$unique = $attributes['unique'];
unset($attributes['unique']);
$providerClass = $linkdata['providerClass'];
$authproviderId = $linkdata['provider'];
$params = $linkdata['params'];
$identityPropertyName = $linkdata['identityPropertyName'];

$user = new SERIA_User();
$init = array(
	'username' => sha1(mt_rand().mt_rand().mt_rand().mt_rand()),
	'is_administrator' => 0,
	'enabled' => 1,
	'password_change_required' => 0,
	'password' => 'local_blocked_random_'.sha1(mt_rand().mt_rand().mt_rand().mt_rand().mt_rand()),
	'guestAccount' => 1 /* Users are created as guest-accounts when created from an external source */
);
foreach ($init as $name => $value)
	$user->set($name, $value);
$userFields = array(
	"firstName", 
	"lastName",
	"displayName",
	"username",
	"password",
	"email"
);
foreach ($attributes as $name => $value) {
	if (in_array($name, $userFields))
		$user->set($name, $value);
}
/*
 * Force safe-email to false if not avail. This has two effects
 * 1. Shows the email-field to the user
 * 2. Distrusts the email-adress supplied by the user.
 */
if (!isset($attributes['email']) || !$attributes['email'])
	$params['safeEmail'] = FALSE;

class AuthprovidersCreateUserForm extends SERIA_Form
{
	public static $additionalFields = array();

	public static function getBasicFormSpec()
	{
		return array(
			'first_name' => array(
				'fieldtype' => 'text',
				'caption' => _t('First name:'),
				'weight' => 0
			),
			'last_name' => array(
				'fieldtype' => 'text',
				'caption' => _t('Last name:'),
				'weight' => 0
			),
			'display_name' => array(
				'fieldtype' => 'text',
				'caption' => _t('Display name:'),
				'weight' => 0
			)
		);
	}
	public static function getFormSpec()
	{
		$spec = self::getBasicFormSpec();
		if (self::$additionalFields) {
			foreach (self::$additionalFields as $name => $values)
				$spec[$name] = $values;
		}
		return $spec;
	}
	public static function caption()
	{
		return _t('Create a user account');
	}
	public function _handle($data)
	{
		$fields = array_keys(AuthprovidersCreateUserForm::getFormSpec());
		foreach ($fields as $key) {
			if (isset($_POST[$key]))
				$this->object->set($key, $_POST[$key]);
		}
		$this->object->save();
	}
	public function lateErrors($errors)
	{
		$aliases = array(
			'first_name' => 'firstName',
			'last_name' => 'lastName',
			'display_name' => 'displayName'
		);
		foreach ($errors as $name => $value) {
			$alias = array_search($name, $aliases);
			if ($alias)
				$name = $alias;
			$this->errors[$name] = $value;
		}
	}
}

if (!isset($params['safeEmail']) || !$params['safeEmail']) {
	AuthprovidersCreateUserForm::$additionalFields['email'] = array(
		'fieldtype' => 'text',
		'caption' => _t('Email:'),
		'weight' => 0
	);
}

$form = new AuthprovidersCreateUserForm($user);

$state = new SERIA_AuthenticationState();
if (sizeof($_POST)) {
	SERIA_Base::debug('Got POST-data');
	try {
		SERIA_Base::debug('Trying to consume the data (user-form)');
		if (SERIA_Base::elevateUser(array($form, 'receive'), $_POST)) {
			SERIA_Base::debug('User object should have been stored.');
			if (!$user->get('id'))
				throw new SERIA_Exception('The user object has not been updated.');
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

			SERIA_Base::user($user);
			SERIA_Authproviders::loadProviders($providerClass);
			$providerObj = SERIA_Authproviders::getProvider($authproviderId);
			SERIA_Components::getComponent('seria_authproviders')->loggedInByProvider($providerObj);
			$loginManager = new SERIA_GenericAuthproviderLogin();
			$loginManager->loggedIn($providerClass, $authproviderId, $params, $attributes);
			$state->terminate('continue');
			die();
		}
	} catch (SERIA_ValidationException $e) {
		SERIA_Base::debug('There were late validation errors.');
		$errors = $e->getValidationErrors();
		foreach ($errors as $name => $error)
			SERIA_Base::debug('Validation error: '.$name.': '.$error);
		$form->lateErrors($errors);
	}
}

if (isset($_GET['linked']) && $_GET['linked']) {
	$state = new SERIA_AuthenticationState();
	if ($_GET['linked'] == 'done') {
		if (isset($_REQUEST['goahead']))
			$state->terminate('continue');
		$component->parseTemplate('linkaccount', array(
			'continueUrl' => $state->getLast('continue'),
			'linked' => true
		));
		return;
	}
	$user = SERIA_Base::user();
	if (!$user)
		$state->terminate('continue');
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

	$loginManager = new SERIA_GenericAuthproviderLogin();
	$loginManager->loggedIn($providerClass, $authproviderId, $params, $attributes);

	SERIA_Base::redirectTo(SERIA_Url::current()->setParam('linked', 'done')->__toString());
	die();
}

$linktoParams = array(
	'id' => $_GET['id'],
);
if (isset($_GET['guest']) && $_GET['guest'])
	$linktoParams['guest'] = 'yes';

$linkedParams = array(
	'id' => $_GET['id'],
	'linked' => 'yes'
);
$state = new SERIA_AuthenticationState();
$linkedUrl = $state->stampUrl(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/linkaccount.php?'.http_build_query($linkedParams));
$providers = SERIA_Authproviders::getAllProviderUrls($linkedUrl, false, ((isset($_GET['guest']) && $_GET['guest']) ? SERIA_IAuthprovider::LOGIN_GUEST : SERIA_IAuthprovider::LOGIN_SYSTEM));
if (isset($providers[$authproviderId]) && count($providers) > 1)
	unset($providers[$authproviderId]);
foreach ($providers as $providerId => &$provider) {
	$action = new SERIA_ActionUrl('linktoProvider', $providerId);
	SERIA_Base::debug('Checking provider: '.$providerId);
	if ($action->invoked()) {
		$state->assert();
		$state->pushTerminateHook('continue', $linkedUrl);
		SERIA_Base::debug('Action invoked, redirecting to: '.$provider['url']);
		SERIA_Base::redirectTo($provider['url']);
		die();
	}
	$provider['url'] = $action->__toString();
}
unset($provider);
$component->parseTemplate('linkaccount', array(
	'form' => $form,
	'linktoUrl' => SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/linkto.php?'.http_build_query($linktoParams),
	'secondaryLogin' => $providers,
	'linked' => false
));

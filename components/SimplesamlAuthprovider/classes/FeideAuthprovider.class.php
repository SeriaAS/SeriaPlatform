<?php

class FeideAuthprovider extends SimplesamlAuthprovider
{
	protected $caption;
	protected $name;
	protected $idp;

	public function __construct($caption, $name, $idp)
	{
		$this->caption = $caption;
		$this->name = $name;
		$this->idp = $idp;
	}

	public static function loadProviders()
	{
		$retv =& parent::loadProviders();
		SERIA_Authproviders::addProvider(new FeideAuthprovider(_t('Feide public authentication'), 'feide_openidp', 'https://openidp.feide.no'));
		SERIA_Authproviders::addProvider(new FeideAuthprovider(_t('Feide test authentication'), 'feide_test', 'https://idp-test.feide.no'));
		SERIA_Authproviders::addProvider(new FeideAuthprovider(_t('Feide authentication'), 'feide', 'https://idp.feide.no'));
		return array_merge($retv, array(
			'configure' => array(
				'caption' => _t('Configure authprovider'),
				'call' => array('FeideAuthprovider', 'configureProvider'),
				'version' => 2
			)
		));
	}

	protected static function getUniqueSources()
	{
		return array(
			'uid' => array('uid', 0),
			'eduPersonPrincipalName' => array('eduPersonPrincipalName', 0)
		);
	}
	public function setUniqueSource($uniqueSource)
	{
		SERIA_Base::setParam($this->getProviderId().'.uniqueSource', $uniqueSource);
	}
	public function getUniqueSource()
	{
		$name = SERIA_Base::getParam($this->getProviderId().'.uniqueSource');
		if ($name)
			return $name;
		else
			return null;
	}
	public function getUniqueSourceKey()
	{
		$name = $this->getUniqueSource();
		if ($name === null)
			return null;
		$sources = $this->getUniqueSources();
		if (isset($sources[$name]))
			return $sources[$name];
		else
			return null;
	}
	public function isAvailable()
	{
		return ($this->getUniqueSourceKey() !== null);
	}

	public function getParameters()
	{
		if (SERIA_AuthenticationState::available()) {
			$state = new SERIA_AuthenticationState();
			$stateId = $state->get('id');
		} else
			$stateId = 'null';
		return array(
			'authsources' => array(
				'feide' => array(
					'saml:SP',
					'idp' => $this->idp,
					/*
					 * We need to override the metadata entityID with an URL where the state path-parameter is null.
					 * The state path must be fixed in the entityID, if it changes when an actual login is
					 * invoked it will be rejected by the SAML server.
					 */
					'entityID' => SERIA_HTTP_ROOT.'/seria/components/SimplesamlAuthprovider/simplesamlphp.php/FeideAuthprovider/'.$this->getProviderId().'/null/module.php/saml/sp/metadata.php/feide'
				)
			),
			'config' => array(
				'baseurlpath' => 'seria/components/SimplesamlAuthprovider/simplesamlphp.php/FeideAuthprovider/'.$this->getProviderId().'/'.$stateId.'/'
			),
			'authsource' => 'feide',
			'attributes' => array(
				'defaults' => array(
					'firstName' => '',
					'lastName' => '',
					'displayName' => '',
					'email' => '',
					'feideRoles' => array(),
					'jpegPhoto' => array(),
					'postalCode' => '',
					'fylke' => '',
					'eduPersonOrgDN:o' => '',
					'eduPersonOrgDN:eduOrgLegalName' => '',
					'eduPersonOrgUnitDN:ou' => '',
					'eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier' => ''
				),
				'load' => array(
					'unique' => $this->getUniqueSourceKey(),
					'email' => array('mail', 0),
					'firstName' => array('givenName', 0),
					'lastName' => array('sn', 0),
					'displayName' => array('cn', 0),
					'feideRoles' => 'eduPersonAffiliation',
					'jpegPhoto' => 'jpegPhoto',
					'postalCode' => 'postalCode',
					'fylke' => 'eduPersoOrgDN:eduOrgLegalName',
					'eduPersonOrgDN:o' => 'eduPersonOrgDN:o',
					'eduPersonOrgDN:eduOrgLegalName' => 'eduPersonOrgDN:eduOrgLegalName',
					'eduPersonOrgUnitDN:ou' => 'eduPersonOrgUnitDN:ou',
					'eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier' => 'eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier'
				),
				'combinations' => array(
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique',
					'email',
					'firstName',
					'lastName',
					'displayName',
					'feideRoles',
					'jpegPhoto',
					'postalCode',
					'fylke',
					'eduPersonOrgDN:o',
					'eduPersonOrgDN:eduOrgLegalName',
					'eduPersonOrgUnitDN:ou',
					'eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier'
				)
			),
			'metadata' => array(
				'saml20-idp-remote' => array(
				)
			),
			'safeEmail' => true
		);
	}
	public function filterAttributes($attributes)
	{
		$attributes = parent::filterAttributes($attributes);
		if (!$attributes['unique'])
			throw new SERIA_Exception(_t('Unique field is blank. Check feide provider settings in the control-panel.'));
		if ($this->getUniqueSource() == 'uid')
			$attributes['unique'] .= '@feide';
		return $attributes;
	}

	/**/
	public function getProviderId()
	{
		return $this->name;
	}
	public function getName()
	{
		return $this->caption;
	}

	protected function getFlatConfig($map)
	{
		$flat = array();
		foreach ($map as $name => $values) {
			if (!is_array($values))
				$flat[$name] = $values;
			else {
				$subconfig = $this->getFlatConfig($values);
				foreach ($subconfig as $nam => $val)
					$flat[$name.'.'.$nam] = $val;
			}
		}
		return $flat;
	}
	public function configure($params)
	{
		$options = array(
			'config' => array(
				'auth' => array(
					'adminpassword' => $this->getConfigParameter('auth.adminpassword')
				),
				'technicalcontact_name' => $this->getConfigParameter('technicalcontact_name'),
				'technicalcontact_email' => $this->getConfigParameter('technicalcontact_email')
			)
		);
		$modified = false;
		$flat = $this->getFlatConfig($options['config']);
		foreach ($flat as $name => &$value) {
			$pname = str_replace('.', '_', $name);
			SERIA_Base::debug('Checking post: '.$name.'/'.$pname);
			if (isset($_POST[$pname])) {
				SERIA_Base::debug('Got post: '.$name);
				$value = $_POST[$pname];
				$modified = true;
			}
		}
		unset($value);
		$errors = array();
		if (isset($_POST['uniqueSource'])) {
			$modified = true;
			if ($_POST['uniqueSource'] == '')
				$this->setUniqueSource(null);
			else {
				$sources = array_keys(self::getUniqueSources());
				if (in_array($_POST['uniqueSource'], $sources))
					$this->setUniqueSource($_POST['uniqueSource']);
				else
					$errors['uniqueSource'] = _t('Not a valid unique source.');
			}
		}
		if ($modified) {
			SERIA_Base::debug('Got config posted update');
			if (isset($_POST['auth_adminpassword'])) {
				if (!isset($_POST['password_repeat']) || $_POST['auth_adminpassword'] != $_POST['password_repeat'])
					$errors['auth.adminpassword'] = _t('Passwords do not match.');
			}
			if (!$errors) {
				foreach ($flat as $name => $value)
					$this->setConfigParameter($name, $value);
				if (!$errors) {
					SERIA_Base::redirectTo($params['redirect']);
					die();
				}
			}
		}
		$config = $this->getParameters();
		$metaTemplate = new SERIA_MetaTemplate();
		$metaTemplate->addVariable('uniqueSource', $this->getUniqueSource());
		$metaTemplate->addVariable('uniqueSources', array_keys($this->getUniqueSources()));
		$metaTemplate->addVariable('config', $options['config']);
		$metaTemplate->addVariable('errors', $errors);
		$metaTemplate->addVariable('simplesaml_base_url', SERIA_HTTP_ROOT.'/'.$config['config']['baseurlpath']);
		$metaTemplate->addVariable('submitCaption', $params['submitCaption']);
		$metaTemplate->addVariable('redirect', $params['redirect']);
		$metaTemplate->addVariable('cancel', $params['cancel']);
		$component = SERIA_Components::getComponent('simplesamlcomponent');
		if (!$component)
			throw new SERIA_Exception('SimpleSAML component is not loaded.');
		echo $metaTemplate->parse($component->getTemplateFilename('feide_config'));
	}
	public static function configureProvider($provider, $params=array())
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		$provider->configure($params);
	}
}
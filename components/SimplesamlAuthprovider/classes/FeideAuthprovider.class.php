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
		$retv = parent::loadProviders();
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
					'eduPersonOrgDN:o' => array('eduPersonOrgDN:o', 0),
					'eduPersonOrgDN:eduOrgLegalName' => array('eduPersonOrgDN:eduOrgLegalName', 0),
					'eduPersonOrgUnitDN:ou' => array('eduPersonOrgUnitDN:ou', 0),
					'eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier' => array('eduPersonOrgUnitDN:norEduOrgUnitUniqueIdentifier', 0)
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
					'https://idp-test.feide.no' => array(
						'name' => array(
							'en' => 'Feide Test environment',
							'no' => 'Feide testmiljø',
						),
						'description'                  => 'Feide test environment (idp-test.feide.no). Authenticate with your identity from a school or university in Norway.',

						'SingleSignOnService'          => 'https://idp-test.feide.no/simplesaml/saml2/idp/SSOService.php',
						'SingleLogoutService'          => 'https://idp-test.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',

						'certFingerprint'              => 'fa982efdb69f26e8073c8f815a82a0c5885960a2',
						'hint.cidr'                    => '158.38.0.0/16',

						'keys' => array(
							0 => array(
								'encryption' => true,
								'signing' => true,
								'type' => 'X509Certificate',
								'X509Certificate' => 'MIIDkDCCAngCCQCLL8NWusxhbzANBgkqhkiG9w0BAQUFADCBiTELMAkGA1UEBhMCTk8xEjAQBgNVBAcTCVRyb25kaGVpbTETMBEGA1UEChMKVW5pbmV0dCBBUzEOMAwGA1UECxMFRkVJREUxGjAYBgNVBAMTEWlkcC10ZXN0LmZlaWRlLm5vMSUwIwYJKoZIhvcNAQkBFhZtb3JpYS1kcmlmdEB1bmluZXR0Lm5vMB4XDTE0MDQxMTEwMjkxMloXDTM0MDQxMTEwMjkxMlowgYkxCzAJBgNVBAYTAk5PMRIwEAYDVQQHEwlUcm9uZGhlaW0xEzARBgNVBAoTClVuaW5ldHQgQVMxDjAMBgNVBAsTBUZFSURFMRowGAYDVQQDExFpZHAtdGVzdC5mZWlkZS5ubzElMCMGCSqGSIb3DQEJARYWbW9yaWEtZHJpZnRAdW5pbmV0dC5ubzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMFL8ZFo/E42mPw4r27+HVn54E0ltmb88q1MsfGyiRlaEvVdnIo81tTUonjG4EP58wz/bQ49dSPOOoNVZ4NkhU2G4x81XErqEGFw31NBQerXp0Gcs8A93aIVGluKfCW5kDZtV+WnE0P2trwyPS5vKTVvs4MvIoDrGoWRT0y2ok9xzv5nxbICrSzsnBTC5rMrKFgKeaoappnZHt3isttfVZSP3aidmHEbl2Hw7xci554woRjx7n2kOxgOUa8A49HqV7Sr9lZDyffusOZ8QRBjongfBOgNGcrkyxXjI9xs1dD9ZKrwlORNx54kP9/rpHe+drXCV9QvR6zNrxHnxbEuWiUCAwEAATANBgkqhkiG9w0BAQUFAAOCAQEAFOsehLFueCFZqVOua+Uc81amKA+ZWHkvZWOavCsfzozZSLH4gGtwzMA1/6bh+FhURB+QdIiglH9EUDWWItaC8SCvhDo87v3bzg+LT8AE9go8mI15AraZAF6XwJC6r23UOsHcn68GLuDF+om8slizTTec6aQtA9qkhMLSwMarvk1S3m8KZEVOcghB9cpgyt3otz0JbiOmfIDoetbNeEa/x6sLXi9il/H5mtEmJUhdB6YjKaIPtMiILr1ow7DaHmJGgt+qyr09rZXOCz3okDko6WRCGCw5EdgDuYwiHz4xtixLhBvY5TKqIwgKAhNYKRxO6C4ugrS/ToCgC0j1epeK6A=='
							)
						)
					),
					'https://idp.feide.no' => array(
						'name' => 'Feide',
							'description' => array(
							'en' => 'Authenticate with your identity from a school or university in Norway.',
							'no' => 'Logg inn med din identitet fra skolen eller universitetet du er tilknyttet (i Norge).',
						),
						'SingleSignOnService'          => 'https://idp.feide.no/simplesaml/saml2/idp/SSOService.php',
						'SingleLogoutService'          => 'https://idp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
						'hint.cidr'                    => '158.38.0.0/16',

						'keys' => array (
							0 => array (
								'encryption' => true,
								'signing' => true,
								'type' => 'X509Certificate',
								'X509Certificate' => 'MIIDhjCCAm4CCQCZwrMQOJ3URzANBgkqhkiG9w0BAQUFADCBhDELMAkGA1UEBhMC
                            						  Tk8xEjAQBgNVBAcTCVRyb25kaGVpbTETMBEGA1UEChMKVW5pbmV0dCBBUzEOMAwG
                            						  A1UECxMFRkVJREUxFTATBgNVBAMTDGlkcC5mZWlkZS5ubzElMCMGCSqGSIb3DQEJ
                            						  ARYWbW9yaWEtZHJpZnRAdW5pbmV0dC5ubzAeFw0xNDA0MTEwOTM1MTBaFw0zNDA0
                            						  MTEwOTM1MTBaMIGEMQswCQYDVQQGEwJOTzESMBAGA1UEBxMJVHJvbmRoZWltMRMw
                            						  EQYDVQQKEwpVbmluZXR0IEFTMQ4wDAYDVQQLEwVGRUlERTEVMBMGA1UEAxMMaWRw
                            						  LmZlaWRlLm5vMSUwIwYJKoZIhvcNAQkBFhZtb3JpYS1kcmlmdEB1bmluZXR0Lm5v
                            						  MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr3UtSny6D+DRQzdjWOdd
                            						  +eQZxa9aKrx/v70Uo+yvnzgenLLS+MsUxbiSLkAPIbkWOO2kLdG9XSZ9sp9S5aGY
                            						  MnsarxeGEXV1AS6olrpo5QJOZoQStFB0dYEXzBSJifTIsEmyXByd8mE64dkMcdzG
                            						  90eBzfcFNwU6vKjln0vmoDocJrKZvUoF7d1egD+aUa9o3BneMDylcp8mkCe6XcnP
                            						  lJ8QqxQ/RBmaly/Hl/zTZei8+pEu7ICRiorD2iHEDM/EhsclOrMKiRFBuZN8yB4s
                            						  gknhdmAiWRyB/D4CEj74MQDQPp7Mr1B0Vxn7Y7ZeStt19HxEjzxyJGsdC9BMrn+t
                            						  zwIDAQABMA0GCSqGSIb3DQEBBQUAA4IBAQBwZmzNzTgbYAuQGikkRbKInog5OCMo
                            						  3GhZO82+IrtasJC6rNPrz/+8KHfIOUB83wnfEMnKKygW7ELeSnvlbKUyve6DbNXr
                            						  HjMJYzjqLG3cdgIKZaFyTfWaQiY8G82qP38Lc7rtgLoh/F7lpqCdunzPfSQBraGH
                            						  2IAHyP6x3tjlsGGTj/LN8sT20iHRk8IXsBsMGv5DcZ4n+zB2E5hyfxH87sNYu6ga
                            						  Irpcxcv5N0AK++fvpnrhlEmT0rW7b8wgBB4BmaPfCCb4DbDgHvIBPmG8QF7SNjUG
                            						  uVPUFJRPTkvhighbeuRtoNpq0W1EVXKq0ZeBO8jJ6Si9LAdFvqwy70D0',
							),
							1 => array (
								'encryption' => true,
								'signing' => true,
								'type' => 'X509Certificate',
								'X509Certificate' => 'MIIDhjCCAm4CCQCMHNhxUI2H1TANBgkqhkiG9w0BAQUFADCBhDELMAkGA1UEBhMC
                            						 Tk8xEjAQBgNVBAcTCVRyb25kaGVpbTETMBEGA1UEChMKVW5pbmV0dCBBUzEOMAwG
                            						 A1UECxMFRkVJREUxFTATBgNVBAMTDGlkcC5mZWlkZS5ubzElMCMGCSqGSIb3DQEJ
                            						 ARYWbW9yaWEtZHJpZnRAdW5pbmV0dC5ubzAeFw0wODA5MDUxMTU0MzNaFw0xODA3
                            						 MTUxMTU0MzNaMIGEMQswCQYDVQQGEwJOTzESMBAGA1UEBxMJVHJvbmRoZWltMRMw
                            						 EQYDVQQKEwpVbmluZXR0IEFTMQ4wDAYDVQQLEwVGRUlERTEVMBMGA1UEAxMMaWRw
                            						 LmZlaWRlLm5vMSUwIwYJKoZIhvcNAQkBFhZtb3JpYS1kcmlmdEB1bmluZXR0Lm5v
                            						 MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4fTsmIsKVGtniXddnerS
                            						 eiLeAZAlAOL8v+ebzVzYcpTJzMHrplD+lF2tXxRgs7IGEw3t2zRCtxnGbiGkXPW+
                            						 oCs4T989z+Sq8nh7Lff/XlyK+jQ7BtfC8RUYQ+eNEQy0Fif+81JyPbiwZovbiL4W
                            						 rK1GOG81/2CF7rvwyXJkDD1YXJ5W18/c06YLfYJjuzZgEoCVRq6ecgQyPKg1xwIp
                            						 W2GpkKOBXA7oKWtev+xcmSiLZwZE96mSHjty0L+wW6NUuf2/8VSCc4IED0EbzqFU
                            						 oeHuGXqPak+tu9+VpP6vmmyp4gSCxsmWtoKm7UC8P1QeCyZxwQaoGlIp78wsE5ao
                            						 5wIDAQABMA0GCSqGSIb3DQEBBQUAA4IBAQACUWuuirUSwDWksdkwKuqsNttnNmiv
                            						 wUMLtKDjHbMcwVK/b4qWjrAjfmJXxamUSYlnGjeoiqSQQuc3/qHCPAZUnN8VqXcZ
                            						 UCBXWjMO7Y/AnnsFKHpkYm51jWckjudeyfUr4UnH7te0OuUrGRIVrRRg3DqLdgrc
                            						 bkJ98JyT97hnaDMke4qNVwrQFF+yvxYs1aYkILySBB/KPGSTh5sxJovcyWd7GY4a
                            						 d5nH5oEjXF1yZzndmUuHGlTTzk6SGzmUJgqKyba+KJ/jauy6qNC1gPqfnbntWKDk
                            						 E9a9ow8tlsi3jHI9AZu9U6LnOvTJ8MjhyXOEByCaDnTpK8JiZr6JvCaV',
							)
						)
					)
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
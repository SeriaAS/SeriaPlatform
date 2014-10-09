<?php

class OpenidGoogleAuthprovider extends SimplesamlAuthprovider
{
	public static function loadProviders()
	{
		$retv = parent::loadProviders();
		SERIA_Authproviders::addProvider(new OpenidGoogleAuthprovider());
		return $retv;
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
				'openid-google' => array(
					'openid:OpenIDConsumer',
					'attributes.ax_required' => array(
						'http://axschema.org/contact/email',
						'http://axschema.org/namePerson/first',
						'http://axschema.org/namePerson/last',
					),
					'target' => 'https://www.google.com/accounts/o8/id'
				),
			),
			'config' => array(
				'baseurlpath' => 'seria/components/SimplesamlAuthprovider/simplesamlphp.php/OpenidGoogleAuthprovider/'.$this->getProviderId().'/'.$stateId.'/'
			),
			'authsource' => 'openid-google',
			'attributes' => array(
				'defaults' => array(
					'email' => '',
					'firstName' => '',
					'lastName' => '',
					/* For combinations */
					'_space' => ' '
				),
				'load' => array(
					'unique' => array('openid', 0),
					'email' => array('http://axschema.org/contact/email', 0),
					'firstName' => array('http://axschema.org/namePerson/first', 0),
					'lastName' => array('http://axschema.org/namePerson/last', 0)
				),
				'combinations' => array(
					'displayName' => array('firstName', '_space', 'lastName')
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique',
					'email',
					'firstName',
					'lastName',
					'displayName'
				)
			),
			'safeEmail' => true
		);
	}

	/**/
	public function getProviderId()
	{
		return 'openid_google_provider';
	}
	public function getName()
	{
		return _t('Google OpenID authentication');
	}
}
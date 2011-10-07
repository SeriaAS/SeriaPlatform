<?php

class OpenidGoogleAuthprovider extends SimplesamlAuthprovider
{
	public static function loadProviders()
	{
		$retv =& parent::loadProviders();
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
					'ax.required' => array(
						array('http://axschema.org/contact/email', 'email'),
						array('http://axschema.org/namePerson/first', 'firstname'),
						array('http://axschema.org/namePerson/last', 'lastname')
					),
					'discovery' => 'https://www.google.com/accounts/o8/id'
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
					'email' => array('openid.ax.email', 0),
					'firstName' => array('openid.ax.firstname', 0),
					'lastName' => array('openid.ax.lastname', 0)
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
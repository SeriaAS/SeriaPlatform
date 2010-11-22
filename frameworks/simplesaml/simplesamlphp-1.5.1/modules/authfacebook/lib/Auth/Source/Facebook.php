<?php

/**
 * Authenticate using Facebook Platform.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authfacebook_Auth_Source_Facebook extends SimpleSAML_Auth_Source {


	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'facebook:init';

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'facebook:AuthId';


	private $api_key;
	private $secret;
	private $ext_params;



	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		if (!array_key_exists('api_key', $config))
			throw new Exception('Facebook authentication source is not properly configured: missing [api_key]');
		
		$this->api_key = $config['api_key'];
		
		if (!array_key_exists('secret', $config))
			throw new Exception('Facebook authentication source is not properly configured: missing [secret]');

		$this->secret = $config['secret'];

		if (isset($config['ext_params']) && $config['ext_params'])
			$this->ext_params = $config['ext_params'];
		else
			$this->ext_params = array();
			

		require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extlibinc/facebook.php');

	}


	/**
	 * Log-in using Facebook platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		
		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);
		
		SimpleSAML_Logger::debug('facebook auth state id = ' . $stateID);

		$redirect = sspmod_simpleurl_UrlGenerator::getRedirectUrl($state, $state[SimpleSAML_Auth_State::RESTART]);

		$simplesaml_session = SimpleSAML_Session::getInstance();
		$simplesaml_session->saveSession();

		$facebook = new Facebook($this->api_key, $this->secret);		
		$u = $facebook->require_login($redirect, $this->ext_params);
		# http://developers.facebook.com/documentation.php?v=1.0&method=users.getInfo
		/* Causes an notice / warning...
		if ($facebook->api_client->error_code) {
			throw new Exception('Unable to load profile from facebook');
		}
		*/
		$info = $facebook->api_client->users_getInfo($u, array_merge(array('first_name', 'last_name'), $this->ext_params));
		$fullname = $info[0]['first_name'] .' '. $info[0]['last_name'];

		$attributes = array(
			'sn' => array($info[0]['last_name']),
			'givenName' => array($info[0]['first_name']),
			'cn' => array($info[0]['first_name'] .' '. $info[0]['last_name']),
			'uid' => array($u),
			'eduPersonPrincipalName' => array('facebook:' . $u),
		);
		if (isset($info[0]['email']))
			$attributes['email'] = array($info[0]['email']);
		$state['Attributes'] = $attributes;
	}
	


}

?>
<?php

/**
 * Authentication module which acts as an OpenID Consumer
 *
 * @author Andreas Åkre Solberg, <andreas.solberg@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_openid_Auth_Source_OpenIDConsumer extends SimpleSAML_Auth_Source {

	/**
	 * List of optional attributes.
	 */
	private $optionalAttributes;


	/**
	 * List of required attributes.
	 */
	private $requiredAttributes;

	/**
	 * List of ax optional attributes
	 */
	private $axOptionalAttributes;

	/**
	 * List of ax required attributes
	 */
	private $axRequiredAttributes;

	/**
	 * Url of discovery endpoint
	 */
	private $discoveryEndpoint;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, TRUE));

		$this->optionalAttributes = $cfgParse->getArray('attributes.optional', array());
		$this->requiredAttributes = $cfgParse->getArray('attributes.required', array());

		$this->axOptionalAttributes = $cfgParse->getArray('attributes.ax_optional', array());
		$this->axRequiredAttributes = $cfgParse->getArray('attributes.ax_required', array());

		$this->discoveryEndpoint = $cfgParse->getString('target', false);
	}


	/**
	 * Initiate authentication. Redirecting the user to the consumer endpoint 
	 * with a state Auth ID.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		$state['openid:AuthId'] = $this->authId;
		$id = SimpleSAML_Auth_State::saveState($state, 'openid:state');

		$simplesaml_session = SimpleSAML_Session::getInstance();
		$simplesaml_session->saveSession();

		$url = SimpleSAML_Module::getModuleURL('openid/consumer.php');
		SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
	}


	/**
	 * Retrieve required attributes.
	 *
	 * @return array  Required attributes.
	 */
	public function getRequiredAttributes() {
		return $this->requiredAttributes;
	}


	/**
	 * Retrieve optional attributes.
	 *
	 * @return array  Optional attributes.
	 */
	public function getOptionalAttributes() {
		return $this->optionalAttributes;
	}

	/**
	 * Retrieve required attributes.
	 *
	 * @return array  Required attributes.
	 */
	public function getAxRequiredAttributes() {
		return $this->axRequiredAttributes;
	}

	/**
	 * Retrieve optional attributes.
	 *
	 * @return array  Optional attributes.
	 */
	public function getAxOptionalAttributes() {
		return $this->axOptionalAttributes;
	}

	/**
	 * Retrieve the url of the discovery endpoint. (Google/etc uses this).
	 *
	 * @return string  Either an url or false.
	 */
	public function getDiscoveryEndpoint() {
		return $this->discoveryEndpoint;
	}
}

?>
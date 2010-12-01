<?php

/**
 * Handle linkback() response from Twitter.
 */
# sspmod_oauth_Consumer::dummy();

// $config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
 
$oauthState = $session->getData('oauth', 'oauth');

if (empty($oauthState)) throw new Exception('Could not load oauthstate');
if (empty($oauthState['stateid'])) throw new Exception('Could not load oauthstate:stateid');

$stateId = $oauthState['stateid'];
SimpleSAML_Logger::debug('Returned to twitter auth state id = ' . $stateId);

// echo 'stateid is ' . $stateId;

$state = SimpleSAML_Auth_State::loadState($stateId, sspmod_authtwitter_Auth_Source_Twitter::STAGE_INIT);
$state['requestToken'] = $oauthState['requestToken'];

if (isset($_GET['oauth_verifier'])) {
	$state['requestVerifier'] = serialize($_GET['oauth_verifier']);
	SERIA_Base::debug('Verifier: '.$state['requestVerifier']);
}



/* Find authentication source. */
assert('array_key_exists(sspmod_authtwitter_Auth_Source_Twitter::AUTHID, $state)');
$sourceId = $state[sspmod_authtwitter_Auth_Source_Twitter::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

try {

	$config = SimpleSAML_Configuration::getInstance();

	$source->finalStep($state);



	SimpleSAML_Auth_Source::completeAuth($state);

} catch (SimpleSAML_Error_Exception $e) {
	/*
	 * This is probably a login failure or user cancel.
	 */
	SimpleSAML_Auth_State::throwException($state, $e);
}



<?php

/*
 * Disable strict error reporting, since the OpenID library
 * used is PHP4-compatible, and not PHP5 strict-standards compatible.
 */
SimpleSAML_Utilities::maskErrors(E_STRICT);

require_once('Auth/OpenID/SReg.php');
require_once('Auth/OpenID/Server.php');
require_once('Auth/OpenID/ServerRequest.php');

session_start();

$config = SimpleSAML_Configuration::getInstance();

/* Find the authentication state. */
if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing mandatory parameter: AuthState');
}
$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthState'], 'openid:state');
$authState = $_REQUEST['AuthState'];
$authSource = SimpleSAML_Auth_Source::getById($state['openid:AuthId']);
if ($authSource === NULL) {
	throw new SimpleSAML_Error_BadRequest('Invalid AuthId \'' . $state['openid:AuthId'] . '\' - not found.');
}


function displayError($message) {
	global $authState;

	$config = SimpleSAML_Configuration::getInstance();
	$t = new SimpleSAML_XHTML_Template($config, 'openid:consumer.php', 'openid');
	$t->data['error'] = $message;
	$t->data['AuthState'] = $authState;
	$t->show();
}



function getConsumer() {
	global $state;
	$store = new sspmod_openid_StateStore($state);
	return new Auth_OpenID_Consumer($store);
}

function getReturnTo() {
	return SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 
		array('returned' => '1') 
	);

}

function getTrustRoot() {
	return SimpleSAML_Utilities::selfURLhost();
}

function ax_create_alias_from_uri($uri)
{
	$alias = explode('/', $uri);
	return array_pop($alias);
}

function run_try_auth() {
    global $authSource;

    SimpleSAML_Logger::debug('Trying auth');

    $openid = $_GET['openid_url'];
    $consumer = getConsumer();

    // Begin the OpenID authentication process.
    $auth_request = $consumer->begin($openid);

    // No auth request means we can't begin OpenID.
    if (!$auth_request) {
        displayError("Authentication error; not a valid OpenID.");
    }

    $sreg_request = Auth_OpenID_SRegRequest::build(
			$authSource->getRequiredAttributes(),
			$authSource->getOptionalAttributes());

    if ($sreg_request) {
        $auth_request->addExtension($sreg_request);
    }

    new Auth_OpenID_AX();
    $attrs = array();
    $axopt = $authSource->getAxOptionalAttributes();
    $axreq = $authSource->getAxRequiredAttributes();
    foreach ($axopt as $attr)
        $attrs[] = array(0, $attr, $attr);
    foreach ($axreq as $attr)
        $attrs[] = array(1, $attr, $attr);
    $req_attrs = array();
    foreach ($attrs as $attr) {
        if (isset($req_attrs[$attr[2]])) {
            $info =& $req_attrs[$attr[2]];
            if ($info['type'] != $attr[1] ||
                $info['required'] != $attr[0])
                throw new Exception('Conflicting type/required for ax attribute '.$attr[2]);
            $info['count']++;
            unset($info);
        } else
            $req_attrs[$attr[2]] = array('count' => 1, 'type' => $attr[1], 'required' => $attr[0]);
    }
    if ($req_attrs) {
    	foreach ($req_attrs as $name => &$attr)
    	    $attr = Auth_OpenID_AX_AttrInfo::make($attr['type'], $attr['count'], $attr['required'], ax_create_alias_from_uri($name));
        unset($attr);
        $ax_request = new Auth_OpenID_AX_FetchRequest();
        foreach ($req_attrs as $attr)
            $ax_request->add($attr);
        $auth_request->addExtension($ax_request);
    }
    
    // Redirect the user to the OpenID server for authentication.
    // Store the token for this authentication so we can verify the
    // response.

    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
    // form to send a POST request to the server.
    if ($auth_request->shouldSendRedirect()) {
        $redirect_url = $auth_request->redirectURL(getTrustRoot(), getReturnTo());

        // If the redirect URL can't be built, display an error message.
        if (Auth_OpenID::isFailure($redirect_url)) {
            displayError("Could not redirect to server: " . $redirect_url->message);
        } else {
            header("Location: ".$redirect_url); // Send redirect.
        }
    } else {
        // Generate form markup and render it.
        $form_id = 'openid_message';
        $form_html = $auth_request->formMarkup(getTrustRoot(), getReturnTo(), FALSE, array('id' => $form_id));

        // Display an error if the form markup couldn't be generated; otherwise, render the HTML.
        if (Auth_OpenID::isFailure($form_html)) {
            displayError("Could not redirect to server: " . $form_html->message);
        } else {
            echo '<html><head><title>OpenID transaction in progress</title></head>
            		<body onload=\'document.getElementById("' . $form_id . '").submit()\'>' . 
					$form_html . '</body></html>';
        }
    }
}

function run_finish_auth() {
    global $authSource;

	SimpleSAML_Logger::debug('Finishing auth');

	$error = 'General error. Try again.';

	try {
	
		$consumer = getConsumer();
	
		$return_to = SimpleSAML_Utilities::selfURL();

		// Complete the authentication process using the server's
		// response.
		$response = $consumer->complete($return_to);
	
		// Check the response status.
		if ($response->status == Auth_OpenID_CANCEL) {
			SimpleSAML_Logger::debug('OpenID authentication cancelled');
			// This means the authentication was cancelled.
			throw new SimpleSAML_Error_Exception('Verification cancelled.');
		} else if ($response->status == Auth_OpenID_FAILURE) {
			SimpleSAML_Logger::debug('OpenID authentication failed');
			// Authentication failed; display the error message.
			throw new SimpleSAML_Error_Exception("OpenID authentication failed: " . $response->message);
		} else if ($response->status == Auth_OpenID_SUCCESS) {
			SimpleSAML_Logger::debug('OpenID authentication succeeded');
			// This means the authentication succeeded; extract the
			// identity URL and Simple Registration data (if it was
			// returned).
			$openid = $response->identity_url;

			SimpleSAML_Logger::debug('OpenID identity url: '.$openid);
			$attributes = array('openid' => array($openid));
	
			if ($response->endpoint->canonicalID) {
				SimpleSAML_Logger::debug('OpenID: openid.canonicalID = '.(is_string($response->endpoint->canonicalID) ? $response->endpoint->canonicalID : serialize($response->endpoint->canonicalID)));
				$attributes['openid.canonicalID'] = array($response->endpoint->canonicalID);
			}
	
			$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
			$sregresponse = $sreg_resp->contents();
			
			if (is_array($sregresponse) && count($sregresponse) > 0) {
				$attributes['openid.sregkeys'] = array_keys($sregresponse);
				foreach ($sregresponse AS $sregkey => $sregvalue) {
					$attributes['openid.sreg.' . $sregkey] = array($sregvalue);
				}
			}
    		$axopt = $authSource->getAxOptionalAttributes();
    		$axreq = $authSource->getAxRequiredAttributes();
    		if ($axopt || $axreq) {
				new Auth_OpenID_AX();
				$obj = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response);
				if ($obj) {
					SimpleSAML_Logger::debug('AX Response: '.serialize($obj));
    				$axattr = array();
    				foreach ($axopt as $attr)
    					$axattr[ax_create_alias_from_uri($attr)] = $attr;
    				foreach ($axreq as $attr)
    					$axattr[ax_create_alias_from_uri($attr)] = $attr;
    				foreach ($axattr as $spec) {
    					$attributes[$spec] = $obj->get($spec);
    					if (!$attributes[$spec])
    						unset($attributes[$spec]);
    				}
				}
			}

			global $state;
			$state['Attributes'] = $attributes;
			SimpleSAML_Logger::debug('Completing OpenID');
			SimpleSAML_Auth_Source::completeAuth($state);
			SimpleSAML_Logger::debug('Completed OpenID');

		}

	} catch (Exception $e) {
		if ($authSource->getDiscoveryEndpoint()) {
			/*
			 * Need to return the error back to requestor..
			 */
			global $state;

			SimpleSAML_Auth_State::throwException($state, $e);
		}
		$error = $e->getMessage();
	}

	$config = SimpleSAML_Configuration::getInstance();
	$t = new SimpleSAML_XHTML_Template($config, 'openid:consumer.php', 'openid');
	$t->data['error'] = $error;
	global $authState;
	$t->data['AuthState'] = $authState;
	$t->show();

}

if (!isset($_GET['openid_url']) || empty($_GET['openid_url']) &&
    $authSource->getDiscoveryEndpoint())
	$_GET['openid_url'] = $authSource->getDiscoveryEndpoint();

if (array_key_exists('returned', $_GET)) {
	run_finish_auth();
} elseif (!empty($_GET['openid_url'])) {
	run_try_auth();
} else {
	$config = SimpleSAML_Configuration::getInstance();
	$t = new SimpleSAML_XHTML_Template($config, 'openid:consumer.php', 'openid');
	global $authState;
	$t->data['AuthState'] = $authState;
	$t->show();
}



?>
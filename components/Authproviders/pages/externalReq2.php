<?php

/**
 * Post to this page to get a server-server login:
 * Name        Datatype
 * returnUrl   url
 * returnData  string
 *
 * Url-parameters ($_GET):
 * Name        Datatype                  Default
 * auth_abort  url
 * interactive integer (boolean: 0/1)    1 (true)
 * guest       integer (boolean: 0/1)    1 (true)
 *
 * This page will automatically post back
 * Name             Datatype
 * returnData       string
 * loggedIn         integer (boolean: 0/1)
 * openSessionToken string
 *
 * WARNING: BE AWARE THAT ALTHOUGH returnData IS RETURNED AS IS
 * FROM THIS PAGE IT IS NOT PROTECTED AGAINS MALICIOUS MODIFICATION
 * BY ANY THIRD PARTY CAPABLE OF FAKING REQUESTS OR WITH ACCESS
 * TO THE DATA SENT BETWEEN THE CLIENT AND THE SERVERS!
 */

require_once(dirname(__FILE__).'/../../../main.php');

/*
 * Exit from the req2 system
 */
function postToUrl($url, $data)
{
	?>
		<!DOCTYPE HTML>
		<title>Automatic redirect (js)</title>
		<form method="post" action="<?php echo htmlspecialchars($url) ?>">
			<?php
				foreach ($data as $name => $value) {
					?>
						<input type='hidden' name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
					<?php
				}
			?>
			<div>
				<input id='submitb' type='submit' value='Continue' />
				<span id='msg'></span>
			</div>
		</form>
		<script type='text/javascript'>
			<!--
				document.getElementById('submitb').style.display = 'none';
				document.getElementById('msg').innerHTML = <?php echo SERIA_Lib::toJSON(_t('Please wait...')); ?>;
				document.getElementById('submitb').form.submit();
			-->
		</script>
	<?php
}

/*
 * Entering the req2 system
 */
$state = null;
if (sizeof($_POST) && !SERIA_AuthenticationState::available()) {
	$returnUrl = $_POST['returnUrl'];
	if (isset($_GET['auth_abort']))
		$abortUrl = $_GET['auth_abort'];
	else
		$abortUrl = $returnUrl;
	if (!$returnUrl)
		throw new SERIA_Exception('externalReq2 without a return-url');
	if (isset($_POST['returnData']))
		$returnData = $_POST['returnData'];
	else
		$returnData = null;
} else if (SERIA_AuthenticationState::available()) {
	$state = new SERIA_AuthenticationState();
	if (!$state->exists('req2_returnUrl'))
		throw new SERIA_Exception('Req2 without the returnUrl available');
	$returnUrl = $state->get('req2_returnUrl');
	if ($state->exists('req2_returnData'))
		$returnData = $state->get('req2_returnData');
	else
		$returnData = null;
	$abortUrl = $state->get('abort');
} else {
	/*
	 * This could be a bookmarked or stale invokation..
	 */
	if (isset($_GET['auth_abort']))
		SERIA_Base::redirectTo($_GET['auth_abort']);
	throw new SERIA_Exception('Stale externalReq2 with no abort or return-url');
}

$user = SERIA_Base::user();
if ($user !== false) {
	/*
	 * Can grant access..
	 */
	
	if ($state !== null)
		$state->forget();
	$post = new SERIA_ExternalReq2ReturnPost($returnUrl, $user, $returnData);
	if ($post->hostAccessConsentCheck($abortUrl)) {
		$data = $post->getPostData();
		postToUrl($returnUrl, $data);
	} else {
		/* The consent request was rejected (interactive reject will normally not end up here) */
		$data = array(
			'loggedIn' => 0
		);
		if ($returnData !== null)
			$data['returnData'] = $returnData;
		postToUrl($returnUrl, $data);
	}
} else {
	/* Interactive is default on */
	if ($state === null && (!isset($_GET['interactive']) || $_GET['interactive'])) {
		/*
		 * Invoke a login
		 */

		/*
		 * Authentication state entry point (ASEP)
		 */
		$state = new SERIA_AuthenticationState();
		$state->set('abort', $abortUrl);
		$state->set('req2_returnUrl', $returnUrl);
		if ($returnData !== null)
			$state->set('req2_returnData', $returnData);
		$state->push('continue', $returnUrl);
		$state->pushTerminateHook('continue', $state->stampUrl(SERIA_Url::current()->__toString()));

		if (!isset($_GET['guest']) || $_GET['guest'])
			$action = SERIA_AuthproviderActions::getGuestLoginUrl();
		else
			$action = SERIA_AuthproviderActions::getLoginUrl();
		$state->redirectTo($action);
	}
	/*
	 * Return without a login
	 */
	if ($state !== null)
		$state->forget();
	$data = array(
		'loggedIn' => 0
	);
	if ($returnData !== null)
		$data['returnData'] = $returnData;
	postToUrl($returnUrl, $data);
}

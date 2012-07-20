<!DOCTYPE html><?php
require(dirname(__FILE__).'/../../../../main.php');
$loggedIn = $_POST['loggedIn'];
if (!$loggedIn) {
	?>
		<title>Login failed!</title>
		<h1>Login failed!</h1>
		<p>Not logged in after return from external login page!</p>
		<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/tests/components/Authproviders/externalReq2.php');?>">Return to test</a>
	<?php
	return;
}
/*
 * This is not secured, but this is an example.. so... sorry!
 */
?>
	<h1>Authentication check</h1>
	<p>Info:</p>

	<h2>Post-data</h2>
	<pre>
		<?php
			ob_start();
			print_r($_POST);
			$pre = ob_get_clean();
			echo htmlspecialchars($pre);
		?>
	</pre>
<?php
$returnData = unserialize($_POST['returnData']);
?>
	<h2>Received data following the req</h2>
	<pre>
		<?php
			ob_start();
			print_r($returnData);
			$pre = ob_get_clean();
			echo htmlspecialchars($pre);
		?>
	</pre>
<?php

$requestToken = $_POST['openSessionToken'];

function http_get_web_browser()
{
	static $br = null;

	if ($br === null)
		$br = new SERIA_WebBrowser();
	return $br;
}

function http_advanced_get($url, $params)
{
	if (!($url instanceof SERIA_Url))
		$url = new SERIA_Url($url);
	foreach ($params as $name => $value)
		$url->setParam($name, $value);
	$br = http_get_web_browser();
	$br->navigateTo($url->__toString());
	$response = $br->fetchAll();
	return array('url' => $br->url, 'requestHeaders' => $br->requestHeaders, 'responseHeaders' => $br->responseHeaders, 'content' => $response);
}

function http_get($url, $params)
{
	$adv = http_advanced_get($url, $params);
	return $adv['content'];
}

$adv = http_advanced_get($returnData['authBaseUrl'].'/seria/api/', array(
	'apiPath' => 'SAPI_ExternalReq2/getUserData',
	'requestToken' => $requestToken
));

$userData = json_decode($adv['content'], true);

?>
<h2>Received user data</h2>
<table>
	<?php
	foreach ($userData as $name => $value) {
		?>
		<tr><th><?php echo htmlspecialchars($name); ?><td><?php echo htmlspecialchars($value); ?>
		<?php
	}
	?>
</table>

<h3>User data request debug</h3>
<pre>
<?php print_r($adv); ?>
</pre>

<h2>Server-server session test</h2>
<?php

$adv = http_advanced_get($returnData['authBaseUrl'].'/seria/api/', array(
	'apiPath' => 'SAPI_ExternalReq2/getUserSession',
	'requestToken' => $requestToken
));

$userData = json_decode($adv['content'], true);

?>
<p>The session request also includes the user data:</p>
<table>
	<?php
	foreach ($userData as $name => $value) {
		?>
		<tr><th><?php echo htmlspecialchars($name); ?><td><?php echo htmlspecialchars($value); ?>
		<?php
	}
	?>
</table>

<h3>User session request debug</h3>
<pre>
<?php print_r($adv); ?>
</pre>

<h2>Check login session</h2>
<?php

$adv = http_advanced_get($returnData['authBaseUrl'].'/seria/api/', array(
	'apiPath' => 'SAPI_ExternalReq2/checkLogin'
));

$resp = json_decode($adv['content'], true);
if ($resp['loggedIn']) {
	?>
	<p>OK: Logged in as: <?php echo htmlspecialchars($resp['uid']); ?></p>
	<?php
} else {
	?>
	<p>FAILED: Not logged in!</p>
	<?php
}

?>

<h3>User session check request debug</h3>
<pre>
<?php print_r($adv); ?>
</pre>

<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/tests/components/Authproviders/externalReq2.php');?>">Return to test</a>

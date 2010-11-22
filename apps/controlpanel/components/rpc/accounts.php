<?php

	require_once(dirname(__FILE__).'/common.php');

	$baseparams = $_GET;
	if (isset($baseparams['id']))
		unset($baseparams['id']);
	if (!$baseparams)
		$baseparams['rpcsys'] = 'Whiskey'; /* Dummy field */
	$basepath = explode('?', $_SERVER['REQUEST_URI']);
	$basepath = $basepath[0];
	$baseurl = SERIA_HTTP_ROOT.$basepath.'?'.http_build_query($baseparams);

	ob_start();

	$gui->activeMenuItem('controlpanel/settings/rpc/accounts');

	if (isset($_GET['id'])) {
		require(dirname(__FILE__).'/accounts/client.php');
		$gui->contents(ob_get_clean());
		echo $gui->output();
		return;
	}

	echo "<h1 class='legend'>"._t("RPC Client Accounts")."</h1>";
	echo "<p>"._t("Add accounts here to allow others to use resources on this server. They need a client key to access resources here. This client key should be added to their RPC Services in the Control Panel.")."</p>";

	echo SERIA_Fluent::all('SERIA_RPCClientKey')->grid()->output(
		array('client_id', 'name', 'client_key'),
		'<tr onclick="location.href=\''.$baseurl.'&id=%client_id%\';"><td>%client_id%</td><td>%name%</td><td>%client_key%</td></tr>'
	);
?>
<div>
	<button type='button' onclick="<?php echo htmlspecialchars('location.href=\''.$baseurl.'&id=create\';'); ?>"><?php echo htmlspecialchars(_t('New RPC client')); ?></button>
</div>
<?php
	$gui->contents(ob_get_clean());
	echo $gui->output();

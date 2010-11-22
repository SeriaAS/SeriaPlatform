<?php

	require_once(dirname(__FILE__).'/common.php');

	ob_start();
	if (isset($_GET['id'])) {
		require(dirname(__FILE__).'/services/service.php');
		$gui->contents(ob_get_clean());
		echo $gui->output();
		return;
	}
	$gui->activeMenuItem('controlpanel/settings/rpc/services');

	function display_remote_service_callback($obj)
	{
		$baseparams = $_GET;
		if (!$baseparams)
			$baseparams['rpcsys'] = 'Whiskey'; /* Dummy field */
		$basepath = explode('?', $_SERVER['REQUEST_URI']);
		$basepath = $basepath[0];
		$baseurl = SERIA_HTTP_ROOT.$basepath.'?'.http_build_query($baseparams);
		ob_start();
		?>
		<tr onclick="<?php echo htmlspecialchars('location.href=\''.$baseurl.'&id='.$obj->get('service').'\';'); ?>">
			<td><?php echo htmlspecialchars($obj->get('service')); ?></td>
			<td><?php echo htmlspecialchars($obj->get('hostname')); ?></td>
			<td><?php echo htmlspecialchars($obj->get('client_id')); ?></td>
			<td><?php echo htmlspecialchars($obj->get('client_key')); ?></td>
		</tr>
		<?php
		return ob_get_clean();
	}
	echo "<h1 class='legend'>"._t("RPC Services")."</h1>";
	echo "<p>"._t("Add your RPC Client keys here to start using remote services.")."</p>";

	echo SERIA_Fluent::all('SERIA_RPCRemoteService')->grid()->output(array(
			'service', 'hostname', 'client_id', 'client_key'
		),
		'display_remote_service_callback'
	);

	$gui->contents(ob_get_clean());
	echo $gui->output();

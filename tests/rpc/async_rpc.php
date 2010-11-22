<?php

require_once(dirname(__FILE__).'/../../main.php');

ob_start();

if (isset($_GET['service'])) {
	SERIA_Base::pageRequires('login');
	if (!SERIA_Base::isAdministrator())
		die();

	echo 'Starting at: '.date('Y-m-d H:i:s')."\n";

	$rpc = SERIA_RPCClient::connect($_GET['service'], 'SERIA_RPCTest');
	$rpc->loadFramework('rpctest');

	echo 'Calling at: '.date('Y-m-d H:i:s')."\n";

	$rpc->hello();
	$rpc->asynchronousCall('heavyWorkload');

	echo 'Returned from call at: '.date('Y-m-d H:i:s')."\n";
}

$contents = ob_get_clean();

?><html>
	<head>
		<title>Asynchronous RPC test</title>
	</head>
	<body>
		<div>
			<h1>Contents</h1>
			<div>
				<?php echo nl2br(htmlspecialchars($contents)); ?>
			</div>
		</div>
		<form method='get'>
			<div>
				<label for='iservice'>Action:</label>
				<input id='iservice' name='service' type='text' %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<button type='submit'>Submit</button>
			</div>
		</form>
	</body>
</html>

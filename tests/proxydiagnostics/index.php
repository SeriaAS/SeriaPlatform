<!DOCTYPE html>
<?php
	require(dirname(__FILE__).'/../../main.php');
?>
<head>
	<title>Proxy-test</title>
</head>
<h1>Proxy-test</h1>
<?php
	SERIA_ProxyServer::noCache();

	if (!session_id())
		session_start();
	$_SESSION['proxyTestState'] = 'start';
?>
<a href="run.php"><?php echo "Kj\303\270r test"; ?></a>

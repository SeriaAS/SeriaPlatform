<?php

require_once(dirname(__FILE__).'/common.php');

$gui->activeMenuItem('controlpanel/settings/rpc');

ob_start();
?>
<h1 class='legend'><?php echo htmlspecialchars(_t('RPC settings')); ?></h1>
<p><?php echo htmlspecialchars(_t('RPC connects instances of Seria Platform together so that tasks can be distributed over the internet. Here you can insert authentication keys required to do privileged operations on other systems. You can also allow other systems to do privileged operations on this system by adding a new account and settings an authentication key (password). Just give them the client-id and the key and they will be able to insert that into their RPC services page.')); ?></p>
<ul>
	<li><a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/services.php'); ?>"><?php echo htmlspecialchars(_t('RPC services')); ?></a></li>
	<li><a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/accounts.php'); ?>"><?php echo htmlspecialchars(_t('RPC client accounts')); ?></a></li>
</ul>
<?php
$gui->contents(ob_get_clean());

echo $gui->output();

?>

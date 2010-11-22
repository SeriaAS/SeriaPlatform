<?php
	require('common.php');
	$menu->addLink(_t("Add memcached server"), SERIA_HTTP_ROOT . '/seria/settings/memcached/addserver.php');
	$menu->addLink(_t('<< Back to settings'), SERIA_HTTP_ROOT . '/seria/settings/', 1000);
	
	function memcachedServerStatus($value) {
		if ($value) {
			return _t('Active');
		} else {
			return _t('Disabled');
		}
	}
?>
<h1 class="legend"><?php echo _t('Memcached servers'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<p>
	<?php echo _t('Memcached servers is used to gain higher cache performance.'); ?>
</p>
<p>
	<strong><?php echo _t('Warning: Incorrect memcached settings will reduce performance.') ?></strong>
</p>
<?php
	function format_delay($delay) {
		return (int) $delay . ' seconds';
	}
?>

<?php
	$datagrid = new SERIA_HtmlArDatagrid(SERIA_MemcacheServers::find_all(), array(
		'address' => _t('Hostname/IP address'),
		'port' => _t('Port'),
		'enabled' => array(_t('Status'), 'memcachedServerStatus'),
		'edit' => '',
		'delete' => ''
	),
	array(
		'edit' => '<a href="addserver.php?edit={ID}">' . _t('Edit') . '</a>',
		'delete' => '<a onclick="return confirm(\'' . _t('Are you sure you want to remove this memcached server?') . '\')" href="delete.php?id={ID}">' . _t('Delete') . '</a>'
	));
	
	$datagrid->setColumnCssClass('address', 'tableMaxWidth');
	$datagrid->setColumnClickUrlTemplate(array('address', 'port', 'enabled'), 'addserver.php?edit={ID}#status');
		
	echo $datagrid->render();
?>

<?php
	require('common_tail.php');
?>
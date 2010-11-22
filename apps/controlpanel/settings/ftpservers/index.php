<?php
	require('common.php');
	$menu->addLink(_t("Add FTP server"), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/addserver.php');
	$menu->addLink(_t('<< Back to settings'), SERIA_HTTP_ROOT . '/seria/settings/', 1000);
?>
<h1 class="legend"><?php echo _t('Mirror FTP servers'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<p>
	<?php echo _t('Mirror FTP servers provides support for mirroring files on other servers for reducing load on the web server. Mirror FTP servers can also be used to support files on other protocols like media streaming.'); ?>
</p>
<?php
	function format_delay($delay) {
		return (int) $delay . ' seconds';
	}
?>

<?php
	$datagrid = new SERIA_HtmlArDatagrid(SERIA_FTPs::find_all(), array(
		'host' => _t('Hostname'),
		'port' => _t('Port'),
		'username' => _t('Username'),
		'file_root' => _t('File&nbsp;root'),
		'delay' => array(_t('Delay'), 'format_delay'),
		'edit' => '',
		'delete' => ''
	),
	array(
		'edit' => '<a href="addserver.php?edit={ID}">' . _t('Edit') . '</a>',
		'delete' => '<a onclick="return confirm(\'' . _t('Are you sure you want to remove this FTP server?') . '\')" href="delete.php?id={ID}">' . _t('Delete') . '</a>'
	));
	
	$datagrid->setColumnCssClass('host', 'tableMaxWidth');
	$datagrid->setColumnCssClass('delay', 'nobr');
	$datagrid->setColumnClickUrlTemplate(array('host', 'port', 'username', 'file_root', 'delay'), 'addserver.php?edit={ID}');
	echo $datagrid->render();
?>

<?php
	require('common_tail.php');
?>
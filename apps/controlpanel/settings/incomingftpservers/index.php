<?php
	require('common.php');
	$menu->addLink(_t("Add incoming FTP server"), SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/addserver.php');
	$menu->addLink(_t('<< Back to settings'), SERIA_HTTP_ROOT . '/seria/settings/', 1000);
?>
<h1 class="legend"><?php echo _t('Incoming FTP servers'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<p>
	<?php echo _t('Incoming FTP servers is used to provide files for the file select dialog. Files on the configured FTP servers is available for usage when a file is required.'); ?>
</p>

<?php
	$datagrid = new SERIA_HtmlArDatagrid(SERIA_IncomingFtpServers::find_all(), array(
		'title' => _t('Title'),
		'hostname' => _t('Hostname'),
		'port' => _t('Port'),
		'username' => _t('Username'),
		'root' => _t('Root&nbsp;directory'),
		'edit' => '',
		'delete' => ''
	),
	array(
		'edit' => '<a href="addserver.php?edit={ID}">' . _t('Edit') . '</a>',
		'delete' => '<a onclick="return confirm(\'' . _t('Are you sure you want to remove this FTP server?') . '\')" href="delete.php?id={ID}">' . _t('Delete') . '</a>'
	));
	
	$datagrid->setColumnCssClass('title', 'tableMaxWidth');
	$datagrid->setColumnClickUrlTemplate(array('title', 'hostname', 'port', 'username', 'root'), 'addserver.php?edit={ID}');
	
	
	echo $datagrid->render();
?>

<?php
	require('common_tail.php');
?>
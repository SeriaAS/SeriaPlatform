<?php
	$ftpServer = $ftp;
	if (!$ftpServer) {
		die();
	}
	
	$fileTypes = $ftpServer->Filetypes;
?>
	
<?php
	$datagrid = new SERIA_HtmlArDatagrid($fileTypes, array(
		'pattern' => _t('Filename pattern'),
		'type' => _t('Type'),
		'delete' => ''
	),
	array(
		'delete' => '<a href="filetype_delete.php?id={ID}&amp;ftp_server_id=' . $ftpServer->id . '">' . _t('Delete') . '</a>'
	));
	
	echo $datagrid->render();
?>
<?php
	$ftpServer = $ftp;
	if (!$ftpServer) {
		die();
	}
	
	$fileProtocols = $ftpServer->FileProtocols;
	
	$datagrid = new SERIA_HtmlArDatagrid($fileProtocols, array(
		'name' => array(_t('Protocol name'), 'strtoupper'),
		'delete' => ''
	),
	array(
		'delete' => '<a href="protocol_delete.php?id={ID}&amp;ftp_server_id=' . $ftpServer->id . '">' . _t('Delete') . '</a>'
	));
	
	echo $datagrid->render();
?>
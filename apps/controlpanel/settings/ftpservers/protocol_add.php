<?php
	require('common.php');

	$ftpServer_id = (int) $_GET['ftp_server_id'];
	if (!$ftpServer_id || (!($ftpServer = SERIA_FTPs::find($ftpServer_id, array('include' => array('Fileprotocols')))))) {
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/ftpservers/');
		die();
	}
	$menu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/protocols.php?ftp_server_id=' . $ftpServer->id);
?>

<?php
	$availableProtocols = SERIA_FtpFileprotocol::$availableProtocols;

	$fileProtocol = new SERIA_FtpFileprotocol();
	if ($fileProtocol->fromPost()) {
		$fileProtocol->ftp_server_id = $ftpServer->id;
		if ($fileProtocol->isValid()) {
			$fileProtocol->save();
			SERIA_HtmlFlash::notice(_t('File protocol handler was successfully created'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/ftpservers/addserver.php?edit=' . $ftpServer->id . '#protocols');
		}
	}
	
	$form = new SERIA_HtmlForm($fileProtocol);
?>
<h1 class="legend"><?php echo _t('Add new file protocol'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<?php echo $form->start(); ?>
	<?php echo $form->errors(); ?>
	
	<table>
		<tr>
			<td>
				<?php echo $form->label('name', _t('File protocol: ')); ?>
			</td>
			<td>
				<?php echo $form->select('name', $availableProtocols); ?>
			</td>
		</tr>
	</table>
	<?php echo $form->submit('Save'); ?>
	
<?php echo $form->end(); ?>

<?php
	require('common_tail.php');
?>
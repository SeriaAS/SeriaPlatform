<?php
	require('common.php');

	$ftpServer_id = (int) $_GET['ftp_server_id'];
	if (!$ftpServer_id || (!($ftpServer = SERIA_FTPs::find($ftpServer_id, array('include' => array('Filetypes')))))) {
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/ftpservers/');
		die();
	}
	$menu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/filetypes.php?ftp_server_id=' . $ftpServer->id);
?>

<?php
	$fileType = new SERIA_FtpFiletype();
	if ($fileType->fromPost()) {
		$fileType->ftp_server_id = $ftpServer->id;
		if ($fileType->isValid()) {
			$fileType->save();
			SERIA_HtmlFlash::notice(_t('File type was successfully added'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/ftpservers/addserver.php?edit=' . $ftpServer->id . '#filetypes');
			die();
		}
	}
	
	$form = new SERIA_HtmlForm($fileType);
?>

<h1 class="legend"><?php echo _t('Add new file type'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<?php echo $form->start(); ?>
	<?php echo $form->errors(); ?>
	
	<table>
		<tr>
			<td>
				<?php echo $form->label('pattern', _t('Filename pattern (example: *.txt): ')); ?>
			</td>
			<td>
				<?php echo $form->text('pattern'); ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php echo $form->label('type', _t('Include or exclude: ')); ?>
			</td>
			<td>
				<?php echo $form->select('type', array('include' => _t('Upload files matching pattern'), 'exclude' => _t('Don\'t upload files matching pattern'))); ?>
			</td>
		</tr>
	</table>
	<?php echo $form->submit('Save'); ?>
	
<?php echo $form->end(); ?>

<?php
	require('common_tail.php');
?>
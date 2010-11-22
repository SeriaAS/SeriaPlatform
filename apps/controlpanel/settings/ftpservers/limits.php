<?php
	$ftpServer = $ftp;
	if (!$ftpServer) {
		die();
	}
	
	$limits = $ftpServer->ServerLimit;
	try {
		if (!$limits->id) {
			throw new Exception('');
		}
	} catch (Exception $null) {
		$limits = new SERIA_FtpServerLimit();
		$limits->maxfilecount = 0;
		$limits->maxfilesize = 0;
		$limits->maxstorageusage = 0;
		$limits->ftp_server_id = $ftpServer->id;
		$limits->save();
	}
	
	if ($limits->fromPost()) {
		if ($limits->isValid()) {
			$limits->save();
			SERIA_HtmlFlash::notice(_t('Limits was successfully saved'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/ftpservers/addserver.php?edit=' . $ftpServer->id . '&' . mt_rand(0,1000) . '#limits');
			die();
		}
	}
	
	$form = new SERIA_HtmlForm($limits);
?>

<?php SERIA_HtmlFlash::show(); ?>

<?php echo $form->start(); ?>

<?php echo $limits->getErrorHtml(); ?>
	<table>
		<tr>
			<td>
				<?php echo $form->label('maxstorageusage', _t('Maximum total storage usage: ')); ?><br />
			</td>
			<td>
				<?php echo $form->text('maxstorageusage'); ?> <?php echo _t('MB (0 for unlimited)'); ?>
			</td>
		</tr>
		
		<tr>
			<td>
				<?php echo $form->label('maxfilesize', _t('Maximum single file size: ')); ?>
			</td>
			<td>
				<?php echo $form->text('maxfilesize'); ?> <?php echo _t('kB (0 for unlimited)'); ?>
			</td>
		</tr>
		
		<tr>
			<td>
				<?php echo $form->label('maxfilecount', _t('Maximum file count: ')); ?>
			</td>
			<td>
				<?php echo $form->text('maxfilecount'); ?> <?php echo _t('(0 for unlimited)'); ?>
			</td>
		</tr>
	</table>
	
	<?php echo $form->submit(_t('Save limits')); ?>
<?php echo $form->end(); ?>

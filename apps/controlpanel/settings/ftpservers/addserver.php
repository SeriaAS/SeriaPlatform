<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");

	ob_start();	
	
	$edit_id = (int) $_GET['edit'];
	if ($edit_id) {
		$ftp = SERIA_FTPs::find($edit_id);
		if ($ftp) {
			$menu->addLink(_t('Add file type'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/filetype_add.php?ftp_server_id=' . $ftp->id);
			$menu->addLink(_t('Add file protocol'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/protocol_add.php?ftp_server_id=' . $ftp->id);
			$menu->addLink(_t('<< Back to FTP servers'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/', 1000);
		}
	} else {
		$menu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/', 1000);
	}
?>
<?php if (!$ftp) { ?>
	<h1 class="legend"><?php echo _t('Add new FTP server'); ?></h1>
<?php } else { ?>
	<h1 class="legend"><?php echo _t('Edit FTP server %SERVER%', array('SERVER' => $ftp->host)); ?></h1>
	<div class="tabs">
		<ul>
			<li><a href="#edit"><span><?php echo _t('Edit'); ?></span></a></li>
			<li><a href="#filetypes"><span><?php echo _t('File types'); ?></span></a></li>
			<li><a href="#protocols"><span><?php echo _t('Protocol handlers'); ?></span></a></li>
			<li><a href="#limits"><span><?php echo _t('File limits'); ?></span></a></li>
		</ul>
	</div>
<?php } ?>

<?php
	if (!$ftp) {
		$ftp = new SERIA_FTP();
		$ftp->port = 21;
		$ftp->pasv = 1;
		$ftp->delay = 0;
	}
	
	if ($ftp->fromPost()) {
		if ($ftp->isValid()) {
			$ftp->save();
			SERIA_HtmlFlash::notice(_t('FTP server was successfully saved'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/ftpservers/');
		}
	}
	
	$form = new SERIA_HtmlForm($ftp);
?>

<?php $flash = SERIA_HtmlFlash::getHtml(); ?>
<?php echo $flash; ?>

<div id="edit">
	<?php require('edit_form.php'); ?>
</div>

<?php if ($ftp->id) { ?>
	<div id="filetypes">
		<?php require('filetypes.php'); ?>
	</div>
	
	<div id="protocols">
		<?php require('protocols.php'); ?>
	</div>
	
	<div id="limits">
		<?php require('limits.php'); ?>
	</div>
<?php } ?>

<?php
	$contents = ob_get_clean();
	
	$gui->contents($contents);
	$gui->output();
?>
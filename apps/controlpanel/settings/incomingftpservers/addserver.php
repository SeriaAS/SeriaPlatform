<?php
	require_once(dirname(__FILE__)."/../common.php");
	
	SERIA_Base::pageRequires("admin");

	ob_start();	
	
	$edit_id = (int) $_GET['edit'];
	if ($edit_id) {
		$ftp = SERIA_IncomingFtpServers::find($edit_id);
		if ($ftp) {
			$menu->addLink(_t('<< Back to FTP servers'), SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/', 1000);
		}
	} else {
		$menu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/', 1000);
	}
?>
<?php if (!$ftp) { ?>
	<h1 class="legend"><?php echo _t('Add new Incoming FTP server'); ?></h1>
<?php } else { ?>
	<?php
		if (trim($ftp->title)) {
			$title = trim($ftp->title);
		} else {
			$title = $ftp->hostname;
		}
	?>
	<h1 class="legend"><?php echo _t('Edit FTP server %SERVER%', array('SERVER' => htmlspecialchars($title))); ?></h1>
	<div class="tabs">
		<ul>
			<li><a href="#edit"><span><?php echo _t('Edit'); ?></span></a></li>
		</ul>
	</div>
<?php } ?>

<?php
	if (!$ftp) {
		$ftp = new SERIA_IncomingFtpServer();
		$ftp->port = 21;
	}
	
	if ($ftp->fromPost()) {
		if ($ftp->isValid()) {
			$ftp->save();
			SERIA_HtmlFlash::notice(_t('Incoming FTP server was successfully saved'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/');
		}
	}
	
	$form = new SERIA_HtmlForm($ftp);
?>

<?php $flash = SERIA_HtmlFlash::getHtml(); ?>
<?php echo $flash; ?>

<div id="edit">
	<?php require('edit_form.php'); ?>
</div>

<?php
	$contents = ob_get_clean();
	
	$gui->contents($contents);
	$gui->output();
?>
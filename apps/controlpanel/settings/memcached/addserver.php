<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");

	ob_start();	
	
	$edit_id = (int) $_GET['edit'];
	if ($edit_id) {
		$server = SERIA_MemcacheServers::find($edit_id);
		if ($server) {
			$menu->addLink(_t('<< Back to memcached servers'), SERIA_HTTP_ROOT . '/seria/settings/memcached/', 1000);
		}
	} else {
		$menu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/settings/memcached/', 1000);
	}
?>
<?php if (!$server) { ?>
	<h1 class="legend"><?php echo _t('Add new memcached server'); ?></h1>
<?php } else { ?>
	<h1 class="legend"><?php echo _t('Edit memcached server %SERVER%', array('SERVER' => $ftp->address)); ?></h1>
	<div class="tabs">
		<ul>
			<li><a href="#edit"><span><?php echo _t('Edit'); ?></span></a></li>
			<?php if ($server->id) { ?>
				<li><a href="#status"><span><?php echo _t('Status'); ?></span></a></li>
			<?php } ?>
		</ul>
	</div>
<?php } ?>

<?php
	if (!$server) {
		$server = new SERIA_MemcacheServer();
		$server->port = 11211;
	}
	
	if ($server->fromPost()) {
		if ($server->isValid()) {
			$server->save();
			SERIA_HtmlFlash::notice(_t('Memcached server was successfully saved'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/memcached/');
		}
	}
	
	$form = new SERIA_HtmlForm($server);
?>

<?php $flash = SERIA_HtmlFlash::getHtml(); ?>
<?php echo $flash; ?>

<div id="edit">
	<?php require('edit_form.php'); ?>
</div>

<?php if ($server->id) { ?>
	<div id="status">
		<?php try { ?>
			<?php require(SERIA_ROOT . '/seria/status/memcachedstatus.php'); ?>
		<?php } catch (Exception $null) { ?>
			<p>This memcached server is not available</p>
		<?php } ?>
	</div>
<?php } ?>

<?php
	$contents = ob_get_clean();
	
	$gui->contents($contents);
	$gui->output();
?>
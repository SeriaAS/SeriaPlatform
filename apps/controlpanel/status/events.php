<?php
	require('common.php');
	ob_start();	
	
	$cache = new SERIA_Cache('statusMessages');
	$cache->set('messageCount', null, 1);
	
	$categories = array('system' => _t('System'), 'security' => _t('Security'), 'performance' => _t('Performance'), 'content' => _t('Content'));
	
	foreach ($categories as $id => $null) {
		$messagesInCategory[$id] = SERIA_SystemStatusMessages::find_all_by_category($id, array('criterias' => array('status' => 0), 'limit' => 10000, 'order' => array('time' => 'desc')));
	}
?>

<?php
	function formatMessageLevel($level) {
		switch ($level) {
			case SERIA_SystemStatus::NOTICE:
				return _t('Notice');
				break;
			case SERIA_SystemStatus::WARNING:
				return _t('Warning');
				break;
			case SERIA_SystemStatus::ERROR:
				return _t('Error');
				break;
		}
	}
?>

<!-- *** TEMPLATE START *** -->
<h1 class="legend"><?php echo _t('Status panel'); ?></h1>

<?php SERIA_HtmlFlash::show(); ?>

<h2><?php echo _t('Event log'); ?></h2>

<div class="tabs">
	<ul>
		<?php foreach ($categories as $id => $title) { ?>
			<li><a href="#<?php echo $id ?>"><span><?php echo $title; ?>(<?php echo $messagesInCategory[$id]->count; ?>)</span></a></li>
		<?php } ?>
	</ul>
</div>

<style type='text/css'>
table.grid thead th.level {
	width: 60px;
}
table.grid thead th.time {
	width: 110px;
}
</style>

<?php foreach ($categories as $id => $title) { ?>
	<div id="<?php echo $id; ?>">
		<form action="close.php" method="post">
			<input type="hidden" name="intab" value="<?php echo $id; ?>" />
			<?php
				$messages = $messagesInCategory[$id];
			
				$datagrid = new SERIA_HtmlArDatagrid($messages,
				array(
					'level' => array(_t('Level'), 'formatMessageLevel'),
					'time' => _t('Time'),
					'message' => array(_t('Message'), '_t')
				),
				array(
				));
				$datagrid->addBatchSelect('status');
				$datagrid->setColumnCssClass('time', 'nobr');
				echo $datagrid->render();
			?>
			<div class="batchOperations">
				<input type="submit" name="closeSelected" value="<?php echo _t('Close selected'); ?>" />
				<input type="submit" name="closeOld" value="<?php echo ('Close all older than one hour'); ?>" />
			</div>
		</form>
	</div>
<?php } ?>
<!-- *** TEMPLATE END *** -->
	
<?php
	$contents = ob_get_clean();
	
	$gui->contents($contents);
	$gui->output();
?>

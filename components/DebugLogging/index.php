<?php

require(dirname(__FILE__).'/../../main.php');

SERIA_Base::pageRequires('admin');

set_time_limit(120);

$app = SERIA_Components::getComponent('debug_logging_component_jep');
if (!$app)
	throw new SERIA_Exception('Debug logging component is not registered.');
$gui = new SERIA_GUI($app->getName());
$gui->title(_t('Debug logging'));
$gui->activeMenuItem('controlpanel/other/debuglogging/logs');

$logViewActions = ViewLogAction::getAll((isset($_GET['pager']) && intval($_GET['pager'], 10)) ? intval($_GET['pager'], 10) : false);
ob_start();

$action = $invoked = false;

foreach ($logViewActions as $action) {
	if ($action->invoked()) {
		$invoked = $action;
		break;
	}
}

if ($invoked) {
	unset($logViewActions);
	$action = $invoked;
	unset($invoked);
	$log = $action->getLog();
	?>
		<h1 class='legend'><?php echo _t('Debug log'); ?></h1>
		<table class='grid'><thead><tr><?php echo '<th>'._t('Time').'</th><th>'._t("Text").'</th>'; ?></tr></thead><tbody>
	<?php
	foreach ($log as $l) {
		$logline = $l;
		?><tr><?php echo '<td>'.date('H:i:s', $logline['ts']).'</td><td>'.htmlspecialchars($logline['msg']).'</td>'; ?></tr>
		<?php
	}

	?></tbody></table><?php

	$data = $action->getViewData(); 
	foreach ($data as $name => $values) {
		switch ($name) {
			case '$_GET':
			case '$_POST':
			case '$_COOKIE':
			case '$_SESSION':
			case '$_SERVER':
			case '$_FILES':
				?>
				<h2><?php echo htmlspecialchars($name); ?></h2>
				<?php
				ob_start();
				print_r($values);
				$values = ob_get_clean();
				?>
				<textarea rows="25" cols="80" style="width: 950px;"><?php echo htmlspecialchars($values); ?></textarea>
				<?php
		}
	}

	$gui->contents(ob_get_clean());
	echo $gui->output();
	return;
}

?>
	<h1 class='legend'><?php echo _t('Please select a page view from the list below'); ?></h1>
	<form method='get'>
		<div>
			<select name='pager'>
				<option value='0'><?php echo _t('Live logs'); ?></option>
				<option value='1'><?php echo _t('Archived logfile 1'); ?></option>
				<option value='2'><?php echo _t('Archived logfile 2'); ?></option>
				<option value='3'><?php echo _t('Archived logfile 3'); ?></option>
				<option value='4'><?php echo _t('Archived logfile 4'); ?></option>
				<option value='5'><?php echo _t('Archived logfile 5'); ?></option>
				<option value='6'><?php echo _t('Archived logfile 6'); ?></option>
			</select>
			<input type='submit' value="<?php echo htmlspecialchars(_t('Show logfile!')); ?>">
		</div>
	</form>
	<table class='grid'>
	<thead><tr>
		<th style='width: 135px'><?php echo _t('Time'); ?></th>
		<th><?php echo _t('Server hostname'); ?></th>
		<th><?php echo _t('URI'); ?></th>
	</tr></thead>
<?php
foreach ($logViewActions as $action) {
	?>
		<tr onclick='location.href="<?php echo $action; ?>";'>
			<td><?php echo date('Y-m-d H:i:s', $action->getTimestamp()); ?></td>
			<td><?php echo htmlspecialchars($action->getServerHost()->__toString()); ?></td>
			<td><?php echo htmlspecialchars($action->getPageUrl()->__toString()); ?></td>
		</tr>
	<?php
}
?>
	</table>
<?php

$gui->contents(ob_get_clean());
echo $gui->output();

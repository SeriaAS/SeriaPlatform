<?php
	$syncType = NDLA_SyncLog::getSyncType($_GET['multimode']);
	if ($syncType === NULL)
		throw new SERIA_Exception('Sync type not found', SERIA_Exception::NOT_FOUND);
	$avail = NDLA_SyncLog::loadSync2();
	list($caption, $callable, $parts) = $avail[$syncType];

	if (isset($_POST['advsync'])) {
		$run = array();
		foreach ($parts as $part => $partcapt) {
			if (isset($_POST[$part]) && $_POST[$part])
				$run[] = $part;
		}
		call_user_func($callable, $run);
		?>
		<s:gui title="Started sync">
		</s:gui>
		<script type='text/javascript'>
			<!--
			$(document).ready(function () {
				alert(<?php echo SERIA_Lib::toJSON(_t('Started manual partial sync.')); ?>);
				top.location.href = <?php echo SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=ndlasyncschedules/edit'); ?>
			});
			-->
		</script>
		<?php
		return;
	}
?>
<s:gui title="{'Advanced sync options'|_t|htmlspecialchars}">
	<?php
	$this->gui->activeMenuItem('controlpanel/settings/ndlasyncschedule');
	?>
	<form method="post">
		<input type="hidden" name="advsync" value="do">
		<div>
			<h1 class='legend'><?php echo _t('Advanced sync options: %CAPT%', array('CAPT' => $caption)); ?></h1>
			<p><?php echo _t("Please make sure that you know exactly what you are doing and which side effects it will cause!"); ?></p>
			<table>
				<thead></thead>
				<tfoot><tr><td>
						<input type="submit" value="<?php echo htmlspecialchars(_t('Run advanced sync')); ?>">
				<tbody><tr><td>
					<ul>
						<?php
						foreach ($parts as $part => $partcapt) {
							?><li><label><input type='checkbox' name="<?php echo htmlspecialchars($part); ?>" checked="checked"> <?php echo htmlspecialchars($partcapt); ?></label><?php
						}
						?>
					</ul>
			</table>
		</div>
	</form>
</s:gui>
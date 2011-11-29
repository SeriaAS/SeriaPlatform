<?php
	if ($this->id === false) {
		$this->title = _t('Add new alerter channel');
		$obj = new SERIA_AlerterChannel();
	} else {
		$this->title = _t('Edit alerter channel');
		$obj = SERIA_Meta::load('SERIA_AlerterChannel', $this->id);
	}
?><s:gui title="{title|_t|htmlspecialchars}">
	<h1 class='legend'>{{title|_t}}</h1>
	<?php
		if ($this->id !== false) {
			$jsgen = new SERIA_AlertGenerator($obj);
			ob_start();
?>
<script type="text/javascript">
	<!--
		function alerterLoadedCallback()
		{
			/* Code that runs after the alerter script has loaded */
		}
	-->
</script>
<?php
			echo SERIA_AlertGenerator::generateListenerCodeWithCallback($jsgen->getFilename(), 'alerterLoadedCallback');
			$code = ob_get_clean();
			?>
				<h2>{{'Javascript code'|_t}}</h2>
				<textarea cols='80' rows='12'><?php echo htmlspecialchars($code); ?></textarea>
				<h2>{{'Javascript publish point'|_t}}</h2>
				<p><?php echo htmlspecialchars($jsgen->getFilename()); ?></p>
			<?php
		}
	?>
	<h2>{{'Channel information'|_t}}</h2>
	<?php
		$this->gui->activeMenuItem('outboard/alerter');
		$action = $obj->editAction();
		if ($action->success) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=outboard/alerter');
			die();
		}
		echo $action->begin();
	?>
	<table>
		<tfoot>
			<tr>
				<td colspan='2'>
					<?php
						echo $action->submit(_t('Save'));
					?>
					<input type='button' value="{{'Cancel'|_t}}" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/alerter')); ?>" />
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<th><?php echo $action->label('name'); ?></th>
				<td><?php echo $action->field('name'); ?></td>
			</tr>
		</tbody>
	</table>
	<?php
		echo $action->end();
	?>
</s:gui>

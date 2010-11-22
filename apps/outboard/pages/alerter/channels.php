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
			?><script type="text/javascript">document.write(unescape("%3Cscript type=\"text/javascript\" src=\"<?php echo htmlspecialchars($jsgen->getFilename()); ?>?r=" + Math.floor((new Date()).getTime() / 60000) + "\"%3E%3C/script%3E"));</script><?php
			$code = ob_get_clean();
			?>
				<h2>{{'Javascript code'|_t}}</h2>
				<textarea cols='80' rows='3'><?php echo htmlspecialchars($code); ?></textarea>
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

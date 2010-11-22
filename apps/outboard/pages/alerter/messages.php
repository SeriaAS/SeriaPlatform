<?php
	if ($this->id === false) {
		$this->title = _t('Add new message');
		$obj = new SERIA_AlerterMessage();
	} else {
		$this->title = _t('Edit message');
		$obj = SERIA_Meta::load('SERIA_AlerterMessage', $this->id);
	}
?><s:gui title="{title|_t|htmlspecialchars}">
	<h1 class='legend'>{{title|_t}}</h1>
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
							<input type='button' value="{{'Cancel'|_t|htmlspecialchars}}" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/alerter')); ?>" />
							<?php
								if ($this->id !== false) {
									?>
									<input type='button' value="{{'Schedule'|_t|htmlspecialchars}}" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/schedule/'.$obj->get('id'))); ?>" />
									<?php
								}
							?>
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<th><?php echo $action->label('title'); ?></th>
						<td><?php echo $action->field('title'); ?></td>
					</tr>
					<tr>
						<th><?php echo $action->label('message'); ?></th>
						<td><?php echo $action->field('message'); ?></td>
					</tr>
				</tbody>
			</table>
		<?php
		echo $action->end();
	?>
</s:gui>
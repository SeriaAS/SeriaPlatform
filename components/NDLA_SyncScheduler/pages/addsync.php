<s:gui title="{'NDLA add sync'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('controlpanel/settings/ndlasyncschedule');
	?>
	<h1 class='legend'>{{'NDLA add sync'|_t}}</h1>
	<?php
		if ($this->sync === false)
			$action = NDLA_ScheduledSync::addAction();
		else
			$action = $this->sync->editAction();
		if ($action->success) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=ndlasyncschedules/edit');
			die();
		}
		echo $action->begin();
		?>
			<table>
				<?php
				/* According to w3 spec:
				 * "TFOOT must appear before TBODY within a TABLE definition so that
				 * user agents can render the foot before receiving all of the
				 * (potentially numerous) rows of data."
				 */
				?>
				<tfoot>
					<tr>
						<?php
						/* This is a violation of the w3 recommendations about tfoot
						 * contents, and may look weird on printed or spoken media.
						 */
						?>
						<td colspans='2'><?php echo $action->submit(_t($this->sync === false ? 'Add scheduled sync' : 'Edit scheduled sync')); ?></td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<th><?php echo $action->label('syncDate'); ?></th>
						<td><?php echo $action->field('syncDate'); ?></td>
					</tr>
					<tr>
						<th><?php echo $action->label('description'); ?></th>
						<td><?php echo $action->field('description'); ?></td>
					</tr>
				</tbody>
			</table>
		<?php
		echo $action->end();
	?>
</s:gui>
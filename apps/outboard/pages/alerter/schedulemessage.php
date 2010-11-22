<?php
	$this->title = 'Schedule message';
?>
<s:gui title="{title|_t|htmlspecialchars}">
	<h1 class='legend'>{{title|_t}}</h1>
	<?php
		$this->gui->activeMenuItem('outboard/alerter');
		$message = SERIA_Meta::load('SERIA_AlerterMessage', $this->messageId);
		if ($this->id !== false) {
			$schedule = SERIA_Meta::load('SERIA_AlerterSchedule', $this->id);
			if ($schedule->get('message')->get('id') != $message->get('id'))
				throw new SERIA_NotFoundException('This is not a scheduled display of the specified message');
		} else {
			$schedule = new SERIA_AlerterSchedule();
			$schedule->set('message', $message);
			$ts = time();
			$schedule->set('start', new SERIA_DateTimeMetaField($ts));
			$schedule->set('stop', new SERIA_DateTimeMetaField(mktime(
				intval(date('G', $ts)),
				intval(date('i', $ts)),
				intval(date('s', $ts)),
				intval(date('n', $ts)) + 1,
				intval(date('j', $ts)),
				intval(date('Y', $ts))
			)));
		}
		$action = $schedule->editAction();
		if ($action->success) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=outboard/schedule/'.$message->get('id'));
			die();
		}
		echo $action->begin();
	?>
	<table>
		<tfoot>
			<tr>
				<td colspan='2'>
					<?php echo $action->submit(_t('Save')); ?>
					<input type='button' value="{{'Cancel'|_t|htmlspecialchars}}" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/message/'.$message->get('id'))); ?>" />
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<th><?php echo $action->label('start'); ?></th>
				<td><?php echo $action->field('start'); ?></td>
			</tr>
			<tr>
				<th><?php echo $action->label('stop'); ?></th>
				<td><?php echo $action->field('stop'); ?></td>
			</tr>
			<?php
				$first = true;
				$channels = $schedule->availableChannels();
				foreach ($channels as $channel) {
					?>
						<tr>
							<?php
								if ($first) {
									$first = false;
									?>
										<th rowspan='<?php echo count($channels); ?>'>{{'Channels'|_t}}</th>
									<?php
								}
							?>
							<td>
								<?php
									echo $action->field('channel'.$channel->get('id'));
									echo $action->label('channel'.$channel->get('id'));
								?>
							</td>
						</tr>
					<?php
				}
			?>
		</tbody>
	</table>
	<?php
		echo $action->end();
	?>
</s:gui>
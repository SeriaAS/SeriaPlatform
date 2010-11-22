<s:gui title="{'Message schedule'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('outboard/alerter');

		$message = SERIA_Meta::load('SERIA_AlerterMessage', $this->id);

		?>
			<table>
				<tbody>
					<tr>
						<th>{{'Title:'|_t}}</th>
						<td><?php echo $message->get('title'); ?></td>
					</tr>
					<tr>
						<th>{{'Text:'|_t}}</th>
						<td><?php echo $message->get('message'); ?></td>
					</tr>
				</tbody>
			</table>
		<?php

		try {
			$grid = new SERIA_MetaGrid(SERIA_Meta::all('SERIA_AlerterSchedule')->where('message = :message', array('message' => $message->get('id'))));
			$grid->addButton(_t('Add'), SERIA_HTTP_ROOT.'?route=outboard/schedule/'.$message->get('id').'/message');

			function alerter_schedule_row($object)
			{
				ob_start();
				$channels = array();
				$chobjs = $object->enabledChannels();
				foreach ($chobjs as $channel)
					$channels[] = $channel->get('name');
				?>
					<tr onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/schedule/'.$object->get('message')->get('id').'/message/'.$object->get('id'))); ?>">
						<td><?php echo $object->get('start')->__toString(); ?></td>
						<td><?php echo $object->get('stop')->__toString(); ?></td>
						<td><?php echo implode(', ', $channels); ?></td>
					</tr>
				<?php
				return ob_get_clean();
			}

			echo $grid->output(
				array(
					'start',
					'stop',
					_t('Channels')
				),
				'alerter_schedule_row'
			);
		} catch (SERIA_Exception $e) {
			if (SERIA_DEBUG && $e->getCode() == SERIA_Exception::NOT_FOUND) {
				/* oh well.. this will cause a 404 which we don't want here for debugging purposes. */
				die($e->getTraceAsString());
			}
			throw $e;
		}
	?>
</s:gui>
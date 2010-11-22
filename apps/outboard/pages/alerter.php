<s:gui title="{'Alerter'|_t|htmlspecialchars}">
	<h1 class='legend'>{{'Alerter'|_t}}</h1>
	<h2>Messages</h2>
	<?php
		$this->gui->activeMenuItem('outboard/alerter');

		$grid = new SERIA_MetaGrid(SERIA_Meta::all('SERIA_AlerterMessage'));
		$grid->addButton(_t('Add message'), SERIA_HTTP_ROOT.'?route=outboard/message');
		function display_message_row($obj)
		{
			$delete = SERIA_Meta::deleteAction('DeleteMessage', $obj);
			if ($delete->success) {
				SERIA_Base::redirectTo($delete->removeFromUrl(SERIA_Url::current())->__toString());
				die();
			}
			ob_start();
			?>
				<tr mnu="{{'Delete'|_t|htmlspecialchars}}:top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($delete->__toString())); ?>" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/message/'.$obj->get('id'))); ?>">
					<td><?php echo $obj->get('title'); ?></td>
					<td><?php echo $obj->get('message'); ?></td>
				</tr>
			<?php
			return ob_get_clean();
		}
		echo $grid->output(array('title', 'message'), 'display_message_row');
	?>
	<h2>Channels</h2>
	<?php
		$grid = new SERIA_MetaGrid(SERIA_Meta::all('SERIA_AlerterChannel'));
		$grid->addButton(_t('Add channel'), SERIA_HTTP_ROOT.'?route=outboard/channel');
		function display_channel_row($obj)
		{
			$delete = SERIA_Meta::deleteAction('DeleteChannel', $obj);
			if ($delete->success) {
				SERIA_Base::redirectTo($delete->removeFromUrl(SERIA_Url::current())->__toString());
				die();
			}
			ob_start();
			?>
				<tr mnu="{{'Delete'|_t|htmlspecialchars}}:if (confirm(<?php echo htmlspecialchars(SERIA_Lib::toJSON(_t('Deleting a channel will delete the javascript file for this channel. You have to remove the script tag from all sites that use this channel. Are you sure you want to delete this channel?'))); ?>)) top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($delete->__toString())); ?>" onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=outboard/channel/'.$obj->get('id'))); ?>">
					<td><?php echo $obj->get('name'); ?></td>
				</tr>
			<?php
			return ob_get_clean();
		}
		echo $grid->output(array('name'), 'display_channel_row');
	?>
</s:gui>
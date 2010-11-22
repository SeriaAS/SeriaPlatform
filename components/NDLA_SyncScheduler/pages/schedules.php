<s:gui title="{'NDLA Sync Schedules'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('controlpanel/settings/ndlasyncschedule');
	?>
	<h1 class='legend'>{{'NDLA Sync Schedules'|_t}}</h1>
	<?php
		$action = NDLA_SyncLog::stopSyncingAction();
		if ($action !== null) {
			$url = $action->__toString();
			?>
				<p>{{'Sync schedule is enabled. You can stop the schedule from running syncs by clicking the stop button below.'|_t}}</p>
				<input type='button' onclick="top.location.href = {{$url|toJson|htmlspecialchars}};" value="{{'Stop sync schedule'|_t|htmlspecialchars}}">
			<?php
		} else {
			$action = NDLA_SyncLog::startSyncingAction();
			$url = $action->__toString();
			?>
				<p>{{'Sync schedule is disabled. You can resume running the scheduled syncs by clicking the resume button below.'|_t}}</p>
				<input type='button' onclick="top.location.href = {{$url|toJson|htmlspecialchars}};" value="{{'Resume sync schedule'|_t|htmlspecialchars}}">
			<?php
		}
		if ($action->success) {
			SERIA_Base::redirectTo($action->removeFromUrl(SERIA_Url::current())->__toString());
			die();
		}
	?>
	<h2>{{'Weekly schedule'|_t}}</h2>
	<p>{{'Weekly schedule is shown below. Check the hours you want to run sync.'|_t}}</p>
	<?php
		global $weekly;
		$weekly = NDLA_WeeklySyncSchedule::editAction();
		if ($weekly->success) {
			SERIA_Base::redirectTo(SERIA_Url::current());
			die();
		}
		echo $weekly->begin();
		$grid = new SERIA_MetaGrid(NDLA_WeeklySyncSchedule::generate());

		/*
		 * Row callback:
		 */
		function weeklyRow($hour)
		{
			global $weekly;

			ob_start();
			?>
				<tr>
					<th><?php echo ($h = $hour->get('hour')); /* Note assignment! */ ?></th>
					<td><?php echo $weekly->checkbox($h.'atMonday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atTuesday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atWednesday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atThursday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atFriday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atSaturday'); ?></td>
					<td><?php echo $weekly->checkbox($h.'atSunday'); ?></td>
				</tr>
			<?php
			return ob_get_clean();
		}

		echo $grid->output(array(
			'hour' => 100,
			'Monday' => 70,
			'Tuesday' => 70,
			'Wednesday' => 70,
			'Thursday' => 70,
			'Friday' => 70,
			'Saturday' => 70,
			'Sunday' => 70
		), 'weeklyRow');
		echo $weekly->submit();
		echo $weekly->end();
	?>
	<h2>{{'Scheduled syncs'|_t}}</h2>
	<?php
		function syncRow($sync)
		{
			ob_start();
			?>
				<tr onclick="<?php echo htmlspecialchars('top.location.href = '.SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route='.urlencode('ndlasyncschedules/edit/'.$sync->get('id'))).';'); ?>">
					<td><?php echo $sync->get('syncDate'); ?></td>
					<td><?php echo htmlspecialchars($sync->get('description')); ?></td>
				</tr>
			<?php
			return ob_get_clean();
		}
		$grid = new SERIA_MetaGrid(SERIA_Meta::all('NDLA_ScheduledSync'));
		$grid->addButton(_t('Add sync'), SERIA_HTTP_ROOT.'?route=ndlasyncschedules/add');
		$grid->addButton(_t('Sync now'), SERIA_HTTP_ROOT.'?route=ndlasyncschedules/sync');
		$grid->addButton(_t('Show sync log'), SERIA_HTTP_ROOT.'?route=ndlasyncschedules/log');
		echo $grid->output(array(
			'syncDate' => 150,
			'description'
		), 'syncRow');
	?>
</s:gui>
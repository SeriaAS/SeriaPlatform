<s:gui title="{'NDLA Sync Now'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('controlpanel/settings/ndlasyncschedule');
		SERIA_Base::pageRequires('login');
	?>
	<h1 class='legend'>{{'NDLA Sync Now'|_t}}</h1>
	<?php
		$action = NDLA_SyncLog::multimodeSyncAction();
		if ($action->success) {
			?>
			<script type='text/javascript'>
				<!--
					$(document).ready(function () {
						alert(<?php echo SERIA_Lib::toJSON(_t('Started manual sync.')); ?>);
						top.location.href = <?php echo SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'?route=ndlasyncschedules/edit'); ?>
					});
				-->
			</script>
			<?php
		} else if ($action->errors) {
			$errors = $action->errors;
			?>
			<script type='text/javascript'>
				<!--
					$(document).ready(function () {
						alert(<?php echo SERIA_Lib::toJSON(array_shift($errors)); ?>);
					});
				-->
			</script>
			<?php
		}
		$avail = NDLA_SyncLog::loadSync2();
		$keys = NDLA_SyncLog::getMultimodeFields();
		echo $action->begin();
		?>
			<table>
				<tfoot>
					<tr>
						<td><input type='submit' value="{{'Sync now!'|_t|htmlspecialchars}}"></td>
					</tr>
				</tfoot>
				<tbody>
					<?php
						foreach ($keys as $key) {
							?>
								<tr>
									<td>
										<?php
											$partial = '<a href="'.htmlspecialchars(SERIA_HTTP_ROOT.'?route=ndlasyncschedules/partialsync&multimode='.rawurlencode($key)).'">'._t('advanced').'</a>';
											echo $action->field($key);
											echo $action->label($key);
											if (isset($avail[NDLA_SyncLog::getSyncType($key)][2]))
												echo '   '._t('(%PARTIAL%)', array('PARTIAL' => $partial));
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
<h1 class='legend'><?php echo _t("Authentication providers"); ?></h1>
<form method='post'>
	<table class='grid'>
		<thead>
			<tr>
				<th><?php echo htmlspecialchars(_t('System login')); ?></th>
				<th><?php echo htmlspecialchars(_t('Guest login')); ?></th>
				<th><?php echo htmlspecialchars(_t('Automatic login')); ?></th>
				<th><?php echo htmlspecialchars(_t('Provider')); ?></th>
				<th><?php echo htmlspecialchars(_t('Configured')); ?></th>
			</tr>
		</thead>
		<tfoot>
			<?php
				foreach ($creators as &$creator) {
				?>
					<tr>
						<td>
							<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/new.php?id='.urlencode($creator['id'])); ?>"><?php echo htmlspecialchars(_t('Create new')); ?></a>
						</td>
						<td colspan='4'><?php echo htmlspecialchars($creator['caption']); ?></td>
					</tr>
				<?php
				}
			?>
		</tfoot>
		<tbody>
			<?php
				foreach ($providers as &$provider_info) {
					$provider =& $provider_info['provider'];
					$mnu = array();
					if ($provider_info['configure'])
						$mnu[] = _t('Configure').':top.location.href = '.SERIA_Lib::toJSON($provider_info['configure']).';';
					if ($provider_info['delete'])
						$mnu[] = _t('Delete').':top.location.href = '.SERIA_Lib::toJSON($provider_info['delete']).';';
					?>
						<tr mnu="<?php echo htmlspecialchars(implode('|', $mnu)); ?>">
							<td>
								<input type='hidden' name="<?php echo htmlspecialchars($provider->getProviderId().'_present'); ?>" value='1' %XHTML_CLOSE_TAG%>
								<input type='checkbox' name="<?php echo htmlspecialchars($provider->getProviderId().'_system_enable'); ?>" value='1'<?php if ($provider->isEnabled()) echo ' checked=\'checked\''; if (!in_array('system', $provider_info['supports'])) echo ' disabled=\'disabled\''; ?> %XHTML_CLOSE_TAG%>
							</td>
							<td>
								<input type='checkbox' name="<?php echo htmlspecialchars($provider->getProviderId().'_guest_enable'); ?>" value='1'<?php if ($provider->isEnabled(SERIA_IAuthprovider::LOGIN_GUEST)) echo ' checked=\'checked\''; if (!in_array('guest', $provider_info['supports'])) echo ' disabled=\'disabled\''; ?> %XHTML_CLOSE_TAG%>
							</td>
							<td>
								<input type='checkbox' name="<?php echo htmlspecialchars($provider->getProviderId().'_auto_enable'); ?>" value='1'<?php if ($provider->isEnabled(SERIA_IAuthprovider::LOGIN_AUTO)) echo ' checked=\'checked\''; if (!in_array('auto', $provider_info['supports'])) echo ' disabled=\'disabled\''; ?> %XHTML_CLOSE_TAG%>
							</td>
							<td>
								<?php
									if ($provider_info['configure']) {
										?><a href="<?php echo htmlspecialchars($provider_info['configure']); ?>"><?php
									}
								 	echo htmlspecialchars($provider->getName());
								 	if ($provider_info['configure']) {
								 		?></a><?php
								 	}
								 ?>
							</td>
							<td><?php
								if ($provider->isAvailable())
									echo _t('Yes');
								else
									echo _t('No');
							?></td>
						</tr>
					<?php
					unset($provider);
				}
				unset($provider_info);
			?>
		</tbody>
	</table>
	<div>
		<button type='submit'><?php echo htmlspecialchars(_t('Save')); ?></button>
		<button onclick="<?php echo htmlspecialchars('top.location.href = '.SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'/seria/components/Authproviders/').';'); ?>" type='button'><?php echo htmlspecialchars(_t('Cancel')); ?></button>
	</div>
</form>

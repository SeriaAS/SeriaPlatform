<?php

	if (!isset($icons))
		$icons = SERIA_Hooks::dispatch('seria_controlpanel_settings_icons');

	ob_start();
?>
<div>
	<div>
		<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/apps/controlpanel/application_settings/'); ?>"><?php echo htmlspecialchars(_t('Front')); ?></a>
	</div>
	<ul>
		<?php
			foreach ($icons as $icon) {
				if (isset($icon['callback']))
					$url = SERIA_HTTP_ROOT.'/seria/apps/controlpanel/application_settings/hooked.php?icon='.urlencode($quick).'&hash='.urlencode(hash('md4', serialize($icon)));
				else
					$url = $icon['url'];
				?>
					<li>
						<a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($icon['title']); ?></a>
					</li>
				<?php
			}
		?>
	</ul>
</div>
<?php
	$options = ob_get_clean();
	$gui->addBlock(_t("Settings"), $options);
	
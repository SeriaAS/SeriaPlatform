<?php
	require('../common.php');
	SERIA_Base::pageRequires("admin");
	SERIA_Base::viewMode("admin");

	SERIA_Template::cssInclude(SERIA_CACHED_HTTP_ROOT.'/seria/apps/controlpanel/application_settings/style.css');

	$icons = SERIA_Hooks::dispatch('seria_controlpanel_settings_icons');
	require_once(dirname(__FILE__).'/settings_menu.php');

	ob_start();
	?>
	<h1><?php echo htmlspecialchars(_t('Settings')); ?></h1>
	<div id='settings_icons'>
		<?php
			foreach ($icons as $quick => $icon) {
				if (isset($icon['callback']))
					$url = SERIA_HTTP_ROOT.'/seria/apps/controlpanel/application_settings/hooked.php?icon='.urlencode($quick).'&hash='.urlencode(hash('md4', serialize($icon)));
				else
					$url = $icon['url'];
				?>
					<div class='icon'>
						<div>
							<div>
								<a href="<?php echo htmlspecialchars($url); ?>">
									<img src="<?php echo htmlspecialchars($icon['icon']); ?>" alt="<?php echo htmlspecialchars($icon['title']); ?>" style='border: none;' %XHTML_CLOSE_TAG%>
								</a>
							</div>
							<div>
								<a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($icon['title']); ?></a>
							</div>
						</div>
					</div>
				<?php
			}
		?>
	</div>
	<?php
	$gui->contents(ob_get_clean());

	echo $gui->output();
<s:gui title="{'Available languages'|_t|htmlspecialchars}">
	<h1 class='legend'>{{"Enabled languages"|_t}}</h1>
	<?php
		SERIA_Base::pageRequires('admin');
		$this->gui->activeMenuItem('controlpanel/users/languages');
		function output_userlang_row($language)
		{
			$deleteAction = SERIA_Meta::deleteAction('DeleteIso639Locale', $language);
			if ($deleteAction->invoked()) {
				SERIA_Base::redirectTo($deleteAction->removeFromUrl(SERIA_Url::current()));
				die();
			}
			$displayName = $language->get('displayName');
			$language = $language->getIso639Local();
			$languageName = $language->getEnglishName();
			ob_start();
			?>
				<tr>
					<td><?php echo $language->__toString(); ?></td>
					<td><?php echo _t($languageName); ?></td>
					<td><?php echo $displayName; ?></td>
					<td><a href="<?php echo htmlspecialchars($deleteAction->__toString()); ?>">{{"Delete"|_t}}</a></td>
				</tr>
			<?php
			return ob_get_clean();
		}
		$grid = new SERIA_MetaGrid(SERIA_Meta::all('AvailableUserLocale'));
		$grid->addButton(_t('Add language'), AvailableUserLocale::getAddLocaleActionUrl()->__toString());
		echo $grid->output(array(
				_t('Code'),
				_t('Technical name'),
				_t('Display name'),
				_t('Delete')
			),
			'output_userlang_row'
		);
	?>
</s:gui>
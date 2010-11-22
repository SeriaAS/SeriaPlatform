<s:gui title="{'NDLA Sync Log'|_t|htmlspecialchars}">
	<?php
		$this->gui->activeMenuItem('controlpanel/settings/ndlasyncschedule');
	?>
	<h1>{{'NDLA Sync Log'|_t}}</h1>
	<?php
		$grid = new SERIA_MetaGrid(SERIA_Meta::all('NDLA_SyncLog'));
		$grid->addButton(_t('<< Back'), SERIA_HTTP_ROOT.'?route=ndlasyncschedules/edit');
		echo $grid->output(array(
			'executedAt',
			'description'
		));
	?>
</s:gui>
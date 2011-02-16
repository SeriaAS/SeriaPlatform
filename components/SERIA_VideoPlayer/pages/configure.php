<s:gui title="{'Configure Flowplayer'|_t|htmlspecialchars}">
	<h1 class='legend'>{{"Configuration"|_t}}</h1>
	<?php
		SERIA_Base::pageRequires('admin');
		$this->gui->activeMenuItem('controlpanel/other/flowplayer/configure');

		echo "Hello world";
	?>
</s:gui>

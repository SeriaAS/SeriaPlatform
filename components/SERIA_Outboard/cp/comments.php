<?php
	require('common.php');

	$gui->activeMenuItem('controlpanel/outboard/comments');

	$contents = '<h1 class="legend">'._t("Comments").'</h1>';

	$statistics = '<h2>'._t('A few statistics').'</h2>'
		.SERIA_GuiVisualize::integer(_t('Total threads'), 12334)
		.SERIA_GuiVisualize::integer(_t('Number of user accounts'), 232315)
		.SERIA_GuiVisualize::integer(_t('New comments today'), 3002);

	$actions = '<h2>'._t('What do you want to do?').'</h2>
<ul>
	<li><a href="'.htmlspecialchars(SERIA_HTTP_ROOT.'/?route=outboard/comments/moderate').'">'._t('Approve comments').'</a></li>
	<li><a href="comments/word-blacklist.php">'._t('Manage ban-words').'</a></li>
</ul>';
	

	$contents .= "<table><tr><td style='width: 300px'>".$statistics."</td><td>".$actions."</td></tr></table>";

	$gui->contents($contents);

	echo $gui->output();

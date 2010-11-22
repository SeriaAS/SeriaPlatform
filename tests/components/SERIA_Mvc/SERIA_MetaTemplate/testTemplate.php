<?php

if (!isset($this)) {
	/*
	 * Setup area
	 */
	require_once(dirname(__FILE__).'/../../../../main.php');
	$tpl = new SERIA_MetaTemplate();
	die($tpl->parse(__FILE__));
} else {
	/*
	 * Template area
	 */
	?>
		<s:gui title="{'Meta-template test'|_t|htmlspecialchars}">
			<h1 class='legend'><?php echo _t('Testing meta template (this was a direct translate call)'); ?></h1>
			<p>...</p>
		</s:gui>
	<?php
}
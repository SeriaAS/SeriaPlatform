<?php
	$contents = ob_get_clean();

	$title = $optionsMenu->getTitle();
	$optionsMenu->setTitle(false);
	$gui->addBlock($title, $optionsMenu->render());

	$gui->contents($contents);
	$gui->output();
?>
<?php

$currentPoll = $this->getCurrentPoll();

if ($currentPoll === false) {
	echo 'NOT AVAILABLE';
	return;
}

$form = new SERIA_PollWidgetStorageForm($currentPoll);

if (sizeof($_POST))
	$form->receive($_POST);

echo seria_bml('fieldset')->addChildren(array(
	seria_bml('legend')->setText(_t('Poll settings')),
	$form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/genericInlineForm.php')
))->output();

?>
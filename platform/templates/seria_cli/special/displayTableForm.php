<?php

	require_once(dirname(__FILE__).'/displayTableForm/displayTableForm.php'); /* common code with the inline form */

	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/templates/seria/special/displayTableForm/displayTableForm.css');

	if (!isset($action))
		$action = null;
	echo $form->begin('', 'post', $action);

	$formSpec = $form->_getFormSpec();

	seria_special_templates_renderDisplayTableForm($form, $formSpec);

	echo $form->submit();

	echo $form->end();

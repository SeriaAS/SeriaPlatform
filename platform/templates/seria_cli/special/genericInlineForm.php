<?php

	require_once(dirname(__FILE__).'/genericForm/genericForm.php'); /* common code with the inline form */

	$formSpec = $form->_getFormSpec();

	$c .= seria_renderGenericForm($form, $formSpec);

	echo $c;

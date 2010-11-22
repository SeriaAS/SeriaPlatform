<?php

	require_once(dirname(__FILE__).'/genericForm/genericForm.php'); /* common code with the inline form */

	if (!isset($action))
		$action = null;
	$c = $form->begin('', 'post', $action);

	$formSpec = $form->_getFormSpec();

	$c .= seria_renderGenericForm($form, $formSpec);

	$c .= $form->submit();

	$buttons = $form->_getFormButtons();

	if(sizeof($buttons)>0)
	{
		foreach($buttons as $button)
		{
			$c .= '<input type="button" value="'.$button['caption'].'" onclick="'.htmlspecialchars($button['onclick']).'"> ';
		}
	}

	$c .= $form->end();

	echo $c;


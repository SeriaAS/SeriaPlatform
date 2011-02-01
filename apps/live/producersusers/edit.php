<?php

	require('../common.php');

	$gui->activeMenuItem('serialive/producersusers');

	if(!empty($_GET["id"]))
	{
		$gui->title(_t("Edit live user"));
		$contents = '<h1 class="legend">'._t("Edit live user").'</h1>';
		$produceruser = SERIA_Meta::load('ProducerUser', intval($_GET["id"]));
	}
	else
	{
		$gui->title(_t("Add live user"));
		$contents = '<h1 class="legend">'._t("Add live user").'</h1>';

		$produceruser = new ProducerUser();

	}
	$form = $produceruser->editAction();
	if($form->success)
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/apps/live/producersusers");
	}

	$fields = array(
		'user',
		'producer'
	);
	$formArray = array();

	foreach($fields as $name)
		$formArray[$name] = array(
			$form->label($name),
			$form->field($name)
		);

	$formArray = generateRowsForTwoColumnsForm($formArray, array_keys($formArray));

	$left = showTableRowsForm($formArray, array(
		'user',
	));

	$right = showTableRowsForm($formArray, array(
		'producer'
	));

	$contents .= $form->begin().'<table><summary>'._t("Producer form").'</summary><tbody>
		<tr>
			<td>'.$left.'</td><td>'.$right.'</td>
		</tr>
	</tbody><tfoot>
		<tr>
			<td colspan="2">'.$form->submit(_t("Save")).'</td>
		</tr>
	</tfoot></table>';


	echo $gui->contents($contents)->output();

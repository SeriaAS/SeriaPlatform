<?php

	require('../common.php');

	$gui->activeMenuItem('serialive/producers');

	if(!empty($_GET["id"]))
	{
		$gui->title(_t("Edit producer"));
		$contents = '<h1 class="legend">'._t("Edit producer").'</h1>';
		$producer = SERIA_Meta::load('Producer', intval($_GET["id"]));
	}
	else
	{
		$gui->title(_t("Add producer"));
		$contents = '<h1 class="legend">'._t("Add producer").'</h1>';

		$producer = new Producer();

	}
/*
	* Producer
	* name String
	* orgNumber int
	* billingName ( recipient )
	* billingAddress
	* billingZip
	* billingPhone
	* billingNote
	*
	* currentBlockPrice
	* currentBlockSize
*/
	$form = $producer->editAction();
	if($form->success)
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/apps/live/producers");
	}

	$fields = array(
		'name',
		'orgNumber',
		'billingName',
		'billingAddress',
		'billingZip',
		'billingPhone',
		'billingNote',
		'currentBlockPrice',
		'currentBlockSize',
	);
	$formArray = array();

	foreach($fields as $name)
		$formArray[$name] = array(
			$form->label($name),
			$form->field($name)
		);

	$formArray = generateRowsForTwoColumnsForm($formArray, array_keys($formArray));

	$left = showTableRowsForm($formArray, array(
		'name',
		'orgNumber',
		'currentBlockPrice',
		'currentBlockSize'
	));

	$right = showTableRowsForm($formArray, array(
		'billingName',
		'billingAddress',
		'billingZip',
		'billingPhone',
		'billingNote',
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

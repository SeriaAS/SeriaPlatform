<?php

	require('../common.php');

	$gui->activeMenuItem('serialive/presentationcompanies/edit');

	if(!empty($_GET["id"]))
	{
		$gui->title(_t("Edit company"));
		$contents = '<h1 class="legend">'._t("Edit company").'</h1>';
		$company = SERIA_Meta::load('PresentationCompany', intval($_GET["id"]));
	}
	else
	{
		$gui->title(_t("Add company"));
		$contents = '<h1 class="legend">'._t("Add company").'</h1>';

		$company = new PresentationCompany();

	}

	$form = $company->editAction();
	if($form->success)
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/apps/live/presentationcompanies");
	}

	$fields = array(
		'name',
		'orgNumber',
		'contactName',
		'contactNote',
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
	));

	$right = showTableRowsForm($formArray, array(
		'contactName',
		'contactNote',
	));

	$contents .= $form->begin().'<table><summary>'._t("Company form").'</summary><tbody>
		<tr>
			<td>'.$left.'</td><td>'.$right.'</td>
		</tr>
	</tbody><tfoot>
		<tr>
			<td colspan="2">'.$form->submit(_t("Save")).'</td>
		</tr>
	</tfoot></table>';


	echo $gui->contents($contents)->output();

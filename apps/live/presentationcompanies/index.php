<?php
	require('../common.php');

	$gui->activeMenuItem('serialive/presentationcompanies');
	$gui->title(_t("Companies"));

	$contents = '<h1 class="legend">'._t("Companies").'</h1>';

	$contents .= '<p>'._t("From here you can manage all your companies, a presentation has to be assosciated with a customer").'</p>';

	$customers = SERIA_Meta::all('PresentationCompany')->order('createdDate DESC');

	$contents .= $customers->grid()->output(array('name', 'orgNumber',''), 'customerGrid');
	echo $gui->contents($contents)->output();

	function customerGrid($customer)
	{
		return '<tr onclick="location.href=\'edit.php?id='.$customer->get("id").'\';">
				<td>'.$customer->get('name').'</td>
				<td>'.$customer->get('orgNumber').'</td>
				<td>&nbsp;</td>
		</tr>';
	}

<?php
	require('../common.php');

	$gui->activeMenuItem('serialive/producers');
	$gui->title(_t("Producers"));

	$contents = '<h1 class="legend">'._t("Producers").'</h1>';

	$contents .= '<p>'._t("From here you can manage all the producers entered into your system, a presentation has to be assosciated with a producer").'</p>';

	$producers = SERIA_Meta::all('Producer')->order('createdDate DESC');
	$contents .= $producers->grid()->output(array('name', 'orgNumber', 'currentBlockSize','currentBlockPrice', ''), 'producerGrid');

	echo $gui->contents($contents)->output();

	function producerGrid($producer)
	{
		return '<tr onclick="location.href=\'edit.php?id='.$producer->get("id").'\';">
				<td>'.$producer->get('name').'</td>
				<td>'.$producer->get('orgNumber').'</td>
				<td>'.$producer->get('currentBlockSize').'</td>
				<td>'.$producer->get('currentBlockPrice').'</td>
				<td>&nbsp;</td>
		</tr>';
	}

<?php
	require('../common.php');

	$gui->activeMenuItem('serialive/producersusers');
	$gui->title(_t("Live users"));

	$contents = '<h1 class="legend">'._t("Live users").'</h1>';

	$contents .= '<p>'._t("From here you can manage the assosciation between a producer and a user").'</p>';

	$producerUsers = SERIA_Meta::all('ProducerUser')->order('createdDate DESC');
	$contents .= $producerUsers->grid()->output(array('Name', 'Producer', ''), 'producerUserGrid');

	echo $gui->contents($contents)->output();

	function producerUserGrid($producerUser)
	{
		return '<tr onclick="location.href=\'edit.php?id='.$producerUser->get("id").'\';">
				<td>'.$producerUser->get('user').'</td>
				<td>'.$producerUser->get('producer').'</td>
				<td>&nbsp;</td>
		</tr>';
	}

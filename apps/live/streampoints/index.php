<?php
	require('../common.php');

	$gui->activeMenuItem('serialive/streampoints');
	$gui->title(_t("Streampoints"));

	$contents = '<h1 class="legend">'._t("Streampoints").'</h1>';

	$contents .= '<p>'._t("From here you can manage all your streampoints, a presentation automatically chooses an available streampoint").'</p>';

	$streampoints = SERIA_Meta::all('StreamPoint')->order('createdDate DESC');
	$contents .= $streampoints->grid()->output(array('streamName', 'publishName','reserved','inUse',''), 'streampointGrid');

	echo $gui->contents($contents)->output();

	function streampointsGrid($streampoint)
	{
		return '<tr onclick="location.href=\'edit.php?id='.$streampoint->get("id").'\';">
				<td>'.$streampoint->get('streamName').'</td>
				<td>'.$streampoint->get('publishName').'</td>
				<td>'.$streampoint->get('reserved').'</td>
				<td>'.$streampoint->get('inUse').'</td>
				<td>&nbsp;</td>
		</tr>';
	}

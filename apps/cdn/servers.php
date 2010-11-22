<?php
	require(dirname(__FILE__).'/common.php');
	$gui->activeMenuItem('cdn/servers/list');
	$gui->title(_t('Servers'));

	if(SERIA_Base::isAdministrator())
		$c = '<h1 class="legend">'._t('Configured servers').'</h1>';
	else
		$c = '<h1 class="legend">'._t('My configured servers').'</h1>';

	$grid = SERIA_Fluent::all('SERIA_CDNServer')->grid();
//	$grid->addButton(_t('Add server'), './server.php');

	function gridTemplate($object)
	{
		$name = 'Not available';
		try {
			$name = (String) SERIA_Fluent::createObject('SERIA_User', $object->get('ownerId'));
		}
		catch (Exception $e) {}
		return '<tr onclick="location.href=\'server.php?id='.$object->getKey().'\';"><td><a href="server.php?id='.$object->getKey().'">'.$object->get('name').'</a></td><td>'.$object->get('ip').'</td><td>'.$name.'</td></tr>';
		return '<tr onclick="location.href=\'server.php?id='.$object->getKey().'\';"><td><a href="server.php?id='.$object->getKey().'">'.$object->get('name').'</a></td><td>'.$object->get('ip').'</td><td>'.SERIA_Fluent::createObject('SERIA_User', $object->get('ownerId')).'</td></tr>';
	}
	
	$c .= $grid->output(array('name' => 200, 'ip', 'ownerId'), 'gridTemplate');

	$gui->contents($c);
	$gui->output();

<?php
	require(dirname(__FILE__).'/common.php');
	$gui->activeMenuItem('cdn/servers');

	if(isset($_GET['id']))
	{
		$hostname = SERIA_Fluent::createObject('SERIA_CDNHostname', $_GET['id']);
		$gui->title(_t('Editing hostname: %hostname%', array('hostname' => $hostname->get('hostname'))));
		$server = $hostname->getServer();
	}
	else if(isset($_GET['serverId']))
	{
		$server = SERIA_Fluent::createObject('SERIA_CDNServer', $_GET['serverId']);
		$gui->title(_t('Create a new hostname'));
		$hostname = SERIA_Fluent::createObject('SERIA_CDNHostname');
	}


	SERIA_QuickFormFactory::createFormClass('SERIA_CDNHostname', 'SERIA_CDNHostnameForm', _t('Edit hostname'), array(
		'createdBy' => 'createdBy',
		'createdDate' => 'createdDate',
		'modifiedBy' => 'modifiedBy',
		'modifiedDate' => 'modifiedDate',
		'hostname' => array(),
	));

	$form = new SERIA_CDNHostnameForm($hostname);

        if($form->receive($_POST, array('serverId' => $server->getKey())))
        {
                header("Location: ".SERIA_HTTP_ROOT."/seria/apps/cdn/server.php?id=".$server->getKey());
                die();
        }
	
	if($hostname->getKey())
		$c = '<h1 class="legend">'._t('Editing hostname: %hostname%', array('hostname' => $hostname->get('hostname'))).'</h1>';
	else
		$c = '<h1 class="legend">'._t('Adding hostname').'</h1>';

        $c .= $form->output();

	$gui->contents($c);
	$gui->output();

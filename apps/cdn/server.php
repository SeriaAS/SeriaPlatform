<?php
	require(dirname(__FILE__).'/common.php');
	$gui->activeMenuItem('cdn/servers/edit');

	$gui->title(_t('Server'));

	SERIA_QuickFormFactory::createFormClass('SERIA_CDNServer', 'SERIA_CDNServerForm', _t('Edit server'), array(
		'ownerId' => 'createdBy',
		'createdBy' => 'createdBy',
		'createdDate' => 'createdDate',
		'modifiedBy' => 'modifiedBy',
		'modifiedDate' => 'modifiedDate',
		'name' => array(),
		'ip' => array(
			'fieldtype' => 'text',
		),
	));
	if(isset($_GET['id']))
	{
		$server = SERIA_Fluent::createObject('SERIA_CDNServer', intval($_GET['id']));

		if(isset($_GET['action']))
		{
			if($_GET['action']=='delete')
			{
				if($server->delete())
				{
					header('Location: '.$seriaCDN->getHttpPath().'/servers.php');
					die();
				}
			}
			else if($_GET['action']=='deleteHostname' && isset($_GET['hostnameId']))
			{
				$hostname = SERIA_Fluent::createObject('SERIA_CDNHostname', intval($_GET['hostnameId']));
				if($hostname->delete())
				{
					header('Location: '.$seriaCDN->getHttpPath().'/server.php?id='.$server->getKey());
					die();
				}
			}
		}
	}
	else
	{
		$server = SERIA_Fluent::createObject('SERIA_CDNServer');
	}

	$form = new SERIA_CDNServerForm($server);
	if($server->isDeletable())
		$form->addButton(_t("Delete"), 'if(confirm("'._t('Are you sure? Mirroring will stop working shortly after you confirm!').'")) location.href="server.php?id='.$server->getKey().'&action=delete"');


        if($form->receive($_POST))
        {
		header("Location: ".SERIA_HTTP_ROOT."/seria/apps/cdn/servers.php");
		die();
        }
	
	if($server->getKey())
		$c = '<h1 class="legend">'._t('Editing server: %server%', array('server' => $server->get('name'))).'</h1>';
	else
		$c = '<h1 class="legend">'._t('Creating a new server').'</h1>';
	$c .= $form->output();

	$grid = $server->getHostnames()->grid();
	if($server->getKey())
		$grid->addButton(_('Add hostname'), './hostname.php?serverId='.$server->getKey());

	$c .= $grid->output(array('hostname' => 250,'createdDate', ""),'<tr><td>%hostname%</td><td>%createdDate%</td><td><a href="#" onclick="if(confirm(\''._t('Are you sure? Mirroring for this host wil stop working shortly after you confirm!').'\')) location.href=\'server.php?id='.$server->getKey().'&action=deleteHostname&hostnameId=%id%\'">'._t('Delete').'</a></td></tr>');


	$gui->contents($c);
	$gui->output();

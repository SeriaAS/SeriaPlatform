<?php

	$baseparams = $_GET;
	unset($baseparams['id']);
	if (!$baseparams)
		$baseparams['rpcsys'] = 'Whiskey'; /* Dummy field */
	$basepath = explode('?', $_SERVER['REQUEST_URI']);
	$basepath = $basepath[0];
	$baseurl = SERIA_HTTP_ROOT.$basepath.'?'.http_build_query($baseparams);

	$record = SERIA_Fluent::all('SERIA_RPCRemoteService')->where('service = :id', array(':id' => $_GET['id']))->current();

	$app = SERIA_Applications::getApplication('seria_controlpanel');
	$gui->addMenuItem('controlpanel/rpc/services/edit', _t('Edit RPC service: %NAME%', array('NAME' => $record->get('service'))), _t('Edit hostname and keys to connect to remote'), $app->getHttpPath().'/rpc/services.php?id='.urlencode($_GET['id']), $app->getHttpPath().'/icon.png');
	$gui->activeMenuItem('controlpanel/rpc/services/edit');

	SERIA_QuickFormFactory::createFormClass('SERIA_RPCRemoteService', 'SERIA_RPCRemoteServiceForm', _t('Edit rpc service'), array(
		'service' => array(
			'caption' => _t('Service name:')
		),
		'hostname' => array(
			'caption' => _t('Hostname:')
		),
		'client_id' => array(
			'caption' => _t('Client id:')
		),
		'client_key' => array(
			'caption' => _t('Client key:')
		)
	));

	$form = new SERIA_RPCRemoteServiceForm($record);

	if (sizeof($_POST)) {
		if ($form->receive($_POST)) {
			header('Location: '.$baseurl);
			die();
		}
	}

	echo $form->output(dirname(__FILE__).'/service_form.php');

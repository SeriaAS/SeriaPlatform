<?php
	if ($_GET['id'] != 'create') {
		$record = SERIA_Fluent::all('SERIA_RPCClientKey')->where('client_id = :id', array(':id' => $_GET['id']))->current();
		$gui->activeMenuItem('controlpanel/settings/rpc/accounts/new');
	} else {
		$gui->activeMenuItem('controlpanel/settings/rpc/accounts/new');
		$app = SERIA_Applications::getApplication('seria_controlpanel');
		$record = new SERIA_RPCClientKey();
	}

	$form = new SERIA_RPCClientKeyForm($record);

	if (sizeof($_POST)) {
		if (isset($_POST['delete']) && $_POST['delete'] == 1) {
			$record->delete();
			header('Location: '.$baseurl);
			die();
		}
		if ($form->receive($_POST)) {
			header('Location: '.$baseurl);
			die();
		}
	} else {
		if (!isset($_GET['id']))
			$record->set('client_key', sha1(mt_rand().mt_rand().mt_rand().mt_rand().mt_rand().mt_rand().mt_rand().mt_rand()));
	}

	echo $form->output(dirname(__FILE__).'/client_form.php');

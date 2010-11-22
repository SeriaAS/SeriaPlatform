<?php

	require_once(dirname(__FILE__)."/../../main.php");

	SERIA_Base::pageRequires("javascript");
	SERIA_Base::pageRequires("login");

	SERIA_Base::addFramework('bml');

	$gui = new SERIA_Gui(_t("Change password"));

	$gui->exitButton("Back", "history.go(-1)");

	$user = SERIA_Base::user();

	if (isset($_GET['passchanged'])) {
		if (isset($_GET['continue']))
			header('Location: '.$_GET['continue']);
		$gui->contents(seria_bml('div')->addChildren(array(
			seria_bml('h1')->setText(_t('Successfully changed')),
			seria_bml('p')->setText(_t('Your password was successfully changed.'))
		))->output());
		echo $gui->output();
		die();
	}

	$error = false;
	if (isset($_POST['newPassword']) && isset($_POST['confirmPassword']) && isset($_POST['continue'])) {
		if ($_POST['newPassword'] == $_POST['confirmPassword']) {
			if (SERIA_User::isInvalid('password', $_POST['newPassword']) === false) {
				if (!$user->get('password_change_required')) {
					if (!isset($_POST['password']) ||
					    $_POST['password'] != $user->get('password'))
						$error = _t('The password is incorrect. Please try again.');
				}
				if ($error === false) {
					$user->set('password', $_POST['newPassword']);
					$user->set('password_change_required', false);
					$user->saveEditByUser();
					header('Location: ?passchanged=yes&continue='.$_POST['continue']);
					die();
				}
			} else
				$error = _t('The password is invalid. Please type a password with at least six characters and with both letters and numbers.');
		} else
			$error = _t('Passwords do not match. Please retype the password.');
	}

	$heading = _t('Change password');
	if (isset($_GET['required']))
		$description = _t('You are required to change your password before completing the login process. Please type your new password twice below.');
	else
		$description = _t('You are about to change your password. Please type your new password twice below.');

	/*$pagedisp = new SERIA_BMLElement('div');
	$headobj = new SERIA_BMLElement('h1');
	$headobj->setText($heading);
	$descobj = new SERIA_BMLElement('p');
	$descobj->setText($description);*/

	$gui->contents(seria_bml('form', array('method' => 'POST'))->addChildren(array(
		seria_bml('input', array('type' => 'hidden', 'name' => 'continue', 'value' => (isset($_GET['continue']) ? $_GET['continue'] : ''))),
		seria_bml('h1')->setText($heading),
		seria_bml('p')->setText($description),
		seria_bml('div', array('class' => 'error'))->setText($error !== false ? $error : ''),
		seria_divbuilder(
			array(120, 200),
			array(
				(
				$user->get('password_change_required') ?
					false :
					array(
						seria_bml('label', array('for' => 'password'))->setText(_t('Old password:')),
						seria_bml('input', array('type' => 'password', 'name' => 'password', 'value' => ''))->setWidth(200)
					)
				),
				array(
					seria_bml('label', array('for' => 'newPassword'))->setText(_t('New password:')),
					seria_bml('input', array('type' => 'password', 'name' => 'newPassword', 'value' => ''))->setWidth(200)
				),
				array(
					seria_bml('label', array('for' => 'confirmPassword'))->setText(_t('Confirm password:')),
					seria_bml('input', array('type' => 'password', 'name' => 'confirmPassword', 'value' => ''))->setWidth(200)
				),
			)
		),
		seria_bml('button', array('type' => 'submit'))->setText(_t('OK'))
	))->output());
	echo $gui->output();

?>

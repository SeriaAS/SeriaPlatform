<?php
	require('main.php');
	$form = SERIA_User::loginAction();
	if($form->success)
	{ // a user was logged in
		header("Location: http://some/url");
		die();		
	}

	echo $form->begin().$form->label('username').$form->field('username').$form->label('password').$form->field('password').$form->end();

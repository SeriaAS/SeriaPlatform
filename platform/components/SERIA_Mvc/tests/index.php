<?php
	require("../../../main.php");
	SERIA_Base::addClassPath(dirname(__FILE__).'/*.class.php');

	$form = CD::editAction();

	if($form->success)
	{
		die("SUCCESS");
	}
	else if($form->errors)
	{
var_dump($form->errors);
	}

	$gui = new SERIA_Gui('Editing');
	$contents = $form->begin()."

".$form->label('title')."
".$form->field("title")."
".$form->label('gender')."
".$form->field('gender')."
".$form->submit()."
".$form->end();
	$gui->contents($contents);
	echo $gui->output();

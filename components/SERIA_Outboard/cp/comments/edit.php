<?php
	require('../common.php');
	$gui->activeMenuItem('controlpanel/outboard/comments/edit');
	if(isset($_GET['id']))
		$comment = SERIA_Meta::load('SERIA_Comment', $_GET['id']);
	else
		$comment = new SERIA_Comment();

	$form = $comment->editAction();

	$contents = '<h1 class="legend">';

	if($comment->get('id'))
		$contents .= $comment->get('title').'</h1>';
	else
		$contents .= _t("Adding a comment").'</h1>';

	$gui->contents($contents);


	echo $gui->output();

<?php
	require('common.php');

	if(isset($_GET['action']) && $_GET['action']=='edit')
	{
		if(isset($_GET['id']))
			$entity = SERIA_Meta::load('SERIA_OutboardTestEntity', $_GET['id']);
		else
			$entity = new SERIA_OutboardTestEntity();

		$form = $entity->editAction();
		if($form->success)
		{
			header('Location: '.SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/test.php');
			die();
		}

		$contents = '<h1 class="legend">Add a new entity</h1>';
		$contents .= 
			$form->begin().
			'<table class="form"><tbody><tr><th style="width: 130px;">'.$form->label('name').'</th><td>'.$form->field('name').'</td></tr></tbody></table>'.
			SERIA_GuiVisualize::toolbar(array($form->submit('Save'), '<a href="test.php">Back</a>')).$form->end();

		// Comments
		$comments = SERIA_Comment::getComments($entity);
		foreach($comments as $comment)
		{
			$contents .= "<div style='border:1px solid #eee; padding: 8px;'>";
			$contents .= "her";
			$contents .= "</div>";
		}

		$cf = SERIA_Comment::createAction($entity);
		if($cf->success)
		{
			header('Location: '.SERIA_Url::current());
			die();
		}
		else if($cf->error)
			$contents .= "ERROR: ".$cf->error."<br><br>";
		else if($cf->errors)
			var_dump($cf->errors);
		$contents .= "<h2>Add comment</h2>";
		$contents .= $cf->begin().
			$cf->label('title').$cf->field('title')."<br>".
			$cf->label('message').$cf->field('message')."<br>".
			$cf->submit('Add comment').$cf->end();
	}
	else
	{
		$contents = '<h1 class="legend">Outboard testing: Entities</h1>';
		$contents .= '<p>A list of entities that you can add comments and other user actions to.</p>';
		$entities = SERIA_Meta::all('SERIA_OutboardTestEntity');

		$contents .= $entities->grid()->output(array('name', 'Action'), '<tr onclick="location.href=\'test.php?action=edit&id=%id%\';"><td>%name%</td><td><a href="delete.php?id=%id%">Slett</a></td></tr>');

		$contents .= SERIA_GuiVisualize::toolbar(array(
			'<a href="test.php?action=edit">Add</a>',
		));
	}
	$gui->contents($contents);

	echo $gui->output();

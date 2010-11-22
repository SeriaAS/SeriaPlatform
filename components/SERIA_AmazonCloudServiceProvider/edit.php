<?php
	require('common.php');
	$gui->activeMenuItem('controlpanel/settings/services/cloud/amazon/edit');

	if(isset($_GET['id']))
		$provider = SERIA_AmazonCloudServiceProvider::getProviders()->where('id=:id', $_GET)->current();
	else
		$provider = new SERIA_AmazonCloudServiceProvider();

	$form = $provider->editForm('editAws');

	if($form->success)
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/components/SERIA_AmazonCloudServiceProvider/");
		die();
	}
	$contents = "<h1 class='legend'>".(SERIA_Meta::isNew($provider)?_t("Add an Amazon Web Services account"):_t("Editing %name%", $provider))."</h1>";

	$form->errorTemplate('<div class="error"> %MESSAGE%</div>');
	$contents .= $form->begin()."<table class='form'>
		<tr><th style='width: 210px'>".$form->label('name')."</th><td>".$form->field('name', array('style'=>'width:300px;font-weight:bold;font-size: 1.2em;height:1.2em;'))."</td><td>".$form->error('name')."</td></tr>
		<tr><th>".$form->label('aws_key')."</th><td>".$form->field("aws_key", array('style'=>'width:300px'))."</td><td>".$form->error("aws_key")."</td></tr>
		<tr><th>".$form->label("aws_secret_key")."</th><td>".$form->field("aws_secret_key", array('style'=>'width:300px'))."</td><td>".$form->error("aws_secret_key")."</td></tr>
		<tr><th>".$form->label("aws_account_id")."</th><td>".$form->field("aws_account_id", array('style'=>'width:300px'))."</td><td>".$form->error("aws_account_id")."</td></tr>
		<tr><th>".$form->label("aws_assoc_id")."</th><td>".$form->field("aws_assoc_id", array('style'=>'width:300px'))."</td><td>".$form->error("aws_assoc_id")."</td></tr>
		<tr><th>".$form->label("aws_api_url")."</th><td>".$form->field("aws_api_url", array('style'=>'width:300px'))."</td><td>".$form->error("aws_api_url")."</td></tr>
	</table>".SERIA_GuiVisualize::toolbar(array($form->submit(_t('Save')), '<a href="./">'._t("Abort").'</a>')).$form->end();

	$gui->contents($contents)->output();

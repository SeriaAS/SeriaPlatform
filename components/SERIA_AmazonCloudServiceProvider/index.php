<?php
	require('common.php');
	$gui->activeMenuItem('controlpanel/settings/services/cloud/amazon');

	$contents = '<h1 class="legend">'._t("Amazon Web Service accounts").'</h1>';
	$contents .= '<p>'._t("Manage your Amazon Web Service accounts here.").'</p>';

	$providers = SERIA_AmazonCloudServiceProvider::getProviders();
	$grid = $providers->grid();
	$grid->addButton(_t("Add account"), SERIA_HTTP_ROOT.'/seria/components/SERIA_AmazonCloudServiceProvider/edit.php');
	$contents .= $grid->output(array('name', 'aws_key'), '<tr onclick="location.href=\'edit.php?id=%id%\';"><td>%name%</td><td>%aws_key%</td></tr>', 10);


	$gui->contents($contents)->output();

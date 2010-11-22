<?php
/**
*	Lists all comments for an object. For metaObjects add metaObject={ SERIA_Meta::getMetaReference($object) } to the URL.
*/
	require('../common.php');

	if(!isset($_GET['metaObject']))
	{
		SERIA_Base::displayErrorPage(400, _t("Expected query parameter not found"), _t("I expect a request parameter to be provided that identifies the resource that you wish to view comments for."));
	}

	$gui->title(_t('Listing comments'));


	echo $gui->output();

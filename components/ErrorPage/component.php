<?php

function ErrorPage_error($httpErrorCode, $title, $message, $die, $extraHTML)
{
	if ($die)
		header('HTTP/1.1 '.$httpErrorCode.' '.$title);
	$template = new SERIA_MetaTemplate();
	$template->addVariable('httpErrorCode', $httpErrorCode);
	$template->addVariable('title', $title);
	$template->addVariable('message', $message);
	$template->addVariable('die', $die);
	$template->addVariable('extraHTML', $extraHTML);
	echo $template->parse(ERROR_PAGE_META_TEMPLATE);
	if ($die)
		die();
}

function ErrorPage_init()
{
	if (defined('ERROR_PAGE_META_TEMPLATE') && file_exists(ERROR_PAGE_META_TEMPLATE))
		SERIA_Hooks::listen(SERIA_Base::DISPLAY_ERROR_HOOK, 'ErrorPage_error');
}
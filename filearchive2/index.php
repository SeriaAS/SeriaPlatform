<?php

require_once(dirname(__FILE__).'/../main.php');

SERIA_Template::headPrepend('forceie7mode', '<meta http-equiv="X-UA-Compatible" content="IE=7"'.(SERIA_XHTML ? ' /' : '').'>');

require_once(dirname(__FILE__).'/../filearchive/common.php'); /* For icons */

SERIA_Base::pageRequires("login");
SERIA_Base::pageRequires("javascript");
SERIA_Base::viewMode("admin");

SERIA_ScriptLoader::loadScript('SERIA-Platform-Private');
SERIA_ScriptLoader::loadScript('SERIA-Platform-Common');


SERIA_Template::title(_t('File archive'));


$categories = array(
	'name' => 'ROOT',
	'trace' => array()
);
$root = SERIA_FileDirectory::getRoot();
$stack = array();
$children = $root->getChildren();
while ($children)
	array_push($stack, array(&$categories, array_pop($children)));
while ($stack) {
	$q =& array_pop($stack);
	$attachPoint =& $q[0];
	$item =& $q[1];
	unset($q);
	if (!isset($attachPoint['categories']))
		$attachPoint['categories'] = array();
	$trace = $attachPoint['trace'];
	$trace[] = count($attachPoint['categories']);
	$theItem = array(
		'id' => $item->get('id'),
		'name' => $item->get('name'),
		'selected' => false,
		'trace' => $trace
	);
	$attachPoint['categories'][] =& $theItem;
	$children = $item->getChildren();
	while ($children)
		array_push($stack, array(&$theItem, array_pop($children)));
	unset($theItem);
	unset($item);
	unset($attachPoint);
}

/*
 * The fileicons template will do a load by json.
 */
$pagingInfo = array(
	'page' => 0
);

$contents = SERIA_Template::parseToString(dirname(__FILE__).'/template/main.php', array(
	'categories' => $categories,
	'files' => array(),
	'pagingInfo' => $pagingInfo
));

SERIA_Template::parse(dirname(__FILE__).'/template/html.php', array(
	'contents' => $contents
));

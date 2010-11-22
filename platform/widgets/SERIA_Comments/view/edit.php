<?php

if (isset($_POST['widget']) && isset($_POST['id'])) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	SERIA_Template::disable();
	try {
		if (!SERIA_Base::isAdministrator())
			throw new Exception('Administrator privileges are required for delete. Maybe your login has expired?');
		$widget = SERIA_Widget::createObject($_POST['widget']);
		$comment = $widget->getComment($_POST['id']);
		$widget->deleteComment($comment);
		SERIA_Lib::publishJSON(array());
	} catch (Exception $e) {
		SERIA_Lib::publishJSON(array('error' => $e->getMessage()));
	}
	die();
}

if (isset($_GET['widget']) && isset($_GET['edit'])) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	SERIA_Base::pageRequires('login');
	$gui = new SERIA_Gui('Edit comment');
	$widget = SERIA_Widget::createObject($_GET['widget']);
	$gui->contents($widget->output('popup'));
	echo $gui->output(true);
	die();
}

if (SERIA_Base::isAdministrator()) {
	SERIA_Base::addFramework('bml');
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_Comments/view/style.css');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_Comments/view/active.js');

	require_once(dirname(__FILE__).'/inc.php');

	$contents = getCommentsDisplay($this, 'admin');

	echo seria_bml('div', array('class' => 'SERIA_Comments'))->addChildren($contents)->output();
}

?>

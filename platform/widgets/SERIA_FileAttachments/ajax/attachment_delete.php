<?php 

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Base::pageRequires('admin');

SERIA_Base::viewMode('admin');

SERIA_Template::disable();

try {
	$widget = SERIA_Widget::createObject($_POST['widget_id']);
	$article = $widget->getNamedObject();
	if(SERIA_Base::user()===false || ($article->get("author_id") !== SERIA_Base::user()->get("id") && !SERIA_Base::hasRight("edit_others_articles")))
		throw new Exception('Access denied.');
	$widget->removeAttachmentById($_POST['attachment_id']);
	$values = array(
	);
} catch (Exception $e) {
	$values = array(
		'error' => $e->getMessage()
	);
	if (SERIA_DEBUG) {
		$values['trace'] = $e->getTraceAsString();
	}
}

SERIA_Lib::publishJSON($values);

?>
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
	$fileobj = SERIA_File::createObject($_POST['file_id']);
	$widget->addAttachment($fileobj);
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
<?php

if (isset($_GET['widgetAddComment'])) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	$gui = new SERIA_Gui('Edit comment');
	$widget = SERIA_Widget::createObject($_GET['widgetAddComment']);
	$gui->contents($widget->output('popup'));
	echo $gui->output(true);
	die();
}

SERIA_Base::addFramework('bml');
SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_Comments/view/style.css');
SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_Comments/view/active.js');

require_once(dirname(__FILE__).'/inc.php');

$contents = getCommentsDisplay($this);

if ($this->getCommentFormEnable()) {
	$contents[] = seria_bml('div', array('class' => 'SERIA_Comments_bar'))->addChild(
		seria_bml('button', array('type' => 'button', 'onclick' => 'SERIA_Comments.addInline('.$this->getId().'); return false;'))->setText(_t('Add comment'))
	);

	$addcom = seria_bml('div', array('id' => 'SERIA_Comments_addcom'))->addChild(SERIA_Widget::getWidget('SERIA_Comments', $this->getNamedObject())->output('form'));
	if (!isset($_POST['showAddcom']))
		$addcom->setStyle('display', 'none');
	$contents[] = $addcom;
}

echo seria_bml('div', array('class' => 'SERIA_Comments'))->addChildren($contents)->output();
?>

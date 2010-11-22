<?php 

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Base::pageRequires('admin');

SERIA_Base::viewMode('admin');

SERIA_Template::disable();
SERIA_Base::preventCaching();

ob_start();
try {
	$widget = SERIA_Widget::createObject($_GET['widget_id']);
	$article = $widget->getNamedObject();
	if(SERIA_Base::user()===false || ($article->get("author_id") !== SERIA_Base::user()->get("id") && !SERIA_Base::hasRight("edit_others_articles")))
		throw new Exception('Access denied.');
	$attachments = $widget->getAttachments();
	foreach ($attachments as $attachment_id => $attachment) {
		$file_article = $attachment->get('file_article_id');
		if ($file_article)
			$file_article = SERIA_Article::createObjectFromId($file_article);
		if ($file_article)
			$name = $file_article->get('title');
		else
			$name = false;
		if (!$name)
			$name = $attachment->get('filename');
		?>
			<li class='attachment_item'>
				<span class='attachment_name'><a target='_blank' href='<?php echo htmlspecialchars($attachment->get('url')); ?>'><?php echo htmlspecialchars($name); ?></a></span>
				<span class='attachment_delete'><button type='button' onclick='deleteAttachment("<?php echo htmlspecialchars($attachment_id); ?>");'><?php echo htmlspecialchars(_t('Delete')); ?></button></span>
			</li>
		<?php
	}
	$code = ob_get_clean();
	$values = array(
		'list' => $code
	);
} catch (Exception $e) {
	ob_end_clean();
	$values = array(
		'error' => $e->getMessage()
	);
	if (SERIA_DEBUG) {
		$values['trace'] = $e->getTraceAsString();
	}
}

SERIA_Lib::publishJSON($values);

?>
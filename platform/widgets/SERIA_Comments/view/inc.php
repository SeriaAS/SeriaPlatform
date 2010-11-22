<?php

function getCommentsDisplay($widget, $mode=false)
{
	$contents = array();
	$comments = $widget->getComments();

	foreach ($comments as $comment) {
		if ($mode == 'admin') {
			$edit = seria_bml('div', array('class' => 'admin'))->addChild(seria_bml_ahref($_SERVER['REQUEST_URI'])->setAttr('onclick', 'return SERIA_Comments.edit('.$widget->getId().', '.$comment->id.');')->setText(_t('Edit')));
			$delete = seria_bml('div', array('class' => 'admin'))->addChild(seria_bml_ahref($_SERVER['REQUEST_URI'])->setAttr('onclick', 'return SERIA_Comments.deleteComment('.$widget->getId().', '.$comment->id.');')->setText(_t('Delete')));
		} else {
			$edit = false;
			$delete = false;
		}
		if ($comment->avatar_id !== null) {
			$avatar = SERIA_File::createObject($comment->avatar_id);
			$avatar = $avatar->getThumbnailURL(90, 90);
		} else if (($avatar = $widget->getDefaultAvatarURL()) === false)
			$avatar = SERIA_HTTP_ROOT.'seria/platform/widgets/SERIA_Comments/images/noimage.png';
		if ($comment->rating)
			$rating = $comment->rating;
		else
			$rating = _t('No');
		$contents[] = seria_bml('div', array('class' => 'comment'))->addChild(
			seria_divbuilder(array(95, 400), array(
				array(
					seria_bml('div', array('class' => 'author'))->addChildren(array(
						new SERIA_BMLImage($avatar, 'Avatar'),
						seria_bml('p')->setText($comment->author)
					)),
					seria_bml('div', array('class' => 'text'))->addChildren(array(
						seria_bml('div', array('class' => 'headline'))->addChildren(array(
							seria_bml('div', array('class' => 'heading'))->addChild(seria_bml('h3')->setText($comment->title)),
							seria_bml('div', array('class' => 'rating'))->setText(_t('Rating:%RATING%', array('RATING' => $rating))),
							$delete,
							$edit
						)),
						seria_bml('div', array('class' => 'content'))->setText($comment->text)
					))
				)
			))
		);
	}
	return $contents;
}

?>
<?php
	$this->setSubject(_t('A comment was flagged as inappropriate: '.SERIA_Meta::getReference($data['comment'])));
	$editUrl = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/apps/outboard/pages/comments/edit.php');
	$editUrl->setParam('id', $data['comment']->get('id'));
	echo _t('A comment has been flagged by a user as inappropriate.')."\n";
	echo _t('To open the comment: %URL%', array('URL' => $editUrl->__toString()))."\n";

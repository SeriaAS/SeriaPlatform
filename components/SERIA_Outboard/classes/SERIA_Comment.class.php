<?php
	/**
	*	Get all comments:
	*	
	*	$comments = SERIA_Comment::getComments($article);
	*	foreach($comments as $comment)
	*		echo $comment->get('title')."\n";
	*
	*	Add a comment:
	*
	*	$comment = new SERIA_Comment();
	*	$comment->set('user', SERIA_Base::user());
	*	$comment->set('title', $title);
	*	$comment->set('message', $message);
	*	
	*/
	class SERIA_Comment extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{comments}',
				'fields' => array(
					// Identifies which object this comment is attached to
					'metaObject' => array('SERIA_MetaObject required', _t('Attached to')),

					// A set-name for the comment in question
					'subset' => array('name required', _t('Subset')),

					// The user that wrote the comment
					'user' => array('SERIA_User', _t('User')),

					// Display name for the user
					'displayName' => array('name required', _t('Display name')),

					// URL for the user
					'userUrl' => array('url', _t('Website')),
					'userEMail' => array('email required', _t('E-mail')),

					// The comment title
					'title' => array('title required', _t('Title')),
	
					// The contents of the comment
					'message' => array('message required', _t('Message')),

					// Is the comment approved?
					'approved' => array('boolean', _t('Approved')),
					'rejected' => array('boolean', _t('Rejected')),
					'flagged' => array('boolean', _t("Flagged by user")),

					'notFlaggable' => array('boolean', _t("Not flaggable")),

					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'alteredDate' => 'modifiedDate',
					'alteredBy' => 'modifiedBy',
				),
			);
		}

		public static function getComments(SERIA_MetaObject $object, $subset='default')
		{
			$reference = SERIA_Meta::getReference($object);
			return SERIA_Meta::all('SERIA_Comment')
				->where('metaObject=:metaObject AND subset=:subset', array('metaObject' => $reference, 'subset' => $subset))
				->order('createdDate');
		}

		public static function getAllUnmoderatedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('(approved=0 OR approved IS NULL) AND (rejected=0 OR rejected IS NULL)')->order('createdDate');
		}

		public static function getAllModeratedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('approved=1 OR rejected=1')->order('createdDate');
		}

		public static function getAllApprovedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('approved=1 AND (rejected=0 OR rejected IS NULL)')->order('createdDate');
		}

		public static function getAllRejectedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('(approved=0 OR approved IS NULL) AND rejected=1')->order('createdDate');
		}

		public static function createAction(SERIA_MetaObject $object, $subset='default')
		{
			$comment = new SERIA_Comment();
			$form = new SERIA_ActionForm('create', $comment);
			$form->addField('title', $comment);
			$form->addField('message', $comment);
			$form->addField('displayName', $comment);
			if($form->hasData())
			{
				$comment->set('title', $form->get('title'));
				$comment->set('message', $form->get('message'));
				$comment->set('displayName', $form->get('message'));
				$form->errors = SERIA_Meta::validate($comment);
				if(!$form->errors)
				{
					$form->success = SERIA_Meta::save($comment);
				}
			}
			return $form;
		}

		public function editAction()
		{
//@TODO: Check access
			return SERIA_Meta::editAction('edit', $this, array('metaObject', 'subset', 'displayName', 'displayName', 'userUrl', 'userEMail', 'title', 'message', 'approved'));
		}

		public function approveAction()
		{
//@TODO: Check access
			$a = new SERIA_ActionUrl('accept', $this);
			if($a->invoked())
			{
				$this->set('rejected', false);
				$this->set('approved', true);
				$a->success = SERIA_Meta::save($this);
			}
			return $a;
		}

		public function rejectAction()
		{
//@TODO: Check access
			$a = new SERIA_ActionUrl('reject', $this);
			if($a->invoked())
			{
				$this->set('rejected', true);
				$this->set('approved', false);
				$a->success = SERIA_Meta::save($this);
			}
			return $a;
		}

		public function deleteAction()
		{
			return SERIA_Meta::deleteAction('delete', $this);
		}

		public function unflagAction()
		{
			$a = new SERIA_ActionUrl('unflag', $this);
			if($a->invoked())
			{
				$this->set('flagged', false);
				$a->success = SERIA_Meta::save($this);
			}
			return $a;
		}

		public function flagAction()
		{
			$a = new SERIA_ActionUrl('flag', $this);
			if($a->invoked())
			{
				$this->set('flagged', true);
				$a->success = SERIA_Meta::save($this);
			}
			return $a;
		}

		public function getLastFlagLog()
		{
			$fl = SERIA_Meta::all('SERIA_CommentLog');
			$fl->where('comment=:id', $this);
			$fl->order('createdDate DESC');
			return $fl->current();
		}

	}

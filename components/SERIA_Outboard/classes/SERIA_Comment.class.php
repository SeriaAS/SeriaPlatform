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
		const BEFORE_DELETE_HOOK = 'SERIA_Comment::beforeDeleteHook';
		const AFTER_DELETE_HOOK = 'SERIA_Comment::afterDeleteHook';
		const SEND_FLAGGED_NOTICE_HOOK = 'SERIA_Comment::sendFlaggedNotice';

		/*
		 * Hooks thrown by save:
		 */
		const COMMENT_SUBMIT_HOOK = 'SERIA_Comment::commentSubmitHook';
		const COMMENT_CHANGED_HOOK = 'SERIA_Comment::commentChangedHook';
		
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

					/*
					 * We have several states here. They should not have been named like this.
					 * To preserve strict backwards compatibility, I have just redefined the meaning:
					 *  'approved' => Approved good meaninful comment
					 *  'rejected' => Not so good meaningful comment, but still nothing wrong, so not a priority but
					 *                still no need to hide from public.
					 *  'hidden'   => Rejected, don't show publicly (not anywhere). Only admins can view.
					 */
					'approved' => array('boolean', _t('Approved')),
					'rejected' => array('boolean', _t('Low quality approved')),
					'hidden' => array('boolean', _t('Reject')),

					'flagged' => array('boolean', _t("Flagged by user")),

					'notFlaggable' => array('boolean', _t("Not flaggable")),

					'createdDate' => 'createdDate sortable',
					'createdBy' => 'createdBy',
					'alteredDate' => 'modifiedDate sortable',
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
			return SERIA_Meta::all('SERIA_Comment')->where('(approved=0 OR approved IS NULL) AND (rejected=0 OR rejected IS NULL) AND (hidden=0 OR hidden IS NULL)')->order('createdDate');
		}

		public static function getAllModeratedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('approved=1 OR rejected=1 OR hidden=1')->order('createdDate');
		}

		public static function getAllApprovedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('approved=1')->order('createdDate');
		}
		public static function getAllLowQualityComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('rejected=1')->order('createdDate');
		}
		public static function getAllRejectedComments()
		{
			return SERIA_Meta::all('SERIA_Comment')->where('hidden=1')->order('createdDate');
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

		public function editAction($captchaPassed=true)
		{
//@TODO: Check access
			$fields = array('metaObject', 'subset', 'displayName', 'displayName', 'userUrl', 'userEMail', 'title', 'message', 'approved');
			$action = new SERIA_ActionForm('edit', $this, $fields);
			$action->addField('captcha', array(
				'caption' => _t('Captcha field: '),
				'fieldtype' => 'hidden'
			), !$hasCaptcha);
			if ($action->hasData()) {
				foreach($fields as $field)
					$this->set($field, $action->get($field));
				$action->errors = SERIA_Meta::validate($this);
				if (!$captchaPassed)
					$action->errors['captcha'] = _t('Not correct. Type exacly what you see.');
				if (!$action->errors)
					$action->success = SERIA_Meta::save($this);
			}
			return $action;
		}

		public function approve()
		{
			$this->set('hidden', false);
			$this->set('rejected', false);
			$this->set('approved', true);
			$this->set('flagged', false);
			return SERIA_Meta::save($this);
		}
		public function approveAction()
		{
//@TODO: Check access
			$a = new SERIA_PostActionUrl('accept', $this);
			if($a->invoked())
			{
				$a->success = $this->approve();
				if (!$a->success)
					$a->error = _t('Failed to approve the comment!');
			}
			return $a;
		}
		public function lowQualityApprove()
		{
			$this->set('hidden', false);
			$this->set('approved', false);
			$this->set('rejected', true);
			$this->set('flagged', false);
			return SERIA_Meta::save($this);
		}
		public function lowQualityApproveAction()
		{
//@TODO: Check access
			$a = new SERIA_PostActionUrl('lowqapprove', $this);
			if($a->invoked())
			{
				$a->success = $this->lowQualityApprove();
				if (!$a->success)
					$a->error = _t('Failed to approve the comment!');
			}
			return $a;
		}
		public function reject()
		{
			$this->set('hidden', true);
			$this->set('approved', false);
			$this->set('rejected', false);
			$this->set('flagged', false);
			return SERIA_Meta::save($this);
		}
		public function rejectAction()
		{
//@TODO: Check access
			$a = new SERIA_PostActionUrl('reject', $this);
			if($a->invoked())
			{
				$a->success = $this->reject();
				if (!$a->success)
					$a->error = _t('Failed to reject the comment!');
			}
			return $a;
		}

		public function deleteAction()
		{
			return SERIA_Meta::deleteAction('delete', $this);
		}

		public function unflag()
		{
			$this->set('flagged', false);
			return SERIA_Meta::save($this);
		}
		public function unflagAction()
		{
			$a = new SERIA_PostActionUrl('unflag', $this);
			if($a->invoked())
			{
				$a->success = $this->unflag();
				if (!$a->success)
					$a->error = _t('Failed to remove flag from the comment!');
			}
			return $a;
		}

		public function sendFlaggedNotice()
		{
			if (!SERIA_Hooks::dispatchToFirst(SERIA_Comment::SEND_FLAGGED_NOTICE_HOOK, $this) &&
			    defined('OUTBOARD_FLAGGED_NOTICE_EMAIL') && OUTBOARD_FLAGGED_NOTICE_EMAIL) {
				$email = new SERIA_OutboardEmailTpl();
				$email->setTo(OUTBOARD_FLAGGED_NOTICE_EMAIL);
				if (defined('OUTBOARD_FLAGGED_NOTICE_EMAIL_FROM') && OUTBOARD_FLAGGED_NOTICE_EMAIL_FROM)
					$email->setFrom(OUTBOARD_FLAGGED_NOTICE_EMAIL_FROM);
				else {
					$host = SERIA_Url::current()->getHost();
					$email->setFrom('"'.$host.'" <outboard@'.$host.'>');
				}
				$email->parse(dirname(dirname(__FILE__)).'/emailtpl/flagged.php', array('comment' => $this));
				$email->send();
			}
		}
		public function flag()
		{
			$flag = $this->get('flagged');
			$this->set('flagged', true);
			$success = SERIA_Meta::save($this);
			if (!$flag)
				$this->sendFlaggedNotice();
			return $success;
		}
		public function flagAction()
		{
			$a = new SERIA_PostActionUrl('flag', $this);
			if($a->invoked())
			{
				$a->success = $this->flag();
				if (!$a->success)
					$a->error = _t('Failed to flag the comment!');
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

		protected function MetaBeforeDelete()
		{
			SERIA_Base::db()->beginTransaction();
			try {
				SERIA_Hooks::dispatch(self::BEFORE_DELETE_HOOK, $this);
				/*
				 * Delete sub-comments..
				 */
				$query = SERIA_Meta::all('SERIA_Comment')->where('metaObject = :metaObject', array('metaObject' => SERIA_Meta::getReference($this)));
				foreach ($query as $subcomment) {
					if (!SERIA_Meta::delete($subcomment)) {
						SERIA_Base::debug('<strong>ERROR: Failed to delete a subcomment (Cascading failed)</strong>');
						throw new SERIA_OutboardRollback();
					}
				}
				SERIA_Base::db()->commit();
			} catch (SERIA_RollbackRequiredException $rollback) {
				SERIA_Base::debug('<strong>WARNING: Rollback required because of sub-transaction rollback!</strong>');
				SERIA_Base::db()->rollBack();
				return false;
			} catch (SERIA_OutboardRollback $rollback) {
				SERIA_Base::debug('<strong>WARNING: Rollback commanded!</strong>');
				SERIA_Base::db()->rollBack();
				return false;
			} catch (Exception $e) {
				SERIA_Base::debug('<strong>WARNING: Exception: '.$e->getMessage().'</strong>');
				SERIA_Base::debug('<strong>WARNING: Rollback because of exception!</strong>');
				SERIA_Base::db()->rollBack();
				throw $e;
			}
			return true;
		}
		protected function MetaAfterDelete()
		{
			SERIA_Hooks::dispatch(self::AFTER_DELETE_HOOK, $this);
		}
		protected $metaObjectIsNew = false;
		protected function MetaAfterCreate()
		{
			$this->metaObjectIsNew = true;
		}
		protected function MetaAfterSave()
		{
			if ($this->metaObjectIsNew)
				SERIA_Hooks::dispatch(self::COMMENT_SUBMIT_HOOK, $this);
			else
				SERIA_Hooks::dispatch(self::COMMENT_CHANGED_HOOK, $this);
			$this->metaObjectIsNew = false;
		}
	}

<?php

	class SERIA_CommentLog extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{comments_log}',
				'fields' => array(
					'comment' => array('SERIA_Comment required', _t("Comment")),
					'user' => array('SERIA_User', _t("User")),
					'action' => array('enum', _t("Action"), array(
						'values' => array(
							'flag' => _t("Flag this comment"),
						),
						'type' => 'enum("flag")',
					)),
					'createdDate' => 'createdDate',
					'flagSpam' => array('boolean', _t("Spam")),
					'flagPersonalAttack' => array('boolean', _t("Personal attack")),
					'flagRacist' => array('boolean', _t("Racist")),
					'flagPorn' => array('boolean', _t("Pornographic")),
					'flagCopyright' => array('boolean', _t("Copyright")),
					'flagOther' => array('boolean', _t("Other reason")),
				),
			);
		}

		/**
		*	Returns an action object for reporting a comment
		*	@param SERIA_Comment $comment	The SERIA_Comment object that the user is reporting
		*	@return SERIA_ActionForm [flagSpam,flagPersonalAttack,flagRacist,flagPorn,flagCopyright,flagOther]
		*/
		public static function flagCommentAction(SERIA_Comment $comment)
		{
			$log = new SERIA_CommentLog();
			$log->set('comment', $comment);
			$log->set('action', 'flag');
			$log->set('user', SERIA_Base::user());
			return SERIA_Meta::editAction('flag', $log, array(
				'flagSpam', 'flagPersonalAttack', 'flagRacist', 'flagPorn', 'flagCopyright', 'flagOther'
			));
		}
	}

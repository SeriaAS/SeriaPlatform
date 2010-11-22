<?php

	class SERIA_CommentsWidget_CommentRecord extends SERIA_ActiveRecord
	{
		public $tableName = '_widgets_comments';
		public $usePrefix = true;
	}

	class SERIA_CommentsWidget extends SERIA_Widget {
		protected $attachedEvents = array('DELETE' => 'onDelete');

		protected $defaultAvatarURL = '';

		protected $commentFormEnable = true;

		public function initialize() {
			$installer = new SERIA_Installer();

			$installer->runWidgetUpdates();
		}

		public function getComments()
		{
			return SERIA_CommentsWidget_CommentRecords::find_all_by_widget_id($this->getId());
		}
		public function getComment($id)
		{
			$comment = SERIA_CommentsWidget_CommentRecords::find_first_by_id($id);
			if ($comment !== null) {
				if ($comment->widget_id == $this->getId())
					return $comment;
			}
			return null;
		}
		public function addComment($author, $avatar, $title, $text, $rating)
		{
			$comment = new SERIA_CommentsWidget_CommentRecord();
			$comment->widget_id = $this->getId();
			$comment->author = $author;
			if ($avatar) {
				$avatar->increaseReferrers();
				$comment->avatar_id = $avatar->get('id');
			} else
				$comment->avatar_id = null;
			$comment->title = $title;
			$comment->text = $text;
			$comment->rating = $rating;

			/*
			 * Store rating in article object..
			 */
			$article = SERIA_NamedObjects::getInstanceOf($this->guid);
			$article->vote($rating);

			$comment->save();
		}
		public function deleteComment(SERIA_CommentsWidget_CommentRecord $comment)
		{
			if ($comment->avatar_id)
				$avatar = SERIA_File::createObject($comment->avatar_id);
			else
				$avatar = false;
			$comment->delete();
			if ($avatar)
				$avatar->decreaseReferrers();
		}
		public function deleteAllComments()
		{
			$comments = $this->getComments();
			foreach ($comments as $comment)
				$this->deleteComment($comment);
		}

		protected function onDelete($source) {
			$this->deleteAllComments();
			$this->delete();
		}
		protected function onPublish($source) {
		}
		
		public function action_index() {
		}
		public function action_popup() {
		}
		public function action_edit() {
		}
		public function action_form() {
		}

		/**
		 * 
		 * @param $defavatarURL False to use file-id instead
		 * @param $defavatarId File-id if URL is false
		 * @return unknown_type
		 */
		public static function setDefaultAvatarForAll($defavatarURL, $defavatarId=false)
		{
			if ($defavatarURL) {
				SERIA_Base::setParam('SERIA_Comments->defaultAvatarURL', $defavatarURL);
				SERIA_Base::setParam('SERIA_Comments->defaultAvatarID', '');
			} else {
				SERIA_Base::setParam('SERIA_Comments->defaultAvatarURL', '');
				SERIA_Base::setParam('SERIA_Comments->defaultAvatarID', $defavatarId);
			}
		}
		public static function getDefaultAvatarURLForAll()
		{
			$defURL = SERIA_Base::getParam('SERIA_Comments->defaultAvatarURL');
			if ($defURL)
				return $defURL;
			$defID = SERIA_Base::getParam('SERIA_Comments->defaultAvatarID');
			if (!$defID)
				return false;
			$imgobj = SERIA_File::createObject($defID);
			return $imgobj->get('url');
		}
		public function setDefaultAvatarURL($defurl)
		{
			$this->defaultAvatarURL = $defurl;
			return $this;
		}
		public function getDefaultAvatarURL()
		{
			if ($this->defaultAvatarURL)
				return $this->defaultAvatarURL;
			else
				return self::getDefaultAvatarURLForAll();
		}
		public function setCommentFormEnable($enabling)
		{
			$this->commentFormEnable = $enabling;
			return $this;
		}
		public function getCommentFormEnable()
		{
			return $this->commentFormEnable;
		}
	}

?>
<?php

class SERIA_ShareWithFriendsWidget extends SERIA_Widget
{
	protected $attachedEvents = array('DELETE' => 'onDelete');
	public $errors = false;

	protected function onDelete($source) {
		$this->delete();
	}

	public function getGUID()
	{
		return $this->guid;
	}

	public function action_index() {
	}
	public function action_admin() {
	}
}

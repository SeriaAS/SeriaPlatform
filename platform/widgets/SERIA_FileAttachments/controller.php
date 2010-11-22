<?php

class SERIA_FileAttachmentsWidget extends SERIA_Widget
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

	public function addAttachment($fileobj)
	{
		$insert = array(
			'id' => SERIA_Base::guid(),
			'widget_id' => $this->getId(),
			'file_id' => $fileobj->get('id')
		);
		$fileobj->increaseReferrers();
		SERIA_Base::db()->insert('{widgets_attachments}', array_keys($insert), $insert);
	}
	public function removeAttachmentById($id)
	{
		$pk = array('widget_id' => $this->getId(), 'id' => $id);
		$info = SERIA_Base::db()->query('SELECT file_id FROM {widgets_attachments} WHERE widget_id = :widget_id AND id = :id', $pk)->fetch(PDO::FETCH_ASSOC);
		$fileobj = SERIA_File::createObject($info['file_id']);
		SERIA_Base::db()->exec('DELETE FROM {widgets_attachments} WHERE widget_id = :widget_id AND id = :id', $pk);
		$fileobj->decreaseReferrers();
	}
	public function getAttachments()
	{
		$attachments = SERIA_Base::db()->query('SELECT * FROM {widgets_attachments} WHERE widget_id = :widget_id', array('widget_id' => $this->getId()))->fetchAll(PDO::FETCH_ASSOC);
		$attachments_ret = array();
		foreach ($attachments as $attachment)
			$attachments_ret[$attachment['id']] = SERIA_File::createObject($attachment['file_id']);
		return $attachments_ret;
	}

	public function action_index() {
	}
	public function action_admin() {
	}
}

?>
<?php

class SERIA_QueueTask extends SERIA_MetaObject
{
	public static function createObject($description, $data=null, $priority=0, $duplicateIdentifier=null)
	{
		if (!$description)
			throw new SERIA_Exception('Description is required.');
		if ($data === null)
			throw new SERIA_Exception('Data for task is required.');
		$obj = new static();
		$obj->set('description', $description);
		$obj->set('data', $data);
		$obj->set('priority', $priority);
		$obj->set('uniq_id', $duplicateIdentifier);
	}
	public static function getFieldSpec() // returns array() specifying rules for the columns
	{
		return array(
			'uniq_id' => array('name', _t('Identifier')),
			'state' => array('integer', _t('State')),
			'reason' => array('text', _t('Reason')),
			'description' => array('text', _t('Description')),
			'data' => array('text', _t('Blob')),
			'priority' => array('integer', _t('Priority')),
			'queue' => array('name', _t('Queue')),
			'expires' => array('integer', _t('Expires'))
		);
	}
	public static function Meta($instance = NULL)
	{
		return array(
			'table' => '{queue_task}',
			'primaryKey' => 'id',
			'fields' => static::getFieldSpec()
		);
	}
	public static function fromDB($row)
	{
		return SERIA_Queue::createObject($row['name'], $row['title'], $row['description']);
	}

	/**
	 * Removes the task from the queue, and records a reason to why it failed. It will not be run again.
	 * 
	 * @param String $reason  A human readable description of why the task failed.
	 */
	function failed($reason)
	{
		$this->set('state', SERIA_Queue::FAILED);
		$this->set('reason',$reason);
		$this->save();
	}  

	/**
	 * Removes the task from the queue, and records it to be completed. It will not be run again.
	 */
	function success()
	{
		$this->set('state', SERIA_Queue::COMPLETED);
		$this->save();
	}

	/**
	 * Returns the task into the queue. DANGEROUS. May cause items to stay in queue forever.
	 *
	 * @param String $reason  A human readable reason to why the task was postponed. Will be displayed in queue panel in the control panel.
	 */
	function postpone($reason)
	{
		/* TODO */
		throw new Exception('TODO');
	}

	public function isDeletable() // returns true if the user is allowed to delete this object
	{
		return true;
	}
}

<?php

class SERIA_QueueTask extends SERIA_FluentObject
{
	/**
	 * Create a queue if it does not exist. Change $title and $description in database if changed.
	 */
	public function __construct($description, $data=null, $priority=0, $duplicateIdentifier=null)
	{
		if ((is_array($description) || is_numeric($description)) && $description) {
			parent::__construct($description);
			return;
		}
		if (!$description)
			throw new SERIA_Exception('Description is required.');
		if ($data === null)
			throw new SERIA_Exception('Data for task is required.');
		parent::__construct();
		$this->set('description', $description);
		$this->set('data', $data);
		$this->set('priority', $priority);
		$this->set('uniq_id', $duplicateIdentifier);
	}
	public static function getFieldSpec() // returns array() specifying rules for the columns
	{
		return array(
			'id' => array(
				'caption' => _t('ID'),
				'fieldtype' => 'integer',
				'validator' => new SERIA_Validator(array())
			),
			'uniq_id' => array(
				'caption' => _t('Identifier'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'state' => array(
				'caption' => _t('State'),
				'fieldtype' => 'integer',
				'validator' => new SERIA_Validator(array())
			),
			'reason' => array(
				'caption' => _t('Reason'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'description' => array(
				'caption' => _t('Description'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'data' => array(
				'caption' => _t('Blob'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'priority' => array(
				'caption' => _t('Priority'),
				'fieldtype' => 'int',
				'validator' => new SERIA_Validator(array())
			),
			'queue' => array(
				'caption' => _t('Queue'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'expires' => array(
				'caption' => _t('Expires'),
				'fieldtype' => 'integer',
				'validator' => new SERIA_Validator(array())
			)
		);
	}
	public static function fluentSpec() // returns array('table' => '{tablename}', 'primaryKey' => 'id', 'selectWhere' => 'ownerId=123')
	{
		return array(
			'table' => '{queue_task}',
			'primaryKey' => 'id'
		);
	}
	public static function fromDB($row)
	{
		return new SERIA_Queue($row['name'], $row['title'], $row['description']);
	}
	public static function createObject($p=false) // should accept false, an array or a primay key and return a single instance of the object (must use caching)
	{
			return new SERIA_QueueTask($p);
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

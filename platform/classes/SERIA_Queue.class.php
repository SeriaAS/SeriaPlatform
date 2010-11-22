<?php

class SERIA_Queue extends SERIA_FluentObject
{
	const QUEUED = 0;
	const COMPLETED = 1;
	const FAILED = 2;
	const PROCESSING = 3;
	const NOT_FOUND = 4;

	public function __construct($name=false, $title=null, $description=null)
	{
		parent::__construct($name);
		if ($title !== null)
			$this->set('title', $title);
		if ($description !== null)
			$this->set('description', $description);
	}
	public static function getFieldSpec() // returns array() specifying rules for the columns
	{
		return array(
			'id' => array(
				'caption' => _t('Name'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'title' => array(
				'caption' => _t('Title'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			),
			'description' => array(
				'caption' => _t('Description'),
				'fieldtype' => 'text',
				'validator' => new SERIA_Validator(array())
			)
		);
	}
	public static function fluentSpec() // returns array('table' => '{tablename}', 'primaryKey' => 'id', 'selectWhere' => 'ownerId=123')
	{
		return array(
			'table' => '{queue}',
			'primaryKey' => 'id'
		);
	}
	public static function fromDB($row)
	{
		return new SERIA_Queue($row['id'], $row['title'], $row['description']);
	}
	/**
	 * Create a queue if it does not exist. Change $title and $description in database if changed.
	 *
	 * @param $name
	 * @param $title
	 * @param $description
	 * @return unknown_type
	 */
	public static function createObject($name=false, $title=null, $description=null) // should accept false, an array or a primay key and return a single instance of the object (must use caching)
	{
		if ($name === false)
			throw new SERIA_Exception('This object can not be created without a name.');
		try {
			$obj = new SERIA_Queue($name, $title, $description);
			if ($title !== null || $description !== null)
				$obj->save();
		} catch (SERIA_NotFoundException $e) {
			if ($title === null || $description === null)
				throw new SERIA_NotFoundException('Creating a queue without a title or description.');
			SERIA_Base::db()->insert('{queue}', array('id', 'title', 'description'), array(
				'id' => $name,
				'title' => $title,
				'description' => $description
			));
			$obj = new SERIA_Queue($name);
		}
		return $obj;
	}
	public function set($name, $value)
	{
		switch ($name) {
			case 'id':
				if (isset($this->row['id']))
					throw new SERIA_exception('id can only be set once.');
				break;
		}
		return parent::set($name, $value);
	}

	/**
	 * Add a task to the queue and generate a new GUID which is returned. If a duplicate is found, then record a mapping from the new GUID to the old GUID.
	 *
	 * @param SERIA_QueueTask $task
	 * @return unknown_type
	 */
	public function add(SERIA_QueueTask $task)
	{
		$task->set('queue', $this->get('id'));
		$task->save();
	}
	/**
	 * Check the status of the $taskId, returning one of SERIA_Queue::COMPLETED | SERIA_Queue::FAILED | SERIA_Queue::PROCESSING | SERIA_Queue::QUEUED | SERIA_Queue::NOT_FOUND
	 * If the taskId is not found in the queue table, check the mapping table to see if it exists with another GUID because of $duplicateIdentifier.
	 *
	 * @param $taskId
	 * @return unknown_type
	 */
	public function check($taskId)
	{
		if (!is_numeric($taskId) || !$taskId)
				throw new SERIA_Exception('Bad task-id');
		$task = new SERIA_QueueTask($taskId);
		if ($task->get('queue') != $this->get('id'))
			throw new SERIA_Exception('This task is not on this queue.');
		return $task->get('state');
	}
	protected function handleExpired()
	{
		$query = new SERIA_FluentQuery('SERIA_QueueTask', 'queue = :queue AND state = :state AND expires <= :curtime', array('queue' => $this->get('id'), 'state' => SERIA_Queue::PROCESSING, 'curtime' => time()));
		$query = $query->order('id')->limit(10);
		foreach ($query as $object) {
			$object->set('state', SERIA_Queue::QUEUED);
			$object->save();
		}
	}
	/**
	 * Returns false or a SERIA_QueueTask-object. If task is not completed or failed within $timeToLive seconds, the task is SERIA_Queue::QUEUED again.
	 *
	 * @param $timeToLive
	 * @return unknown_type
	 */
	public function fetch($timeToLive)
	{
		$this->handleExpired();
		$query = new SERIA_FluentQuery('SERIA_QueueTask', 'queue = :queue AND state = :state', array('queue' => $this->get('id'), 'state' => SERIA_Queue::QUEUED));
		$object = $query->order('id')->limit(1)->current();
		if ($object !== false) {
			$object->set('expires', time() + $timeToLive);
			$object->set('state', SERIA_Queue::PROCESSING);
			$object->save();
		}
		return $object;
	}
	/**
	 * Return false or the SERIA_QueueTask object. The returned object should be in read-only mode - calling success() postpone() or failed() will throw an exception.
	 * Should check the task mapping table.
	 *
	 * @param $taskId
	 * @return unknown_type
	 */
	function peek($taskId)
	{
		/* TODO */
		throw new Exception('TODO');
	}

	public function isDeletable() // returns true if the user is allowed to delete this object
	{
		return false;
	}
}

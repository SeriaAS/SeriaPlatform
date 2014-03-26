<?php

class SERIA_Queue extends SERIA_MetaObject
{
	const QUEUED = 0;
	const COMPLETED = 1;
	const FAILED = 2;
	const PROCESSING = 3;
	const NOT_FOUND = 4;

	public static function Meta($instance = NULL)
	{
		return array(
			'table' => '{queue}',
			'primaryKey' => 'id',
			'fields' => array(
				'id' => array('name', _t('Name')),
				'title' => array('text', _t('Title')),
				'description' => array('text', _t('Description'))
			)
		);
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
		$save = FALSE;
		if ($name === false)
			throw new SERIA_Exception('This object can not be created without a name.');
		try {
			$obj = SERIA_Meta::load('SERIA_Queue', $name);
		} catch (SERIA_Exception $e) {
			if ($title === null || $description === null)
				throw new SERIA_NotFoundException('Creating a queue without a title or description.');
			$obj = new static();
			$obj->set('id', $name);
			$save = TRUE;
		}
		if ($title !== null) {
			$obj->set('title', $title);
			$save = TRUE;
		}
		if ($description !== null) {
			$obj->set('description', $description);
			$save = TRUE;
		}
		if ($save)
			SERIA_Meta::save($obj);
		return $obj;
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
		$task = SERIA_Meta::load('SERIA_QueueTask', $taskId);
		if ($task->get('queue') != $this->get('id'))
			throw new SERIA_Exception('This task is not on this queue.');
		return $task->get('state');
	}
	protected function handleExpired()
	{
		$query = SERIA_Meta::all('SERIA_QueueTask')->where('queue = :queue AND state = :state AND expires <= :curtime', array('queue' => $this->get('id'), 'state' => SERIA_Queue::PROCESSING, 'curtime' => time()));
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
		$query = SERIA_Meta::all('SERIA_QueueTask')->where('queue = :queue AND state = :state', array('queue' => $this->get('id'), 'state' => SERIA_Queue::QUEUED));
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

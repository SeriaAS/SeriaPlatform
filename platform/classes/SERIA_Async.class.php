<?php

class SERIA_Async {
	protected static $queue = null;

	public static function call($callback /* args... */)
	{
		$args = func_get_args();
		if (self::$queue === null)
			self::$queue = SERIA_Queue::createObject('SERIA_Async', 'Asynchronous calls', 'The system queue for generic asynchronous calls');
		$cb = array_shift($args);
		$call = array(
			'call' => $cb,
			'args' => $args
		);
		$task = SERIA_QueueTask::createObject('Call', serialize($call));
		self::$queue->add($task);
	}
}
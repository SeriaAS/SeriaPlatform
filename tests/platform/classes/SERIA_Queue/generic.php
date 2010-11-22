<?php

require_once(dirname(__FILE__).'/../../../../main.php');

$queue = SERIA_Queue::createObject('Test', 'Test Queue', 'Test Queue');

$task = new SERIA_QueueTask('Test Task', 'TDATA');

$queue->add($task);

$tfetch = $queue->fetch(1000);

if ($tfetch !== false) {
	echo 'Task todo: '.$tfetch->get('data')."<br>\n";
	$tfetch->success();
}

$tfetch = $queue->fetch(1000);

if ($tfetch !== false) {
	echo 'Task todo: '.$tfetch->get('data')."<br>\n";
	$tfetch->success();
}

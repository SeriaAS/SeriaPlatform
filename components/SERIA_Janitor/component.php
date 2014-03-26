<?php

if (SERIA_COMPATIBILITY < 3) {
	/**


		DEPRECATED




	*	SERIA_Janitor is a special class that aids maintenance functions in Seria Platform and
	*	Seria Platform applications by throwing events on regular intervals. These events can
	*	be intercepted by any class or object and used to trigger tasks on a regular basis.
	*
	*	Usage:
	*	$janitor = SERIA_Janitor::getInstance();
	*
	*	// For event listener objects
	*	$janitor->addEventListener("MONTHLY", $eventListenerObject);
	*	// or
	*	SERIA_EventDispatcher::addClassEventListener("SERIA_Janitor","MONTHLY",$eventListenerObject);
	*
	*	// For applications
	*	SERIA_EventDispatcher::addClassEventHook("SERIA_Janitor","MONTHLY",$callback);
	*
	*
	*/
	class SERIA_Janitor extends SERIA_EventDispatcher
	{
		/**
		*	Maintenance tasks are run regularly. This event dispatcher throws
		*	events on specific intervals:
		*
		*	MONTHLY			Thrown on the first day of every month
		*	WEEKLY			Thrown weekly, on the lowest traffic period of the week
		*	NIGHT			Thrown once every day, during low traffic periods.
		*	HOUR			Thrown every hour
		*	HALFHOUR		Thrown every 30 minutes
		*	10MINUTE		Thrown every 10 minutes
		*	5MINUTE			Thrown every 5 minutes
		*	MINUTE			Thrown every minute
		*	MONDAY			Thrown every monday
		*	TUESDAY			Thrown every tuesday
		*	WEDNESDAY		Thrown every wednesday
		*	THURSDAY		Thrown every thursday
		*	FRIDAY			Thrown every friday
		*	SATURDAY		Thrown every saturday
		*	SUNDAY			Thrown every sunday
		*
		*	To run maintenance tasks every monday do the following:
		*
		*	For persistent (added once, and stored in database):
		*
		*		SERIA_EventDispatcher::addClassEventListener("SERIA_Maintain", "MONDAY", $myEventListener);
		*
		*	For hooks (must be added on every page view, before the run() method is called. Usually from within applications.
		*
		*		SERIA_EventDispatcher::addClassEventHook("SERIA_Maintain", "MONDAY", $myCallbackMethod);
		*/
		function run()
		{
			$lastRunTime =
			$this->throwEvent("MINUTE");
		}

		static function start($name, $seconds)
		{
				// script is running
				if(SERIA_Base::getParam("maintain_".$name."_is_running")) {
						if (SERIA_Base::getParam('maintain_' . $name . '_last_run') > time() - 3600) {
								return false;
						} else {
								SERIA_Base::debug($name . ' maintain is marked as running, but time limit exceeded. Ignoring status.');
						}
			}

				// less than $seconds since last run
				$lastRun = intval(SERIA_Base::getParam("maintain_".$name."_last_run"));
				if($lastRun > time()-$seconds)
						return false;

				SERIA_Base::setParam("maintain_".$name."_last_run", time());
				SERIA_Base::setParam("maintain_".$name."_is_running", 1);

				register_shutdown_function("stop_maintain", $name);

				return true;
		}

		function stop($name)
		{
			SERIA_Base::setParam("maintain_".$name."_is_running", 0);
		}


		function getObjectId()
		{
			return array("SERIA_Maintain","getInstance");
		}

		static function getInstance()
		{
			static $instance = false;
			if($instance===false)
				$instance = new SERIA_Janitor();
			return $instance;
		}
	}
}

<?php
	/**
	 * Objects implementing this interface can catch events 
	 * Object must also implement SERIA_NamedObject
	 */
	interface SERIA_EventListener extends SERIA_NamedObject
	{
		function catchEvent(SERIA_EventDispatcher $source, $eventName);
	}

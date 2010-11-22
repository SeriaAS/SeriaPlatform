<?php
/**
 *	Implementation:
 *  	$object->addEventListener('delete', $this);
 *	or
 *	SERIA_EventDispatcher::addClassEventListener("classname", "eventname", "classEventListener");
 */


	// EXPERIMENTAL
	abstract class SERIA_EventDispatcher implements SERIA_NamedObject
	{
		private static $eventHooks = array();

		/**
		 * Adds an event listener to this object. Whenever this object throws an
		 * event using $this->throwEvent, all listeners listening to the specified 
		 * event name will be instantiated and the catchEvent method will be called 
		 * with event name and a reference to $this object. 
		 */
		public function addEventListener($eventName, SERIA_EventListener $listener)
		{
			$targetId = serialize($listener->getObjectId());
			$sourceId = serialize($this->getObjectId());
			
			$id = SERIA_Base::guid('eventlistener');
			try
			{
				SERIA_Base::db()->exec('INSERT INTO '.SERIA_PREFIX.'_event_listeners (id, source, target, eventName) VALUES (
					'.SERIA_Base::db()->quote($id).',
					'.SERIA_Base::db()->quote($sourceId).',
					'.SERIA_Base::db()->quote($targetId).',
					'.SERIA_Base::db()->quote($eventName).'
				)');
			}
			catch (SERIA_Exception $e)
			{
				throw new SERIA_Exception('Can\'t listen to the same event multiple times. This execption should be avoided, not catched - for scalability.');
			}
		}

		/**
		*	Add a callback function to receive events thrown from various classes.
		*/
		public static function addClassEventHook($className, $eventName, $callback)
		{
			if(!isset(self::$eventHooks[$className]))
				self::$eventHooks[$className] = array();
			if(!isset(self::$eventHooks[$className][$eventName]))
				self::$eventHooks[$className][$eventName] = array();

			self::$eventHooks[$className][$eventName][] = $callback;
		}

		/**
		 * Adds a class event listener that will receive all events thrown in the system. This is persistant.
		 */
		public static function addClassEventListener($className, $eventName, SERIA_EventListener $listener)
		{
			if(!class_exists($className))
				throw new SERIA_Exception("No such class '$className'.");
			if(!is_subclass_of($className, 'SERIA_EventDispatcher'))
				throw new SERIA_Exception("Can't listen to classes that does not extend SERIA_EventDispatcher.");

			$targetId = serialize($listener->getObjectId());
			$id = SERIA_Base::guid('eventlistener');
			try
			{
				SERIA_Base::db()->exec('INSERT INTO '.SERIA_PREFIX.'_event_listeners (id, source, target, eventName) VALUES (
					'.SERIA_Base::db()->quote($id).',
					'.SERIA_Base::db()->quote($className).',
					'.SERIA_Base::db()->quote($targetId).',
					'.SERIA_Base::db()->quote($eventName).'
				)');
			}
			catch (SERIA_Exception $e)
			{
				throw new SERIA_Exception('Can\'t listen to the same event multiple times. This execption should be avoided, not catched - for scalability.');
			}
		}

		/**
		 * Throw an event to all listening objects. Return values are added to a result array.
		 * 
		 * @param $eventName The name of the event being fired, for example 'delete' or 'update'.
		 */	
		public function throwEvent()
		{
			$args = func_get_args();
			$eventName = $args[0];
			unset($args[0]);

			$results = array();
			$listeners = $this->getEventListeners($eventName);
			$args = array_merge(array($this, $eventName), $args);

			$classNames = array($className = get_class($this));
			while($className = get_parent_class($className))
				$classNames[] = $className;

			foreach($classNames as $className)
			{
				if(isset(self::$eventHooks[$className]) && isset(self::$eventHooks[$className][$eventName]))
				{
					foreach(self::$eventHooks[$className][$eventName] as $callback)
					{
						if(!is_callable($callback))
						{
							throw new SERIA_Exception('Invalid callback \''.$callback.'\' added as event hook.');
						}
						$results[] = call_user_func_array($callback, $args);
					}
				}
			}

			foreach($listeners as $listener) {
				$function = array($listener, "catchEvent");
				$results[] = call_user_func_array($function, $args);
			}
			return $results;
		}
		
		/**
		 * Get an array of all eventlisteners listening to this object, this class or any of this class's parent classes
		 * @param $eventName The name of the event type that we want listeners for
		 */
		public function getEventListeners($eventName=false)
		{
			$db = SERIA_Base::db();

			$classNameParts = array('source='.$db->quote($className = get_class($this)));
			
			while($className = get_parent_class($className))
				$classNameParts[] = 'source='.$db->quote($className);

			$classNameParts[] = 'source='.$db->quote(serialize($this->getObjectId()));

			$classNamePartsSQL = '('.implode(' OR ', $classNameParts).')';

			$query = 'SELECT target FROM '.SERIA_PREFIX.'_event_listeners WHERE '.$classNamePartsSQL.($eventName!==false?' AND eventName='.SERIA_Base::db()->quote($eventName):'');
			$listeners = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
			$listeners = array_flip($listeners);
			foreach($listeners as $objectId => $object)
			{
				try
				{
					$listeners[$objectId] = SERIA_NamedObjects::getInstanceOf($objectId);
					if (!$listeners[$objectId])
						unset($listeners[$objectId]);
				}
				catch (SERIA_Exception $e)
				{
					unset($listeners[$objectId]);
				}
			}
			return $listeners;
		}
	}

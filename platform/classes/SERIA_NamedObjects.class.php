<?php
	/**
	*	The purpose of this class is to facilitate referencing of objects across
	*	the entire system, without having to specify how this object is recreated when
	*	it is no longer in memory. This can be used for storing event-listeners or
	*	extending the functionality of any object.
	*/
	class SERIA_NamedObjects
	{
		/**
		 * Returns an instance of any object using the object id
		 * @return SERIA_NamedObject
		 */
		static function getInstanceOf($objectId)
		{
			if(is_array($objectId))
				$parts = $objectId;
			else
				$parts = unserialize($objectId);
			if(!$parts)
				throw new SERIA_Exception("Invalid object id '".$objectId."'.");

			list($className, $method) = $parts;
			$args = array_slice($parts, 2);
			$result = call_user_func_array(array($className, $method), $args);
			return $result;
		}

		/**
		*	Get, or create a public identifier for an object implementing SERIA_NamedObject. The use case
		*	for public identifiers are perhaps when you need an untrusted source to be able to reference
		*	an object instance - for example trough AJAX etc.
		*
		*	@param SERIA_NamedObject $object	The object you need a public id of
		*	@return int
		*/
		static function getPublicId(SERIA_NamedObject $object)
		{
			$name = serialize($object->getObjectId());
			$db = SERIA_Base::db();
			$sql = 'SELECT * FROM {namedobjects} WHERE name=?';
			try {
				$row = $db->query($sql, array($name))->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $e) {
				if($e->getCode()==="42S02")
				{
					$db->exec("CREATE TABLE {namedobjects} (id INTEGER PRIMARY KEY, name VARCHAR(100), UNIQUE(name)) ENGINE=InnoDB");
					$row = false;
				}
				else throw $e;
			}
			if($row)
				return $row['id'];

			$id = SERIA_Base::guid();
			if($db->exec("INSERT INTO {namedobjects} (id, name) VALUES (?, ?)", array($id, $name)))
				return $id;
			throw new SERIA_Exception("I was unable to insert an id for this object.");
		}

		/**
		*	Get an instance by its public identifier.
		*
		*	@param int $id	The identifying number
		*	@param string $classExpected The name of an interface or a class that the object must implement.
		*/
		static function getInstanceByPublicId($id, $classExpected)
		{
			$db = SERIA_Base::db();
			try {
				$rs = $db->query("SELECT name FROM {namedobjects} WHERE id=?", array($id));
			} catch (PDOException $e) {
				if($e->getCode()==="42S02")
				{
					$db->exec("CREATE TABLE {namedobjects} (id INTEGER PRIMARY KEY, name VARCHAR(100), UNIQUE(name)) ENGINE=InnoDB");
					$rs = $db->query("SELECT name FROM {namedobjects} WHERE id=?", array($id));
				}
				else throw $e;
			}
			$name = $rs->fetch(PDO::FETCH_COLUMN, 0);
			if($name) {
				$instance = self::getInstanceOf($name);
				if($classExpected!==NULL)
				{
					if(!($instance instanceof $classExpected))
					{
						throw new SERIA_Exception("Incorrect class. Potential security issue.", SERIA_Exception::ACCESS_DENIED);
					}
				}
				return $instance;
			}
			throw new SERIA_Exception("Instance not found", SERIA_Exception::NOT_FOUND);
		}
	}


<?php
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
		static function getPublicKey(SERIA_NamedObject $object)
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

		static function getInstanceByPublicKey($id)
		{
			$db = SERIA_Base::db();
			$rs = $db->query("SELECT name FROM {namedobjects} WHERE id=?", array($id));
			$name = $rs->fetch(PDO::FETCH_COLUMN, 0);
			if($name) return self::getInstanceOf($name);
			throw new SERIA_Exception("Instance not found", SERIA_Exception::NOT_FOUND);
		}
	}

?>

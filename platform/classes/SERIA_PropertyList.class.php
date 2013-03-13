<?php

	/**
	* SERIA_PropertyList
	* Construct by giving a namedObject (ie: User, or Article). like this (to use caching) : $proplist = SERIA_PropertyList::createObject($namedObject);
	*
	* use the set and get functions to set properties for any given object. these will be represented in an indexed database structure.
	*
	* query Queries the database by className, fieldName and value
	*
	* deleteFromAll removes all entries by a given domain. (for uninstalling purposes)
	*
	**/

	class SERIA_PropertyList {
	
		private $object,$className;

		private $properties = array(), $deletedProperties = array();

		function __construct(SERIA_NamedObject $o, $direct = true) {
			if($direct)
				throw new SERIA_Exception('Use SERIA_PropertyList::createObject($object) to create the propertylist.');
			$this->object = $o;
		}

		function &createObject(SERIA_NamedObject $o)
		{ // caches objects created
			static $cache = false;

			if ($plist = SERIA_Hooks::dispatchToFirst('SERIA_PropertyList::createObject', $o))
				return $plist;

			if($cache===false) $cache = array();
			try {
				if(isset($cache[$s = serialize($o->getObjectId())]))
					return $cache[$s];
				if (!($plist = SERIA_Hooks::dispatchToFirst('SERIA_PropertyList::createObject('.$s.')', $o)))
					$plist = new SERIA_PropertyList($o, false);
				return $cache[$s] = $plist;
			}
			catch (Exception $e)
 			{
				return new SERIA_PropertyList($o, false);
 			}
		}

		public function set($name, $value) // default sort as string
		{
			if($value === NULL) throw new SERIA_Exception('Do not set properties to NULL. Instead, use SERIA_PropertyList->delete().');
			$this->properties[$name] = $value;
		}
		public function save()
		{
			try
			{
				$objectKey = serialize($this->object->getObjectId());
			}
			catch (SERIA_Exception $e)
			{
				throw new SERIA_Exception('Cannot save the property list unless you first save the object "'.get_class($this->object).'".');
			}
			$db = SERIA_Base::db();

			foreach($this->deletedProperties as $name => $value)
			{
				$db->exec('DELETE FROM {property_list} WHERE name=:name AND owner=:owner', array('name' => $name, 'owner' => $objectKey));
				unset($this->properties[$name]);
			}

			foreach($this->properties as $name => $value)
			{
				$value = serialize($value);
				try
				{
					SERIA_Base::db()->exec('INSERT INTO {property_list} (className, owner, name, value) VALUES (:className, :owner, :name, :value)', array("className"=>get_class($this->object), "owner"=>$objectKey, "name"=>$name, "value"=>$value));
				} catch (PDOException $e) { // probably already exists
					if($e->getCode() == '23000')
						SERIA_Base::db()->exec('UPDATE {property_list} SET value=:value WHERE owner=:owner AND name=:name', array("value"=>$value, "owner"=>$objectKey, "name"=>$name));
					else
						throw $e;
				}
				$this->properties = array();
			}
			return true;
		}
		
		public function get($name)
		{
			if(isset($this->properties[$name]))
			{
				return $this->properties[$name];
			}
			else
			{
				try 
				{
					$objectKey = serialize($this->object->getObjectId());
				}
				catch (SERIA_Exception $e)
				{
					return $this->properties[$name] = NULL;
				}
				$res = SERIA_Base::db()->query('SELECT value FROM {property_list} where owner=:owner AND name=:name', array('owner' => $objectKey, 'name' => $name))->fetch(PDO::FETCH_COLUMN);
				if($res)
					return $this->properties[$name] = unserialize($res);
				return NULL;
			}
		}

		public function delete($name)
		{
			return $this->deletedProperties[$name] = true;

		}

		static function query($className, $name, $value)
		{
			$db = SERIA_Base::db();
			$results = array();
			foreach ($db->query('SELECT owner FROM `'.SERIA_PREFIX.'_property_list` WHERE className='.$db->quote($className).' AND name='.$db->quote($name).' AND value='.$db->quote(serialize($value)))->fetchAll(PDO::FETCH_COLUMN,0) as $owner) {
				try {
					$object = SERIA_NamedObjects::getInstanceOf($owner);
					$results[] = $object;
				} catch (SERIA_NotFoundException $e) {
					/* Maybe the list should be cleaned. Can't do anything else than ignoring here. */
					SERIA_Base::debug('Exception: '.$e->getMessage().' (ignored)');
					SERIA_Base::debug('Named object not found: '.$owner);
				}
			}
			return $results;
		}

		function deleteFromAll($propertyNameQuery)
		{
			$db = SERIA_Base::db();
			return $db->exec('DELETE FROM {property_list} WHERE name LIKE :name', array('name' => $propertyNameQuery));
		}
		public static function deleteAll(SERIA_NamedObject $object)
		{
			SERIA_Base::db()->exec('DELETE FROM {property_list} WHERE owner=:owner', array('owner' => serialize($object->getObjectId())));
		}

		public function fetchAll()
		{
			try 
			{
				$objectKey = serialize($this->object->getObjectId());
				$all = SERIA_Base::db()->query('SELECT name,value FROM {property_list} WHERE owner = :owner', array('owner' => $objectKey))->fetchAll(PDO::FETCH_NUM);
				$properties = array();
				foreach ($all as $p)
					$properties[$p[0]] = unserialize($p[1]);
				foreach ($this->deletedProperties as $name => $value) {
					if (isset($properties[$name]))
						unset($properties[$name]);
				}
				foreach($this->properties as $name => $value)
					$properties[$name] = $value;
				return $this->properties = $properties;
			}
			catch (SERIA_Exception $e)
			{
				return $this->properties;
			}
		}
	}

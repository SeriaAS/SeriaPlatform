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
	}

?>

<?php
	require_once("../../main.php");

	class A
	{
		static $SORM = array(
			'table' => 'a',
			'pk' => 'id',			
			'map' => array(
				// property a maps into column id in the table
				'a' => 'id',							

				// property b maps into column authorId in the table
				'b' => 'authorId',
			),
			'relationships' => array(
				// relationship visitors is an array of User object instances, and is a many-to-many relationship stored in the department_visitors table
				'visitors' => array(SORM::MANY_TO_MANY, 'User', array('department_visitors', 'departmentId', 'userId'))
			),
		);

		public $a, $b, $c;

		function __construct($a, $b)
		{
			$this->a = $a;
			$this->b = $b;
			$this->c = $a + $b;
		}

		function __wakeup()
		{
			$this->c = $this->a + $this->b;
		}
	}

	class SORM
	{
		const DIRECT = 1;
		const SERIALIZED = 2;

		/**
		*	Internal cache for the SORM library
		*/
		private $cache = array(
			'__wakeup' => array(),
		);

		/**
		*	All SORM objects must specify a class variable $SORM with the following format:
		*
		*	class::$SORM = array(
		*		"map" => array( // maps from database column to property name
		*			"tableColumn" => "propertyName",
		*			"tableColumn" => array("propertyName",SORM::DIRECT),
		*				// direct means that the value from the database is directly mapped to the column
		*			"tableColumn" => array("propertyName",SORM::SERIALIZED),
		*				// serialized means that the value from the database is unserialized before setting it in the specified property
		*		),
		*	);
		*/
		function getConfig($mixed)
		{
			if(is_object($mixed))
				$mixed = get_class($mixed);

			eval('if(!isset('.$mixed.'::$SORM)) throw new Exception(\'Class '.$mixed.' does not have the $SORM static property.\'); $config = '.$mixed.'::$SORM;');
			return $config;
		}

		/**
		*	Creates an object of the specified class using values from a database row, where
		*	class::$SORM is read for configuration.
		*/
		function unfreeze($className, $databaseRow, $lazy = true)
		{
			// check if we have cached the results of the __wakeup-existence check
			if(!isset($this->cache['__wakeup'][$className]))
			{ // never been checked before, look for __wakeup method using PHP Reflection
				$class = new ReflectionClass($className);
				$found = false;
				foreach($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
				{
					if(strtolower($method->name) === '__wakeup')
					{
						$found = true;
						// __wakeup must not be called, so we create a special class that extends the actual class and overrides __wakeup
						eval("class SORM_TMP_".$className." extends $className {function __wakeup() {}}"); 
						break;
					}
				}
				$this->cache['__wakeup'][$className] = $found;
			}

			// create empty object
			if($this->cache['__wakeup'][$className])
			{ // this class has __wakeup defined. Approximately 3 times slower, since we need to do a special trick.
				$tmpClassName = 'SORM_TMP_'.$className;
				$empty = unserialize(sprintf('O:%d:"%s":0:{}', strlen($tmpClassName), $tmpClassName));
			}
			else
			{
				$empty = unserialize(sprintf('O:%d:"%s":0:{}', strlen($className), $className));
			}

			// populate object with data from the database
			$empty->a = 1;
			$empty->b = 2;

			// if we removed the wakeup method, we must go back to the original class then call __wakeup
			if($this->cache['__wakeup'][$className])
			{
				$s = serialize($empty);
				$s = sprintf('O:%d:"%s":', strlen($className), $className).substr($s, 6+strlen($tmpClassName.strlen($tmpClassName)));
				return unserialize($s);
			}
			else
			{
				return $empty;
			}
		}

		/**
		*	Retrieves all database mirrored values and returns a $row array representing
		*	the database row, according to the class::$SORM configuration array.
		*/
		function freeze($object)
		{
			$row = array();
			$config = $this->getConfig($object);
			$properties = $this->getProperties($object);
			foreach($config['map'] as $property => $propdef)
			{
				if(!isset($properties[$property]))
					throw new Exception('Property '.$propdef.' not found in object of class \''.get_class($object).'\'.');

				if(is_string($propdef))
				{ // simple direct map
					$row[$column] = $properties[$property];
				}
				else if(is_array($propdef))
				{
					switch($propdef[1])
					{
						case SORM::DIRECT : 
							$row[$column] = $properties[$propdef[0]];
							break;
						case SORM::SERIALIZED :
							$row[$column] = serialize($properties[$propdef[0]]);
							break;
						default :
							throw new Exception('Unknown property type \''.$propdef[1].'\' in class \''.get_class($object).'\'.');
					}
				}
				else
				{
					throw new Exception('Property '.$propdef.' has an invalid configuration in class \''.get_class($object).'\'.');
				}
			}
			return $row;
		}

		/**
		*	Retrieve all property values in the object, as returned by var_export. Could use Reflection, but it is not supported
		*	until PHP 5.3.
		*/
		function getProperties($object)
		{
			$exported = var_export($object, true);

			$start = strpos($exported, "\n");
			$length = strlen($exported)-$start-1;

			$exported = 'array('.substr($exported, $start, $length);
			eval('$exported = ' . $exported . ';');
			return $exported;
		}
	}

	/**
	*	A class mimicing an array, to support lazy loading of related posts - when accessed
	*/
	class SORM_ArrayProxy
	{
	}

	/**
	*	A proxy class to support lazy loading of single objects - when accessed
	*/
	class SORM_ObjectProxy
	{
		private $className, $id, $object;

		function __construct($className, $id)
		{
			$this->id = $id;
			$this->className = $className;
		}
	}

	$orm = new SORM(array());

	$a =  new A(1,2);

echo "OBJECT A:\n";
print_r($a);

	$t = microtime(true);
	for($i = 0; $i < 10000; $i++)
	{
		$b = $orm->freeze($a);
	}

echo "TIME: ".(microtime(true)-$t)." ";
echo "SEND TO DATABASE:\n";
print_r($b);

	$t = microtime(true);
	for($i = 0; $i < 10000; $i++)
	{
		$c = $orm->unfreeze('A', $b);
	}
echo "TIME: ".(microtime(true)-$t)." ";
echo "OBJECT CREATED FROM DATABASE:\n";
print_r($c);

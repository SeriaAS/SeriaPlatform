<?php
	class SERIA_ActiveRecordInterfaceHandler {
		private $name;
		private $cachedir;
		
		public static function getPlural($singular) {
			if ($singular[strlen($singular) - 1] == 's') {
				return $singular . 'es';
			}
			if ($singular[strlen($singular) - 1] == 'y') {
				return substr($singular, 0, strlen($singular) - 1) . 'ies';
			}
			return $singular . 's';
		}
		public static function getSingular($plural) {
			if (substr($plural, strlen($plural)-3, 3) == 'ies') {
				return substr($plural, 0, strlen($plural) - 3) . 'y';
			}
			
			if (substr($plural, strlen($plural)-2, 2) == 'es') {
				return substr($plural, 0, strlen($plural)-2);
			}
			
			return substr($plural, 0, strlen($plural)-1);
		}
		
		public function __construct($name) {
			$this->name = $name;
			$this->cachedir = SERIA_ROOT . '/files/';
		}
		
		public static function autoloadHandler($className) {
			
			// Only enabled if class name ends with 's''
			if ($className[strlen($className)-1] != 's') {
				return false;
			}
			
			$singleClassName = self::getSingular($className);
			
			// To support single class names ending with 'e'
			if ($className[strlen($className) - 2] == 'e') {
				if (!class_exists($singleClassName)) {
					$singleClassName .= 'e';
				}
			}
			
			if (!class_exists($singleClassName)) {
				return false;
			}
			
			// There is a class name with non-plural class name
			// Proceed with class generation if class is subclass of activerecord
			if (property_exists($singleClassName, 'isActiveRecord')) {
				$handler = new SERIA_ActiveRecordInterfaceHandler($singleClassName);
				$handler->createClass();
				
				return true;
			}
			
			return false;
		}
		
		// Generates a dynamic class with plural name of original model class name
		// The generated class will contain methods for all find_-queries
		// The methods will be passed dynamically to activerecord
		// if PHP version >= 5.3
		private function createClass() {
			$classname = self::getPlural($this->name);
			
			$recordObject = new $this->name();
			$columns = $recordObject->getColumns();
			if (!sizeof($columns)) {
				throw new Exception('No columns found for ' . $classname);
			}
			
			$prefixes = array(
				'find_all_by_',
				'find_first_by_',
				'find_last_by_'
			);
			
			$methods = array('find_all', 'find', 'find_first', 'find_last');
			
			foreach ($prefixes as $prefix) {
				foreach ($columns as $column) {
					$column = preg_replace('/[^a-z0-9_]/i', '', $column);
					$methods[] = $prefix . $column;
				}
			}
			
			$classDef  = '';
			$classDef .= 'class ' . $classname . ' extends SERIA_ActiveRecordInterface {' . "\n";
			
			$classDef .= 'public static function __callStatic($method, $arguments) {' . "\n" .
			             '  ' . $this->name . '::$method($arguments);' . "\n" .
			             '}' . "\n"; 
			
			// Create a method definition for all "dynamic" static methods
			foreach ($methods as $method) {
				$classDef .= 'public static function ' . $method . '() {' . "\n";
				$classDef .= '  $object = self::getInstanceOf(\'' . addslashes($this->name) . '\');' . "\n";
				$classDef .= '  $arguments = func_get_args();' . "\n";
				$classDef .= '  $arguments += array(\'tableName\', \'' . addslashes($this->name) . '\');' . "\n";
				$classDef .= '  return $object->_callStatic(\'' . addslashes($method) . '\', $arguments); ' . "\n";
				$classDef .= '}' . "\n";
			}
			
			$classDef .= '}' . "\n";
			
			// This code will delete the class definition and recreate it if INSTALL mode is enabled.
			$bootstrapCode = '
				if (SERIA_INSTALL || (mt_rand(0,10) > 9)) {
					try {
						unlink(__FILE__);
					} catch (ErrorException $e) {
						/*
						 * SERIA_Base:errorHandler throws an exception if errors occur on unlink.
						 * The FALSE return does not work.
						 */
						if (strpos($e->getMessage(), \'No such file\') === false)
							throw $e;
					}
					class_exists(\'' . $classname . '\');
				}
			';
			
			try {
				$classFilename = SERIA_DYNAMICCLASS_ROOT . '/' . $classname . '.activerecord.php';
				file_put_contents($classFilename, '<?php ' . $bootstrapCode . ' ' . $classDef);
				
				require($classFilename);
			} catch (Exception $exception) {
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, 'Saving of Active record class ' . $classname . ' in file ' . $classFilename . ' failed: ' . $exception->getMessage(), 'system', 'save_' . $classname);
				eval($classDef);
			}
		}
	}
?>

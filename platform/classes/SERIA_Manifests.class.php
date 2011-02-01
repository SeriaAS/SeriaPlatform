<?php
/**
*	Class for working with Manifest classes in Seria Platform
*/
class SERIA_Manifests {
	protected static $_manifests = array();

	/**
	*	Returns the reflection object for the class corresponding to the manifest,
	*	assuming that the manifest have been loaded.
	*
	*	@param $name	The name of the manifest class, excluding the "Manifest"-suffix
	*	@returns ReflectionClass
	*/
	public static function getManifest($name)
	{
		if(!isset(self::$_manifests[$name]))
			return NULL;
		return self::$_manifests[$name];
	}

	/**
	*	Returns all reflection objects for the manifest classes.
	*	@return array
	*/
	public static function getAllManifests()
	{
		return self::$_manifests;
	}

	/**
	*	This method processes manifest classes that are declared
	*	for each component and application. It updates the database structure
	*	and logs which tables are created by which component, so that uninstallation
	*	is possible in the future.
	*
	*	********************************************************
	*	SERIAL must increase by one for every change you do!!!!!
	*	********************************************************
	*
	*	Format of a basic manifest file:
	*	class ComponentNameManifest {
	*		static SERIAL = 5; 			// the serial number is used to identify which version this file is
	*		static NAME = 'appName';		// an identifier that will be used in urls and menu structure for referencing your application
	*		public static $classPaths = array(	// an array of paths relative to the folder where the Manifest is defined
	*			'classes/*.class.php',
	*		);
	*		public static $menu = array(		// an array containing information about every administrative menu item that this application provides
	*			'appName' => array(		// the application name should be represented in a menu item named exactly the same as the static NAME property
	*				'title' => 'Title',	// the menu item title. Do not use the translation function here, it will be used automatically
	*				'description' => 'Desc',// the menu item description. Do not use the translation function.
	*				'weight' => 1000,	// [optional] decides the ordering of elements on the same level in the menu structure.
	*				'icon' => 'http://url',	// [optional] complete path to an image to be used whenever needed.
	*			),
	*			'appName/edit' => array(
	*				'title' => 'Title',	// menu item title
	*				'description' => 'Desc',// menu item description
	*			),
	*		);
	*		public static $database = array(	// declares the database structure
	*			'creates' => array(		// a list of create table statements. NOTE! Normally you simply modify this to alter your datamodel
	*				'CREATE TABLE {mytable} (id INTEGER PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8',
	*			),
	*			'drops' => array(		// a list of tables to drop before parsing the 'creates'
	*				2 => array(		// the serial number to perform this for
	*					'{oldtable}',
	*				),
	*			),
	*			'alters' => array(		// a list of alter table statements to perform before parsing the 'creates'
	*				4 => 'ALTER TABLE {mytable} DROP COLUMN name',
	*			),
	*		);
	*	}
	*/
	static function processManifests($namespace, array $classNames)
	{
		sort($classNames);
		$hash = '';
		$versions = array();
		$reflectors = array();
		foreach($classNames as $className)
		{
			$reflector = $reflectors[$className] = new ReflectionClass($className);
			$serial = $reflector->getConstant('SERIAL');
			if(!$serial)
				throw new SERIA_Exception('The manifest class "'.$className.'" does not specify the SERIAL constant as a positive integer.');

			$name = $reflector->getConstant('NAME');
			if(!$name)
				throw new SERIA_Exception('The manifest class "'.$className.'" does not specify the NAME constant. This constant is used to identify your manifest and create nice admin urls.');
			if(isset(self::$_manifests[$name]))
				throw new SERIA_Exception('The manifest class "'.$className.'" has a name that have been used before. This is illegal.');
			self::$_manifests[$name] = $reflector;
			$hash .= '('.$serial.')';
		}
		foreach($reflectors as $className => $reflector)
		{
			$path = dirname($reflector->getFileName());
			// Manifest::$classPaths
			try {
				$classPaths = $reflector->getStaticPropertyValue('classPaths');
				if($classPaths) foreach($classPaths as $subPath)
				{
					SERIA_Base::addClassPath($path.'/'.$subPath);
				}
			}
			catch (ReflectionException $null) {}
		}
		$currentHash = SERIA_Base::getParam('manifestversions:'.$namespace);
		if($currentHash !== $hash)
			$changed = true; // must check if the $hash have changed
		else
			$changed = false;
		if($changed)
		{
			SERIA_Base::debug('One or more "'.$namespace.'"-manifests have changed. Performing update.');
			// validate the manifest classes
			foreach($reflectors as $reflector)
			{
				// validate doc comment
				$docComment = $reflector->getDocComment();
				if($docComment===false)
					throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" does not specify a doc comment (/** */) which is required.');
				// keywords to look for in doc-comment:
				$keywords = array('@author','@package');
				foreach($keywords as $keyword)
					if(strpos($docComment, $keyword)===false)
						throw new SERIA_Exception('The doc comment (/** */) for the class "'.$reflector->getName().'" does not specify the "'.$keyword.'" keyword. Must use the following keywords: '.implode(", ", $keywords));
				// validate constants
				$constants = $reflector->getConstants();
				foreach($constants as $c => $v)
				{
					// REQUIRED CONSTANT
					if($c == 'SERIAL')
					{
					}
					else if ($c == 'NAME')
					{
					}
					else if(strtoupper($c) != $c)
					{
						throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the constant "'.$c.'" which does not follow guidelines (must be uppercase).');
					}
					else if(substr($c, -5) == '_HOOK' && $v == $reflector->getName()."::".$c)
					{ // hooks must end with _HOOK and contain a value identical to its token name in PHP
					}
					else
					{ // all other constants are illegal for the manifest class
						throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the constant "'.$c.'" which does not follow guidelines (only a few constants are allowed here).');
					}
				}
				// validate methods
				$methods = $reflector->getMethods();
				foreach($methods as $method)
				{
					if($method->isStatic() && $method->isPublic())
					{ // public static methods
						if($method->getName() == 'beforeUpgrade' && $method->getNumberOfRequiredParameters()==2);
						else if($method->getName() == 'afterUpgrade' && $method->getNumberOfRequiredParameters()==2);
						else
							throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not follow guidelines.');
					}
					else
						throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not follow guidelines (only a few methods are allowed here).');
					$docComment = $method->getDocComment();
					if($docComment === false)
					{
						throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not have a doc comment.');
					}
				}
			}
			/**
			*	Algorithm to prevent accessing site and concurrent updates while manifests are being processed:
			*
			*	* Try to lock manifestprocessing
			*	* If not able to lock manifestprocessing, check if more than 60 seconds since locked and try to lock manifestprocessing again
			*	* If manifestprocessing not locked, wait 1 seconds and see if it finished - die if not.
			*/
			$locked = SERIA_Base::insertParam('manifestprocessing', time());
			if($locked && $locked < time()-10)
			{
				$locked = SERIA_Base::replaceParam('manifestprocessing', time(), $locked);
			}
			if($locked)
			{
				sleep(1);
				$locked = SERIA_Base::getParam('manifestprocessing');
				if($locked) // still locked, die to prevent processes filling up
					SERIA_Base::displayErrorPage('500', 'Site is being updated', 'An update to the website is being processed, and this is taking more time than expected. Please try again by pressing the reload button.');
				else // manifests have been processed
					return;
			}
			// database connection will be required
			$db = SERIA_Base::db();
			// manifest processing must happen, even on empty databases - so we create the tables here. Should give no performance penalty, since they are not called
			// unless manifests have chagned.
			/**
			*	Record known manifests, so that we can uninstall them if the class is deleted
			*/
			$db->exec("CREATE TABLE IF NOT EXISTS {manifests} (name VARCHAR(100) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			/**
			*	Record database tables created by manifests, so that they can be deleted later, and we can detect if other manifests use the same table name
			*/
			$db->exec("CREATE TABLE IF NOT EXISTS {manifests_tables} (name VARCHAR(100) PRIMARY KEY, manifestName VARCHAR(100)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			/**
			*	Record folders created by manifests, so that they can be deleted later
			*/
			$db->exec("CREATE TABLE IF NOT EXISTS {manifests_folders} (id INTEGER PRIMARY KEY AUTO_INCREMENT, pathName VARCHAR(100), path VARCHAR(100), manifestName VARCHAR(100)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			/**
			*	Record updates of manifest serial numbers, so that we simply can keep track and possibly identify sources of problems
			*/
			$db->exec("CREATE TABLE IF NOT EXISTS {manifests_logs} (id INTEGER PRIMARY KEY AUTO_INCREMENT, category VARCHAR(20), title VARCHAR(100), info BLOB, manifestName VARCHAR(100), serial INTEGER, targetSerial INTEGER) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			// Safety measure: make sure that manifestversions for this namespace must be updated some time, even if this fails
			SERIA_Base::unsetParam('manifestversions:'.$namespace);
			foreach($reflectors as $className => $reflection)
			{
				$newVersion = $reflection->getConstant('SERIAL');
				$installedVersion = SERIA_Base::getParam('manifest:'.$className.':serial');
				if($installedVersion===NULL) $installedVersion = 0;
				if($newVersion>$installedVersion)
				{
/**
*	Synchronize database definition
*/
					try {
						$database = $reflection->getStaticPropertyValue('database');
						// perform custom alter tables and drop tables for this manifest
						if($installedVersion>0)
						{ // do not drop or alter tables for new manifests
						for($i = $installedVersion+1; $i <= $newVersion; $i++)
							{
								if(isset($database['drops']) && isset($database['drops'][$i]))
								{ // there are drop table instructions for this version
									foreach($database['drops'][$i] as $tableName)
									{
										$owner = $db->query("SELECT * FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
											$tableName, $className
										))->fetch(PDO::FETCH_ASSOC);
										if($owner)
										{
//TODO: dangerous! Consider renaming the table temporarily
											$db->exec($statement = 'DROP TABLE '.$tableName, NULL, true);
											$db->exec("DELETE FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
												$tableName,
												$className
											), true);
											$db->exec('INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial) VALUES (?,?,?,?)', array(
												'drop',
												'Dropped the database table "'.$tableName.'".',
												$className,
												$i,
												$newVersion,
												serialize(array('sql' => $statement)),
											), true);
										}
										else throw new SERIA_Exception('The manifest class "'.$className.'" tried to drop the table "'.$tableName.'" which it does not own!');
									}
								}
								if(isset($database['alters']) && isset($database['alters']))
								{ // there are alter table instructions for this version
									foreach($database['alters'][$i] as $statement)
									{
										$owner = $db->query("SELECT * FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
											$tableName, $className
										))->fetch(PDO::FETCH_ASSOC);
										if($owner)
										{
											$db->exec($statement, NULL, false);
											$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial, info) VALUES (?,?,?)", array(
												'alter',
												'Altered the database table "'.$tableName.'".',
												$className,
												$i,
												$newVersion,
												serialize(array('sql' => $statement)),
											), true);
										}
										else throw new SERIA_Exception('The manifest class "'.$className.'" tried to alter the table "'.$tableName.'" which it does not own!');
									}
								}
							}
						}
						else
						{ // this is a new manifest
							SERIA_Base::setParam('manifest:'.$className.':serial', $newVersion);
							$db->exec("INSERT INTO {manifests} (name) VALUES (?)", array($className));
							$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetserial) VALUES (?,?,?,?,?)", array(
								'install',
								'First installation of the "'.$className.'" manifest.', 
								$className,
								$newVersion,
								$newVersion,
							));
						}
//todo check altered table type!
						// synchronize table definitions with actual database
						if(isset($database['creates']))
						{
							foreach($database['creates'] as $create)
							{
								// create temporary table,
								preg_match_all('|{([a-zA-Z0-9_]+)}|m', $create, $matches);
								$matchCount = sizeof($matches[0]);
								if($matchCount === 0)
								{ //TODO: Parse SQL using SERIA_DB::sqlTokenize and support create table statements without {} around table names
									throw new SERIA_Exception('The create statement "'.$create.'" in the manifest class "'.$className.'" does not enclose its table names in curly brackets {} so I can\'t perform synchronization.');
								}
								else if($matchCount > 1)
								{ // Unlikely scenario
									throw new SERIA_Exception('The create statement "'.$create.'" in the manifest class "'.$className.'" contains multiple tables within curly brackets, and I do not know how to process it.');
								}
								$sql = str_replace($matches[0][0], '{tmp_manifest}', $create);
								$pos = stripos($sql, 'TABLE');
								$sql = substr($sql, 0, $pos).' TEMPORARY '.substr($sql, $pos);
								$db->exec($sql);
								// compare table with the current table,
								try {
									$specOriginal = $db->getColumnSpec($matches[0][0]);
								} catch (PDOException $e) {
									if($e->getCode()=="42S02")
									{ // table does not exist, so we accept the create statement directly
										$db->exec('DROP TABLE {tmp_manifest}');
										$db->exec($create);
										$db->exec("INSERT INTO {manifests_tables} (name, manifestName) VALUES (?,?)", array(
											$matches[0][0],
											$className,
										), true);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'install',
											'Created table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $create)),
											$newVersion,
											$newVersion
										));
										continue;
									}
									throw $e;
								}
								$specNew = $db->getColumnSpec('{tmp_manifest}');
								$dropped = array();
								$added = array();
								// are all columns in original also in new?
								foreach($specOriginal as $column => $spec)
								{
									if(!isset($specNew[$column]))
									{ // column seems to be dropped, but we can't be sure yet
										$dropped[] = $column;
									}
								}
								// are all columns in new, also in original
								foreach($specNew as $column => $spec)
								{
									if(!isset($specOriginal[$column]))
									{
										$added[] = $column;
									}
								}
								// alter the current table to match the temporary table,
								if(sizeof($dropped)>0 && sizeof($added)>0)
								{ // columns may have been renamed, or dropped and added. We don't know.
									throw new SERIA_Exception('Unable to detect if changes to database schema is renaming of fields or adding and removing fields. Create alter statements for manifest class "'.$className.'".');
								}
								else if(sizeof($dropped)>0)
								{ // columns have been dropped
									foreach($dropped as $column)
									{
										$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' DROP COLUMN '.$column);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'alter',
											'Dropped column "'.$column.'" from the table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $statement)),
											$newVersion,
											$newVersion
										));
									}
								}
								else if(sizeof($added)>0)
								{
									foreach($added as $column)
									{
										$spec = $specNew[$column];
										$coldef = $column.' '.$spec['type'];
										if(!empty($spec['length']))
											$coldef .= '('.$spec['length'].')';
										if(!$spec['null'])
											$coldef .= ' NOT NULL';
										if($spec['default']!==NULL)
											$coldef .= ' DEFAULT '.$db->quote($spec['default']);
										else
											$coldef .= ' DEFAULT NULL';
										$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' ADD COLUMN '.$coldef);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'added',
											'Added the column "'.$column.'" to the table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $statement)),
											$newVersion,
											$newVersion,
										));
									}
								}
								// updated altered columns
								foreach($specNew as $column => $spec)
								{
									if(isset($specOriginal[$column]))
									{
										$identical = true;
										foreach($specOriginal[$column] as $fieldName => $fieldValue)
										{
											if(!isset($specNew[$column][$fieldName]) || $specNew[$column][$fieldName] != $fieldValue)
											{
												// NULL === NULL returns false, so we check extra for this.
												if(!($fieldName=='default' && $fieldValue===NULL && $specNew[$column][$fieldName]===NULL))
													$identical = false;
											}
										}
										if(!$identical)
										{
											$sql = 'ALTER TABLE '.$matches[0][0].' MODIFY COLUMN '.$column.' '.$spec['type'];
											if(!empty($spec['length']))
												$sql .= '('.$spec['length'].')';
											if(!$spec['null'])
												$sql .= ' NOT NULL';
											if($spec['default']!==NULL)
												$sql .= ' DEFAULT '.$db->quote($spec['default']);
											else
												$sql .= ' DEFAULT NULL';
											$db->exec($sql);
											$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
												'altered',
												'Altered column "'.$column.'" in the table "'.$matches[0][0].'"',
												$className,
												serialize(array('sql' => $sql)),
												$newVersion,
												$newVersion,
											));
										}
									}
								}
								// check if the indexes have changed
								$originalIdx = self::_manifestBuildSqlIndexStatements($db->query('SHOW INDEXES FROM '.$matches[0][0])->fetchAll(PDO::FETCH_ASSOC));
								$newIdx = self::_manifestBuildSqlIndexStatements($db->query('SHOW INDEXES FROM {tmp_manifest}')->fetchAll(PDO::FETCH_ASSOC));
								$added = array();
								$dropped = array();
								$modified = array();
								foreach($newIdx as $name => $sql)
								{
									if(!isset($originalIdx[$name]))
										$added[] = $name;
									else if($originalIdx[$name]!=$sql)
										$modified[] = $name;
								}
								foreach($originalIdx as $name => $sql)
								{
									if(!isset($newIdx[$name]))
										$dropped[] = $name;
								}
								foreach($dropped as $name)
								{
									$db->exec($sql = 'ALTER TABLE '.$matches[0][0].' DROP INDEX '.$name);
									$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
										'drop_index',
										'Dropped the index "'.$name.'" from the table "'.$matches[0][0].'"',
										$className,
										serialize(array('sql' => $sql)),
										$newVersion,
										$newVersion,
									), true);
								}
								foreach($added as $name)
								{
									$db->exec($sql = 'ALTER TABLE '.$matches[0][0].' ADD '.$newIdx[$name]);
									$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
										'added_index',
										'Added the index "'.$name.'" to the table "'.$matches[0][0].'"',
										$className,
										serialize(array('sql' => $sql)),
										$newVersion,
										$newVersion,
									), true);
								}
								foreach($modified as $name)
								{
									$db->exec('ALTER TABLE '.$matches[0][0].' DROP INDEX '.$name);
									$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' ADD '.$newIdx[$name]);
									$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
										'alter_index',
										'Altered the index "'.$name.'" in the table "'.$matches[0][0].'"',
										$className,
										serialize(array('sql' => $statement)),
										$newVersion,
										$newVersion,
									), true);
								}
								// drop the temporary table
								$db->exec('DROP TABLE {tmp_manifest}');
							}
						}
						$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial) VALUES (?,?,?,?,?)", array(
							'update_manifest',
							'Synchronized the manifest for "'.$className.'"',
							$className,
							$newVersion,
							$newVersion,
						), true);
						SERIA_Base::debug('Updated manifest for "'.$className.'" to version '.$newVersion);
						SERIA_Base::setParam('manifest:'.$className.':serial', $newVersion);
					}
					catch (ReflectionException $e)
					{
					}
/**
*	Synchronize folder definitions
*/

				}
				else if($newVersion<$installedVersion)
				{
					throw new SERIA_Exception('Downgrading serial number for manifest class "'.$className.'" is not supported.');
				}
			}
			// stop others from processing manifests
			SERIA_Base::setParam('manifestversions:'.$namespace, $hash);
			// release lock
			SERIA_Base::setParam('manifestprocessing', 0);
		} // changed
	}

	/**
	*	Accepts all rows from the mysql SHOW INDEXES FROM {table} and
	*	returns an associative array of $indexName => $indexDef for use
	*	in ALTER TABLE {table} ADD $indexDef
	*	@param array $indexDef	Array of associative rows from SHOW INDEXES FROM {table}
	*/
	protected static function _manifestBuildSqlIndexStatements(array $indexDef)
	{
// currently no support for fulltext or spatial indexes.
// no support for foreign keys, and anyway we do not encourage using them
		$result = array();
		$keyNames = array();
		foreach($indexDef as $row)
			$keyNames[$row['Key_name']] = $row['Key_name'];
		foreach($keyNames as $keyName)
		{
			if($keyName === 'PRIMARY')
			{
				$sql = 'PRIMARY KEY';
			}
			else
			{ // UNIQUE OR INDEX?
				foreach($indexDef as $row)
				{
					if($row['Key_name'] === $keyName)
					{
						if($row['Non_unique'])
							$sql = 'INDEX';
						else
							$sql = 'UNIQUE INDEX';
						break;
					}
				}
				$sql .= ' '.$keyName;
			}

			// columns
			$colNames = array();
			foreach($indexDef as $row)
			{
				if($row['Key_name'] === $keyName)
				{
					$colNames[$row['Seq_in_index']] = $row['Column_name'].($row['Sub_part']!==NULL?'('.$row['Sub_part'].')':'').' '.($row['Collation']=='A'?'ASC':'DESC');
				}
			}
			ksort($colNames);
			$sql .= ' ('.implode(",", $colNames).')';

			// index type
			foreach($indexDef as $row)
			{
				if($row['Key_name'] === $keyName)
				{
					$sql .= ' USING '.$row['Index_type'];
					break;
				}
			}
			$result[$keyName] = $sql;
		}
		return $result;
	}
}

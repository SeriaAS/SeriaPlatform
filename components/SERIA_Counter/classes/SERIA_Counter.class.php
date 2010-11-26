<?php

	class SERIA_Counter {
		protected $_namespace;
		protected static $_batchUpdates = array();
		protected static $_instances = 0;

		function __construct($namespace)
		{
			$this->_namespace = $namespace;
			self::$_instances++;
		}

		function add(array $counterNames, $increment=1)
		{
			$values = array();
			foreach($counterNames as $counterName)
			{
				$name = $this->_namespace.":".$counterName;
				if(isset(self::$_batchUpdates[$name]))
					self::$_batchUpdates[$name] += $increment;
				else
					self::$_batchUpdates[$name] = $increment;
			}
		}

		// the destructor
		function __destruct()
		{
			self::$_instances--;
			if(self::$_instances > 0)
				return;
			$db = SERIA_Base::db();
			// create a reverse array to batch together updates
			$reverse = array();
			foreach(self::$_batchUpdates as $name => $increment)
			{
				if(isset($reverse[$increment]))
					$reverse[$increment][] = $name;
				else
					$reverse[$increment] = array($name);
			}
			self::$_batchUpdates = array();

			// create a single statement to update the database, for each increment-value
			foreach($reverse as $increment => $names)
			{
				$values = array();
				foreach($names as $name)
					$values[] = "(".$db->quote($name).",".intval($increment).")";

				$sql = "INSERT INTO {counters_memory} VALUES ".implode(",", $values)." ON DUPLICATE KEY UPDATE counter=counter+".intval($increment);
				try {
					SERIA_Base::db()->exec($sql, NULL, true);
				} catch (PDOException $e) {
					if($e->getCode()==="42S02")
					{ // table does not exist, create it
						SERIA_Base::db()->exec("CREATE TABLE {counters_memory} (id VARCHAR(100), counter BIGINT, PRIMARY KEY(id)) ENGINE = MEMORY DEFAULT CHARSET utf8", NULL, true);
						SERIA_Base::db()->exec("CREATE TABLE {counters} (id VARCHAR(100), counter BIGINT, PRIMARY KEY(id)) ENGINE = InnoDB DEFAULT CHARSET utf8", NULL, true);
						SERIA_Base::db()->exec($sql, NULL, true);
					}
					else throw $e;
				}
			}
		}

		function get(array $counterNames)
		{
			$results = array();
			// fetch from disk table

			foreach($counterNames as $counterName)
			{
				if(!($results[$counterName] = SERIA_Base::db()->query("SELECT counter FROM {counters} WHERE id=".SERIA_Base::db()->quote($this->_namespace.":".$counterName))->fetch(PDO::FETCH_COLUMN, 0)))
					$results[$counterName] = 0;
				// fetch values from in-object cache
				if(isset(self::$_batchUpdates[$this->_namespace.":".$counterName]))
				{
					$results[$counterName] += self::$_batchUpdates[$this->_namespace.":".$counterName];
				}
			}

			// fetch from memory table and add that to the result
			foreach($counterNames as $counterName)
			{
				if($result = SERIA_Base::db()->query("SELECT counter FROM {counters_memory} WHERE id=".SERIA_Base::db()->quote($this->_namespace.":".$counterName))->fetch(PDO::FETCH_COLUMN, 0))
				{
					$results[$counterName] += $result;
				}
			}

			return $results;
		}

		public static function commitMemory()
		{
			$db = SERIA_Base::db();
			while(true)
			{ // do it in iterations
				$db->exec("LOCK TABLES {counters} WRITE,{counters_memory} WRITE", NULL, true);
				$memRows = $db->query("SELECT * FROM {counters_memory} ORDER BY id LIMIT 5000")->fetchAll(PDO::FETCH_ASSOC);
				if(sizeof($memRows)===0) return true;
				$reverse = array();
				foreach($memRows as $memRow)
				{
					if(isset($reverse[$memRow["counter"]]))
						$reverse[$memRow["counter"]][] = "(".$db->quote($memRow["id"]).",".$memRow["counter"].")";
					else
						$reverse[$memRow["counter"]] = array("(".$db->quote($memRow["id"]).",".$memRow["counter"].")");
				}
				foreach($reverse as $increment => $values)
				{
					$db->exec("INSERT INTO {counters} VALUES ".implode(",", $values)." ON DUPLICATE KEY UPDATE counter=counter+".$increment, NULL, true);
				}
				$db->exec("DELETE FROM {counters_memory} ORDER BY id LIMIT 5000", NULL, true);
				$db->exec("UNLOCK TABLES", NULL, true);
			}
		}
	}

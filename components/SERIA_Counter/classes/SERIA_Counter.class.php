<?php
	/**
	*	Class facilitating counting persistently and fast using memory tables and persistent tables.
	*	Warning! Power outages or database restarts will destroy memory tables and you may loose counts.
	*
	*	Optimizations to know about:
	*
	*	As long as you keep a reference to your SERIA_Counter instance, instances are performed in PHP memory and there should
	*	be no need for additional optimizations by external means.
	*
	*	On class destruction, PHP memory is commited to a memory based database.
	*
	*	The commitMemory() method is called every five minutes by the maintenance script to copy memory tables to persistent tables.
	*	Since the algorithm uses table locks, you should not call it yourself. In mysql, table locks starts an implicit database 
	*	commit and your transaction will become unsafe.
	*/
	class SERIA_Counter {
		protected $_namespace;
		protected static $_batchUpdates = array();
		protected static $_instances = 0;

		/**
		*	Constructor
		*
		*	@param string $namespace	A unique string grouping your counters
		*/
		function __construct($namespace)
		{
			$this->_namespace = $namespace;
			self::$_instances++;
		}

		/**
		*	Increment any number of counters by $increment
		*
		*	@param array $counterNames	An array of strings identifying each of the counters you wish to increment
		*	@param int $increment		The number you wish to increment your counters by. Negative numbers are allowed.
		*/
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
					else throw $e;
				}
			}
		}

		/**
		*	Get the value from any number of counters. If you perhaps want to graph the hits for the years 2010, 2011 and 2012
		*	you should provide array(2010,2011,2012).
		*
		*	The return value is an associative array of (countername => count)-pairs.
		*
		*	@param array $counterNames
		*	@return array
		*/
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

		/**
		*	Commit data from the {counters_memory] table to the {counters} table.
		*	WARNING! This function performs an implicit transaction commit on mysql databases because of using LOCK TABLES
		*/
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

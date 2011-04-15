<?php
	/**
	*	Wrapperclass for PDO simplifying the use of prepared statements
	*
	*	Usage is like normal PDO, except that ::query and ::exec have been slightly modified to enable simpler usage
	*	of prepared statements
	*
	*	$o->
	*/
	class SERIA_DB
	{
		protected $_db = false;
		protected $delayedTransaction = false;			// have begin transaction been called?
		const FLUSH_STATEMENT_CACHE = 1;

		protected $dsn, $user, $pass;

		function __construct($dsn, $user, $pass)
		{
			$this->dsn = $dsn;
			$this->user = $user;
			$this->pass = $pass;
		}

		function dbLog($message)
		{
			$this->_db->query("SELECT 'dbLog: $message'")->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		*	Actually connect to the databse
		*/
		function doConnect()
		{
			if($this->_db !== false)
				return;

			if(SERIA_DEBUG) SERIA_Base::debug("<strong>SERIA_DB CONNECTION STARTED</strong>");

		        try {
	        	        if(class_exists('PDO') && !defined('DISABLE_PDO'))
				{
		                        $this->_db = new PDO($this->dsn, $this->user, $this->pass);
		                }
				else
				{
					$type = substr($this->dsn, 0, $o = strpos($this->dsn, ':'));
					$rest = substr($this->dsn, $o+1);
					$parts = explode($rest,';');
					$vals = array();
					foreach($parts as $part)
					{
						$p = explode($part,"=");
						$vals[trim(strtolower($p[0]))] = trim($p[1]);
					}
					switch($type)
					{
						case 'mysql' : 
				                        require_once(SERIA_ROOT."/seria/platform/compatability/PDO_MySQLi.class.php");
							if(!isset($vals['port']))
								$vals['port'] = 3306;
							$this->_db = new PDO_MySQLi(new mysqli($vals['host'], $this->user, $this->password, $vals['dbname'], $vals['port']));
							break;
						default :
							throw new SERIA_Exception('Unknown database type "'.$type.'".');
					}
		                }
		        } catch (Exception $e) { // sometimes exceptions thrown here contain secure information such as passwords and similar.
		                throw new Exception("Unable to connect to database.");
		        }

		        /**
		        *       STANDARDIZE ENVIRONMENT
		        */
		        $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		        if($this->_db->getAttribute(PDO::ATTR_DRIVER_NAME) == "mysql") {
		                // MySQL must be told to operate in UTF-8 mode, and we tell it which timezone we work in
		                $this->_db->exec("SET NAMES utf8");
		                $this->_db->exec("SET time_zone = '".date("P")."'");
				// Will prevent a lot of deadlocks, while preserving the possibility of rollback.
				$this->_db->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
		        }
			register_shutdown_function(array("SERIA_Base","closeDB"));
		}

		protected function rewriteQuery($sql)
		{
			static $rewriteCache = array();
			if(isset($rewriteCache[$sql]))
				return $rewriteCache[$sql];

			$sqlNew = preg_replace('|{([a-zA-Z0-9_]+)}|m', SERIA_PREFIX.'_\1', $sql);
			// do not cache statements that do not use prepared statements with params, since they probably use constants and can amount to thousands of rows in the cache
			if(strpos($sqlNew, ':')!==false)
				return $rewriteCache[$sql] = $sqlNew;
			return $sqlNew;
		}

		function _prepare($statement, $params=NULL) {
			static $statements = array();

			$sql = $statement;

			if($statement === 1) //OPTIMIZATION: faster to use inline constant self::FLUSH_STATEMENT_CACHE)
			{
				$statements = false;
				return true;
			}

			
//OPTIMIZED AWAY (find preg_replace below)			$statement = $this->rewriteQuery($statement);

			if(isset($statements[$statement]))
			{
				$statement = $statements[$statement];
			}
			else
			{
				$this->doConnect();
				$statement = $statements[$statement] = $this->_db->prepare(preg_replace('|{([a-zA-Z0-9_]+)}|m', SERIA_PREFIX.'_\1', $statement)); //OPTIMIZATION: only preg_replace when needed
			}

			if($params === NULL)
				return array($statement, NULL);

			$statementParams = array();
			// Allows ? instead of :name style parameters
/*
			$allInt = true;
			foreach($params as $key => $value)
			{
				if(!is_int($key))
					$allInt = false;
			}
*/
//			if(!$allInt) foreach($params as $key => $value)
			if(isset($params[0])===false) foreach($params as $key => $value)
			{
				if($key[0]!==':') $key = ':'.$key;
				if(strpos($sql, $key)!==false)
					$statementParams[$key] = $value;
			}
			else
			{
				return array($statement, $params);
				foreach($params as $key => $value)
					$statementParams[$key] = $value;
			}

			return array($statement, $statementParams);
		}

		function exec($statement, $params=NULL, $transactionLess = false) {
			if($params !== NULL && !is_array($params))
			{
				if($params instanceof SERIA_MetaObject)
					$params = $params->MetaBackdoor('get_row');
				else if($params instanceof SERIA_FluentObject || in_array('SERIA_IFluentObject', class_implements($params)))
					$params = $params->toDB();
				else
					throw new SERIA_Exception('SERIA_DB::exec() expects array or MetaObject as parameter 2.');
			}


			if($transactionLess === false)
			{
				$tmp = substr(trim(strtoupper($statement)), 0, 6);
				if($tmp === "UPDATE" || $tmp === "DELETE" || $tmp === "INSERT" || $tmp === "REPLAC")
				{
					if($this->delayedTransaction)
						$this->beginTransaction();
				}
			}
			$this->autoCursorClose();
			try
			{
				if(SERIA_DEBUG) SERIA_Base::debug("SERIA_DB->exec($statement)");
				if($params===NULL)
				{
					$this->doConnect();
					return $this->_db->exec($this->rewriteQuery($statement));
				}

				$original = $statement;
				list($statement, $params) = $this->_prepare($statement, $params);
				$this->autoCursorClose($statement);

				if($res = $statement->execute($params))
					return $statement->rowCount();
				return false;
			} catch (PDOException $e) {
				if($e->getCode() === 'HY093')
				{ // workaround for bug in PHP/PDO; see http://framework.zend.com/issues/browse/ZF-1343
					$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
					$this->_prepare(self::FLUSH_STATEMENT_CACHE);
					list($statement, $params) = $this->_prepare($original, $params);
					$this->autoCursorClose($statement);
					SERIA_SystemStatus::publishHtmlMessage(SERIA_SystemStatus::NOTICE, 'Workaround for PHP bug <a href="http://bugs.php.net/bug.php?id=44251">44251</a> in effect. Please upgrade to PHP 5.2.8. This may have a minor impact on performance.');
					if($statement->execute($params))
						return $statement->rowCount();
					return false;
				}
				throw $e;
			}
		}

		function query($statement, $params=NULL) {
			$this->autoCursorClose();
//			$original = $statement;
			try
			{
				if($params===NULL)
				{
					if(SERIA_DEBUG) SERIA_Base::debug("SERIA_DB->query($statement) (<strong>not prepared</strong>)");
					$this->doConnect();
					return $this->autoCursorClose($this->_db->query(preg_replace('|{([a-zA-Z0-9_]+)}|m', SERIA_PREFIX.'_\1', $statement)));	//OPTIMIZATION: perform the preg_replace here instead of in $this->rewriteQuery
//					return $this->autoCursorClose($this->_db->query($this->rewriteQuery($statement)));
				}
				if(SERIA_DEBUG) SERIA_Base::debug("SERIA_DB->query($statement)");

				list($statementPrepared, $params) = $this->_prepare($statement, $params);
				$this->autoCursorClose($statementPrepared);

				$statementPrepared->execute($params);
				return $statementPrepared;
			} catch (PDOException $e) {
				if($e->getCode() === 'HY093')
				{ // workaround for bug in PHP/PDO; see http://framework.zend.com/issues/browse/ZF-1343
					$this->doConnect();
					$this->_db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, false);
					$this->_prepare(self::FLUSH_STATEMENT_CACHE);
					list($statementPrepared, $params) = $this->_prepare($statement, $params);
					$this->autoCursorClose($statementPrepared);
					$statementPrepared->execute($params);
					SERIA_SystemStatus::publishHtmlMessage(SERIA_SystemStatus::NOTICE, 'Workaround for PHP bug <a href="http://bugs.php.net/bug.php?id=44251">44251</a> in effect. Please upgrade to PHP 5.2.8. This may have a minor impact on performance.');
				}
				else throw $e;
			}
		}

		function autoCursorClose($nextStatement=null)
		{
			// will close any open statements automatically, preventing the unbuffered queries open while querying exception introducing the risk that queries have not completed
			static $openStatements = array();

//Benchmarked: Unprepared Time: 6.81088399887 Prepared Time: 7.1397550106
			while($s = array_shift($openStatements))
				$s->closeCursor();

/*Benchmarked: Unprepared Time: 7.12115812302 Prepared Time: 7.16245698929
			foreach($openStatements as $k => $s)
			{
//echo $k.", ";
				$s->closeCursor();
				unset($openStatements[$k]);
			}
*/

			if ($nextStatement !== null)
			{
				$openStatements[] = $nextStatement;
				return $nextStatement;
			}
		}

		function quote($string, $parameter_type = PDO::PARAM_STR) 
		{
			$this->doConnect();
			return $this->_db->quote($string, $parameter_type);
		}

		/**
		*	Direct mapping of commands from the real PDO object:
		*/
		function beginTransaction($delayed=false)
		{
			if($delayed)
			{ // should start transaction once (if) SERIA_Base::exec is called
				if($this->delayedTransaction)
					throw new SERIA_Exception("Nested transactions not supported");

				$this->delayedTransaction = true;
				return true;
			}
			else if($this->delayedTransaction)
			{ // we are actually starting the transaction
				$this->delayedTransaction = false;
			}

			$this->doConnect();
			if(SERIA_DEBUG) SERIA_Base::debug("<strong>SERIA_DB->beginTransaction()</strong>");
			$this->autoCursorClose();
			return $this->_db->beginTransaction();
		}
		function commit()
		{
			if($this->delayedTransaction)
			{ // no transaction was automatically started
				$this->delayedTransaction = false;
				$this->autoCursorClose();
				return true;
			}
			$this->doConnect();
			if(SERIA_DEBUG) SERIA_Base::debug("<strong>SERIA_DB->commit()</strong>");
			$this->autoCursorClose();
			return $this->_db->commit();
		}
		function errorCode() { 
			$this->doConnect();
			return $this->_db->errorCode(); 
		}
		function errorInfo() { 
			$this->doConnect();
			return $this->_db->errorInfo(); 
		}
		function getAttribute($attribute) { 
			$this->doConnect();
			return $this->_db->getAttribute($attribute); 
		}
		static function getAvailableDrivers() { 
			return PDO::getAvailableDrivers(); 
		}
		function lastInsertId($name=NULL) { 
			$this->doConnect();
			return $this->_db->lastInsertId($name); 
		}
		function prepare($statement, array $driver_options=NULL) { 
			if($driver_options===NULL) $driver_options = array(); 
			$this->doConnect();
			return $this->_db->prepare($statement, $driver_options); 
		}
		function rollBack()
		{
			if($this->delayedTransaction)
			{ // no transaction was automatically started				
				$this->delayedTransaction = false;
				$this->autoCursorClose();
				return true;
			}
			$this->doConnect();
			if(SERIA_DEBUG) SERIA_Base::debug("<strong>SERIA_DB->rollBack()</strong>");
			$this->autoCursorClose();
			return $this->_db->rollBack();
		}
		function setAttribute($attribute, $value) { 
			$this->doConnect();
			return $this->_db->setAttribute($attribute, $value); 
		}

		/*
		 * Wrapper functions for inserting and updating from associated arrays
		 */
		/**
		 * Insert a new row into a database-table where data is an associated array.
		 *
		 * @param string $tableName String containing the column name for further handling by fluent-query
		 * @param array $updateColumnNames Array containing fields allowed to be inserted by this query.
		 * @param array $assocFieldData The associated array of fields to write, will be filtered and translated (name substitution) according to $updateColumnNames.
		 * @return unknown_type
		 */
		function insert($tableName, array $updateColumnNames, array $assocFieldData)
		{
			if ($tableName[0] == '{') {
				$len = strlen($tableName);
				if ($tableName[--$len] == '}') {
					$tableName = substr($tableName, 1, --$len);
					$tableName = SERIA_PREFIX.'_'.$tableName;
				}
			}
			$num = 0;
			$mapList = array();
			$colnames = array();
			foreach ($updateColumnNames as $val) {
				if (isset($assocFieldData[$val])) {
					$colnames[] = '`' . $val . '`';
					$mapList[] = $assocFieldData[$val];
					$num++;
				}
			}
			$paramStr = $num > 0 ? '?'.($num > 1 ? str_repeat(', ?', $num-1) : '') : '';
			$sql = 'INSERT INTO '.$tableName.' ('.implode(', ', $colnames).') VALUES ('.$paramStr.')';
// SIMPLER TO MAINTAIN
			$res = $this->exec($sql, $mapList);
			return $res;
			$statement = $this->prepare($sql);
			return $statement->execute($mapList);
		}
		/**
		 * Update a database row where data to be updated is an associated array.
		 *
		 * @param string $tableName String containing the column name for further handling by fluent-query.
		 * @param array $primaryKey An array of key=>value pairs of primary key
		 * @param array $updateColumnNames Array containing fields allowed to be inserted by this query.
		 * @param array $assocFieldData The associated array of fields to write, will be filtered and translated (name substitution) according to $updateColumnNames.
		 * @return unknown_type
		 */
		function update($tableName, array $primaryKey, array $updateColumnNames, array $assocFieldData)
		{
//			if($this->delayedTransaction)
//				$this->beginTransaction();
			if ($tableName[0] == '{') {
				$len = strlen($tableName);
				if ($tableName[--$len] == '}') {
					$tableName = substr($tableName, 1, --$len);
					$tableName = SERIA_PREFIX.'_'.$tableName;
				}
			}
			$primaryKeySize = count($primaryKey);
			if ($primaryKeySize <= 0)
				throw new Exception('Primary key must not be empty (dangerous update stopped)');
			$paramStr = array();
			$mapList = array();
			$num = 0;
			foreach ($updateColumnNames as $val) {
				if (isset($assocFieldData[$val])) {
					$paramStr[] = '`' . $val . '` = ?';
					$mapList[] = $assocFieldData[$val];
					$num++;
				}
			}
			if (!$paramStr)
				throw new SERIA_Exception('No fields to update!');
			$paramStr = implode(', ', $paramStr);
			$primStr = array();
			foreach ($primaryKey as $nam => $val) {
				$mapList[] = $val;
				$primStr[] = '`' . $nam . '` = ?';
			}
			$primStr = implode(' AND ', $primStr);

			return $this->exec('UPDATE '.$tableName.' SET '.$paramStr.' WHERE '.$primStr, $mapList);
		}
		/**
		 * Update, or insert if the row does not exist, a database row where data to be updated is an associated array. This function may not be thread (multiprocess) safe.
		 *
		 * @param string $tableName String containing the column name for further handling by fluent-query.
		 * @param unknown_type $primaryKey Either a string containing the primary key of this table, or an array of field-names forming a primary key.
		 * @param array $updateColumnNames Array containing fields allowed to be inserted by this query.
		 * @param array $assocFieldData The associated array of fields to write, will be filtered and translated (name substitution) according to $updateColumnNames.
		 * @return unknown_type
		 */
		function updateOrInsert($tableName, $primaryKey, array $updateColumnNames, array $assocFieldData)
		{
//			if($this->delayedTransaction)
//				$this->beginTransaction();
			if (is_string($primaryKey))
				$primaryKey = array($primaryKey);
			else if (!is_array($primaryKey))
				throw new Exception('$primaryKey must be either a string or an array of strings');
			$prim = array();
			$fieldsUpdate = $assocFieldData; /* Assume this makes a copy the array */
			foreach ($primaryKey as $val) {
				if (isset($assocFieldData[$val])) {
					$prim[$val] = $assocFieldData[$val];
					unset($fieldsUpdate[$val]);
				} else {
					/*
					 * Full primary key not supplied: Can't update, will insert!
					 */
					return $this->insert($tableName, $updateColumnNames, $assocFieldData);
				}
			}
			/*
			 * Prepare queries
			 */
			if ($tableName[0] == '{') {
				$len = strlen($tableName);
				if ($tableName[--$len] == '}') {
					$tableName = substr($tableName, 1, --$len);
					$tableName = SERIA_PREFIX.'_'.$tableName;
				}
			}
			$primaryKeySize = count($primaryKey);
			if ($primaryKeySize <= 0)
				throw new Exception('Primary key must not be empty (dangerous update stopped)');
			$paramStr = array();
			$mapList = array();
			foreach ($updateColumnNames as $val) {
				if (isset($assocFieldData[$val])) {
					$paramStr[] = '`' . $val . '` = ?';
					$mapList[] = $assocFieldData[$val];
					$num++;
				}
			}
			if (!$paramStr)
				throw new SERIA_Exception('No fields to update!');
			$paramStr = implode(', ', $paramStr);
			$primStr = array();
			$selectMap = array();
			foreach ($prim as $nam => $val) {
				$selectMap[] = $val;
				$mapList[] = $val;
				$primStr[] = '`' . $nam . '` = ?';
			}
			$primStr = implode(' AND ', $primStr);
			/*
			 * Try to update, if it fails, insert.
			 */
			$q = $this->query('SELECT '.implode(', ', $primaryKey).' FROM '.$tableName.' WHERE '.$primStr.' FOR UPDATE', $selectMap)->fetch(PDO::FETCH_NUM);
			if ($q)
				return $this->exec('UPDATE '.$tableName.' SET '.$paramStr.' WHERE '.$primStr, $mapList);
			if (!$err) /* 0 or false */
				return $this->insert($tableName, $updateColumnNames, $assocFieldData);
			else
				return $err;
		}

		/**
		*	Returns the next part in SQL, regardless of what kind of part it is. If it is a number, the entire number is returned,
		*	if it is a string, the entire string is returned.
		*
		*	SELECT * FROM tableName WHERE id=123
		*	gives
		*	SELECT|*|FROM|tableName|WHERE|id|=|123
		*
		*	CREATE TABLE test (id INTEGER PRIMARY KEY, name VARCHAR(100))
		*	gives
		*	CREATE|TABLE|test|(|id|INTEGER|PRIMARY|KEY|,|name|VARCHAR|(|100|)|)
		*/
		static function sqlTokenize($s, $removeWhiteSpace=true)
		{
		        /**
		         * Strip extra whitespace from the query
		         */
			if(!$s) throw new Exception('SERIA_DB::sqlTokenize expects a proper value');

		        if($removeWhiteSpace) {
		         $s = ltrim(preg_replace('/[\\s]{2,}/',' ',$s));
		        }
		        /**
		         * Regular expression based on SQL::Tokenizer's Tokenizer.pm by Igor Sutton Lopes
		         **/
		        $regex = '('; # begin group
		        $regex .= '(?:--|\\#)[\\ \\t\\S]*'; # inline comments
		        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)'; # logical operators
		        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")'; # empty single/double quotes
		        $regex .= '|".*?(?:(?:""){1,}"|(?<!["\\\\])"(?!")|\\\\"{2})|\'.*?(?:(?:\'\'){1,}\'|(?<![\'\\\\])\'(?!\')|\\\\\'{2})'; # quoted strings
		        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/'; # c style comments
		        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)'; # words, placeholders, database.table.column strings
		        $regex .= '|[\t\ ]+';
		        $regex .= '|[\.]'; #period
		        $regex .= '|[\s]'; #whitespace
		        $regex .= ')'; # end group
       
		        preg_match_all('/'.$regex.'/smx', $s, $result);
		        // return tokens
		        return $result[0];
		}

		public function getColumnSpec($table)
		{
			// MySQL specific

			$desc = $this->query($this->rewriteQuery('DESC '.$table))->fetchAll(PDO::FETCH_ASSOC);
			$result = array();
			foreach($desc as $column)
			{
				$row = array();

				$row['name'] = $column['Field'];

				$t = strpos($column['Type'],'(');;
				if($t === false)
				{
					$row['type'] = $column['Type'];
				}
				else
				{
					$row['type'] = substr($column['Type'], 0, $t);
					$t = substr($column['Type'], $t+1, strpos($column['Type'], ')')-($t+1));
					$t = explode(",", $t);
					$row['length'] = intval($t[0]);
					if(isset($t[1]))
						$row['decimals'] = $t[1];
				}

				$row['null'] = $column['Null'] == 'YES';

				$row['default'] = $column['Default'];

				$row['primary_key'] = $column['Key'] == 'PRI';
				$result[$row['name']] = $row;
			}
			return $result;
		}
	}

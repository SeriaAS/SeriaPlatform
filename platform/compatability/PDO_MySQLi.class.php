<?php
	if(!class_exists("PDO", false))
	{
		class PDO {
			const ATTR_DRIVER_NAME = "drivername";
			const ATTR_ERRMODE = "errormode";
			const ERRMODE_EXCEPTION = "errormode_exception";
			const FETCH_ASSOC = "assoc";
			const FETCH_COLUMN = "column";
			const FETCH_NUM = "num";
			const MYSQL_ATTR_DIRECT_QUERY = 'directquery';
		}
	}
	if(!class_exists("PDOException", false))
	{
		class PDOException extends Exception {
		}
	}
	
	class PDO_MySQLi {
		private $mysqli;

		function __construct($mysqli) {
			$this->mysqli = $mysqli;
		}

		function getAttribute($name) {
			switch($name) 
			{
				case PDO::ATTR_DRIVER_NAME : return "mysql";
				default : throw new Exception("Unknown attribute (PDO_MySQLi-wrapper)");
			}
		}

		function lastInsertId($seq=false)
		{
			return $this->mysqli->insert_id;
		}

		function setAttribute($name, $value) {
			switch($name)
			{
				case PDO::ATTR_ERRMODE : if($value===PDO::ERRMODE_EXCEPTION) return true; // only supports PDO_Exception errors
				case PDO::MYSQL_ATTR_DIRECT_QUERY: return true;
				default : throw new Exception("Unknown attribute or value (PDO_MySQLi-wrapper)");
			}
		}

		function exec($query) {
			$result = $this->mysqli->query($query);
			// check for error and throw PDO_Exception
			if($this->mysqli->errno)
				throw new PDOException("(PDO_MySQLi-wrapper): ".$this->mysqli->error." (".$mysqli->errno.")");

			if(stripos($query, "update ")===0)
			{
				return $this->mysqli->affected_rows;
			}
			return $result;
		}

		function query($query) {
			return new PDO_MySQLi_result($this->mysqli->query($query), $this->mysqli);
		}


		function beginTransaction() {
			return $this->exec("START TRANSACTION");
		}

		function commit() {
			return $this->exec("COMMIT");
		}

		function rollBack() {
//FRODE			return $this->exec("ROLLBACK");			
		}

		function quote($param) {
			return "'".$this->mysqli->real_escape_string($param)."'";
		}

	}

	class PDO_MySQLi_result {
		private $mysqli;
		private $result;

		function __construct($result, $mysqli) {
			$this->mysqli = $mysqli;

			// check for error
			if($mysqli->error)
				throw new PDOException("(PDO_MySQLi-wrapper): ".$mysqli->error." (".$mysqli->errno.")");

			$this->result = $result;
		}

		function fetch($style=false, $param=0)
		{
			if($style===false)
				$style = PDO::FETCH_ASSOC;
			switch($style)
			{
				case PDO::FETCH_ASSOC :
					if($this->result)
						return $this->result->fetch_assoc();
					else 
						return false;
				case PDO::FETCH_COLUMN : 
					if($this->result)
					{
						$res = $this->result->fetch_array();
						return $res[$param];
					}
					else
						return false;
				case PDO::FETCH_NUM :
					if($this->result)
						return $this->result->fetch_array();
					else 
						return false;
				default : throw new Exception("(PDO_MySQLi-wrapper): Unknown fetch style $style.");
					
			}
		}

		function fetchAll($style=false, $param=0)
		{
			$res = array();
			while($row = $this->fetch($style, $param))
				$res[] = $row;
			return $res;
		}
	}

?>

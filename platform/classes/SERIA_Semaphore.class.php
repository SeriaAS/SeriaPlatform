<?php
	/**
	*	SERIA Platform Semaphore support
	*
	*	Semaphores are automatically released when the object is destroyed, but it is also possible to call
	*	$object->release();
	*/
	class SERIA_Semaphore
	{
		var $released = false;
		var $key;

		static $semaphores = null;

		/**
		*	Constructur
		*
		*	@param $key Name of the semaphore to get
		*	@param $timeout The maximum number of milliseconds to wait for the semaphore, or true to try once and throw on failure.
		*	@param $maxAge The maximum age of this semaphore (in case of abnormal termination). Defaults to $timeout * 2
		*/
		function __construct($key, $timeout=5000, $maxAge=false)
		{
			if($maxAge === false) {
				if (is_numeric($timeout) && $timeout)
					$maxAge = $timeout * 2;
				else
					$maxAge = 10000;
			}

			if (self::$semaphores === null)
				self::$semaphores = array();
			if (in_array($key, self::$semaphores))
				throw new SERIA_Exception('Aquiring multiple instances of a semaphore will deadlock (recursive locking is not supported)!');

			$this->key = $key;
			$db = SERIA_Base::db();
			$t = microtime(true);
			$tn = 0;
			while(true)
			{
				$tn++;
				$db->exec('LOCK TABLES {semaphores} WRITE');
				try
				{
					$db->exec('INSERT INTO {semaphores} (id, createdTime) VALUES (:id, NOW())', array('id' => $key));
					$db->exec('UNLOCK TABLES');
					self::$semaphores[] = $key;
					return; // exit this eternal loop
				} catch (PDOException $e) {
					/*
					 * Table should be locked.
					 */
					try {
						$time = $db->query('SELECT UNIX_TIMESTAMP(createdTime) FROM {semaphores} WHERE id=:id', array('id' => $key))->fetch(PDO::FETCH_COLUMN, 0);
						if($time < time()-$maxAge)
						{ // this semaphore has expired, grab it
							$exec = $db->exec('UPDATE {semaphores} SET createdTime = NOW() WHERE id=:id AND createdTime = :ctime', array('id' => $key, 'ctime' => $time));
							$db->exec('UNLOCK TABLES');
							if ($exec) {
								self::$semaphores[] = $key;
								return;
							}
						}
						$db->exec('UNLOCK TABLES');
					} catch (Exception $doublefault) {
						$db->exec('UNLOCK TABLES');
						throw $doublefault;
					}
					/*
					 * Table should not be locked.
					 */
					if($timeout === true || microtime(true) - $t > $timeout)
						throw new SERIA_Exception('Unable to aquire semaphore "'.$key.'" within '.$timeout.' milliseconds.');
					// sleep between 0.1 and 0.5 seconds before trying again
					usleep(mt_rand(100000,500000));
				} catch (Exception $e) {
					$db->exec('UNLOCK TABLES');
					throw $e;
				}
			}
		}

		function __destruct()
		{
			if(!$this->released)
			{
				$this->release();
				$this->released = true;
			}
		}

		public function release()
		{
			$index = array_search($this->key, self::$semaphores);
			if ($index === false)
				return; /* Probably a rollback! */
			$db = SERIA_Base::db();
			$db->exec('LOCK TABLES {semaphores} WRITE');
			try {
				$this->released = $db->exec('DELETE FROM {semaphores} WHERE id=:id', array('id' => $this->key));
				$db->exec('UNLOCK TABLES');
			} catch (Exception $e) {
				$db->exec('UNLOCK TABLES');
				throw $e;
			}
			if ($this->released)
				unset(self::$semaphores[$index]);
			return $this->released;
		}
	}

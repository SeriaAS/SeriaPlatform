<?php
/**
 * Class that provides caching functionality, both file and memory based.
 *
 * This version of the cache backend use MySQL for storage. While this is not
 * the fastest type of caching, and also not a scalable type of caching in a system
 * with a lot of updates, it works in all installations.
 *
 * Memcache and file based backeds can replace this.
 */
class SERIA_Cache implements SERIA_ICache // mysql
{
	private $namespace;

	public function __construct($namespace = '')
	{
		$this->namespace = $namespace;
		if(SERIA_INSTALL) return;
		if(mt_rand(0,10000)===0)
		{
			SERIA_Async::call(array('SERIA_Cache', 'garbageCollectCache'));
		}
	}

	public static function garbageCollectCache()
	{
		if(SERIA_INSTALL) return;
		SERIA_Base::db()->exec('DELETE FROM {cache} WHERE expiry < UNIX_TIMESTAMP()');
	}

	public function deleteAll() {
		$db = SERIA_Base::db();
		$db->exec("DELETE FROM seria_cache WHERE name LIKE :namespace", array("namespace" => $this->namespace."%"));
	}

	public function delete($name) {
		$db = SERIA_Base::db();
		$name = $this->namespace.":".md5($name);
		$db->exec("DELETE FROM seria_cache WHERE name=:name", array("name" => $name));
	}

	public function set($name, $value, $expires=1800)
	{
		if(SERIA_INSTALL) return false;
		$name = $this->namespace.":".md5($name);

		$data = serialize($value);

		if(SERIA_DEBUG) SERIA_Base::debug("Cache set $name = [".strlen($data)." bytes]");

		try
		{
			return SERIA_Base::db()->exec("INSERT INTO seria_cache (name, value, expiry) VALUES (:name, :value, UNIX_TIMESTAMP()+".intval($expires).")", array("name" => $name, "value" => $data)) ? true : false;
		}
		catch (PDOException $e)
		{
			if($e->getCode() == "23000")
			{ // it was already there
				$res = SERIA_Base::db()->exec("UPDATE seria_cache SET value=:value, expiry=UNIX_TIMESTAMP()+".intval($expires)." WHERE name=:name", array("value" => $data, "name" => $name));
				if ($res === 0 && SERIA_DEBUG)
					SERIA_Base::debug('Updating cache key "'.$name.'" in namespace "'.$this->namespace.'" with identical expiration and contents.');
				return true;
			}
			else if($e->getCode() === '42S02') // database table does not exist, ignore silently
				return false;
			else throw $e;
		}
		return true;
	}

	public function get($name)
	{
		if(SERIA_INSTALL) return NULL;
		$name = $this->namespace.":".md5($name);

		try
		{
			$result = SERIA_Base::db()->query("SELECT value FROM seria_cache WHERE name=:name AND expiry>UNIX_TIMESTAMP()", array("name" => $name))->fetch(PDO::FETCH_COLUMN, 0);
		}
		catch (Exception $e)
		{ // maybe table does not exist, or something else went wrong. Ignore silently, since install script fails.
			return NULL;
		}
		if($result)
		{
			return unserialize($result);
		}
		else {
			return NULL;
		}
	}
}

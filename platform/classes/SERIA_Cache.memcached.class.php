<?php
/**
 * Class that provides caching functionality, both file and memory based.
 *
 * This is a php-memcached SERIA_Cache.
 *
 */
class SERIA_Cache implements SERIA_ICache // memcached
{
	private static $memcached = null;
	private $namespace;

	/**
	 *
	 * This is an identifier that ensures that we can do a delete-all-operation.
	 * Change and write to memcached and all the values of this namespace are
	 * invalidated.
	 * @var string
	 */
	private $validationToken;

	public function __construct($namespace = '')
	{
		$this->namespace = $namespace;
		if (self::$memcached === null)
			self::initMemcached();
		$key = 'namespace_'.sha1($this->namespace);
		$this->validationToken = self::$memcached->get($key);
		if (!$this->validationToken)
			$this->deleteAll(); /* Create a validation token */
	}
	private static function initMemcached()
	{
		$memcached_servers = 'localhost:11211';
		if (defined('MEMCACHED_SERVERS'))
			$memcached_servers = MEMCACHED_SERVERS;
		$memcached_servers = explode(',', $memcached_servers);

		if (defined('MEMCACHED_PREFIX'))
			$prefix = MEMCACHED_PREFIX;
		else {
			$prefix = gethostname();
			$prefix .= SERIA_DB_NAME;
			$prefix = sha1($prefix);
		}

		/*
		 * Reuse old objects for the same prefix.
		 */
		self::$memcached = new Memcached(sha1($prefix));
		self::$memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
		self::$memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
		self::$memcached->setOption(Memcached::OPT_TCP_NODELAY, true);
		self::$memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
		self::$memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);

		$serverlist = self::$memcached->getServerList();
		$sl = array();
		foreach ($serverlist as $server)
			$sl[] = $server['host'].':'.$server['port'];

		foreach ($memcached_servers as $memcached) {
			list($host, $port) = explode(':', $memcached);
			if (!$port)
				$port = '11211';
			$host = trim($host);
			$port = trim($port);

			/*
			 * If not already added..
			 */
			if (!in_array($host.':'.$port, $sl)) {
				SERIA_Base::debug('Memcached server '.$host.':'.$port);
				self::$memcached->addServer($host, $port);
			}
		}
	}

	protected static function error($error)
	{
		if (SERIA_DEBUG)
			throw new SERIA_Exception($error);
	}

	public function deleteAll()
	{
		$this->validationToken = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		$key = 'namespace_'.sha1($this->namespace);
		SERIA_Base::debug('Set validation token '.$key.' = '.$this->validationToken);
		if (self::$memcached->set($key, $this->validationToken) === false) {
			self::error('Memcached fails: '.self::$memcached->getResultCode().': '.self::$memcached->getResultMessage());
			$this->validationToken = null;
		}
	}

	public function delete($name)
	{
		$key = sha1(sha1($this->namespace).'_'.sha1($name));
		self::$memcached->set($key, null);
	}

	public function set($name, $value, $expires=1800)
	{
		if ($this->validationToken === null)
			return;
		$value = serialize($value);
		if (strlen($value) <= 786432) {
			$value = array(
				$this->validationToken,
				'single', /* Stored the serialized value here in one chunk */
				$value
			);
			/*
			 * Max expire offset in memcached. Longer expire-times can
			 * be stored by specifying a timestamp.
			 */
			if ($expires > 2592000)
				$expires = 2592000;
			else {
				$expires = time() + $expires;
				if ($expires < time()) /* overflow */
					$expires = 2592000;
			}
			$key = sha1(sha1($this->namespace).'_'.sha1($name));
			self::$memcached->set($key, $value, $expires);
		} else {
			/*
			 * Overcome the value-size maximum of 1MB. Using
			 * chunks of 768kB.
			 */
			$blocksize = 786432;
			$multi = array();
			while ($value) {
				if (strlen($value) > $blocksize) {
					$multi[] = substr($value, 0, $blocksize);
					$value = substr($value, $blocksize);
				} else {
					$multi[] = $value;
					$value = '';
				}
			}
			$ids = array();
			foreach ($multi as $i => $blk) {
				$id = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
				$ids[$i] = $id;
				self::$memcached->set($id, $blk, $expires); /* Fragment stored */
			}
			$value = array(
				$this->validationToken,
				'multi', /* Value is split into multiple fragments stored at their own keys */
				$ids /* Keys for value fragments */
			);
			$key = sha1(sha1($this->namespace).'_'.sha1($name));
			self::$memcached->set($key, $value, $expires);
		}
	}

	public function get($name)
	{
		if ($this->validationToken === null)
			return null;
		$key = sha1(sha1($this->namespace).'_'.sha1($name));
		$value = self::$memcached->get($key);
		if ($value !== false) {
			if (array_shift($value) == $this->validationToken) {
				$type = array_shift($value);
				if ($type == 'single')
					return unserialize(array_shift($value));
				else if ($type == 'multi') {
					$ids = array_shift($value);
					$value = '';
					foreach ($ids as $id) {
						$blk = self::$memcached->get($id);
						if ($blk === false)
							return null; /* Lost fragment */
						$value .= $blk;
					}
					return unserialize($value);
				}
			}
		} else {
			$code = self::$memcached->getResultCode();
			if ($code != Memcached::RES_NOTFOUND)
				self::error('Memcached get error: '.$code.': '.self::$memcached->getResultMessage());
		}
		return null;
	}
}

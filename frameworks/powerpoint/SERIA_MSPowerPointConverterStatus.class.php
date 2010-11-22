<?php

class SERIA_MSPowerPointConverterStatus
{
	protected $path;
	protected $vars = null;
	protected $locked = null;
	protected $dirty = false;

	public function __construct($file_id)
	{
		$basepath = SERIA_DYN_ROOT.'/filetranscoding/powerpoint';

		if (!file_exists($basepath))
			mkdir($basepath, 0755, true);
		$this->path = $basepath.'/status'.$file_id.'.txt';
	}

	public static function parse($contents)
	{
		$pairs = explode(';', $contents);
		if (!$pairs)
			throw new SERIA_Exception('Bad file contents: Not hashed!');
		$hash = array_pop($pairs);
		if ($hash != sha1(implode(';', $pairs)))
			throw new SERIA_Exception('Bad file contents: Bad hash!');
		$vars = array();
		foreach ($pairs as $pair) {
			$pair = explode(',', $pair);
			foreach ($pair as &$v)
				$v = base64_decode($v);
			unset($v);
			list($name, $value) = $pair;
			$vars[$name] = $value;
		}
		return $vars;
	}
	protected function readValues($lock=false)
	{
		if (!$this->locked && !file_exists($this->path)) {
			$this->vars = array();
			$this->writeValues($this->vars, false);
			if ($lock)
				$this->readValues($lock);
			return;
		}
		if ($lock && !$this->locked) {
			$this->locked = fopen($this->path, 'r+');
			if (!flock($this->locked, LOCK_EX)) {
				fclose($this->locked);
				$this->locked = false;
				throw new SERIA_Exception('Unable to lock file for writing: '.$this->path);
			}
			$f = $this->locked;
		} else if ($this->locked) {
			rewind($this->locked);
			$f = $this->locked;
		} else {
			$f = fopen($this->path, 'r');
			if (!flock($f, LOCK_SH)) {
				fclose($f);
				throw new SERIA_Exception('Unable to lock file for reading: '.$this->path);
			}
		}
		ob_start();
		fpassthru($f);
		$contents = ob_get_clean();
		if (!$this->locked) {
			flock($f, LOCK_UN);
			fclose($f);
		} else
			rewind($this->locked);
		$this->vars = self::parse($contents);
		$this->dirty = false;
	}
	protected function writeValues($values, $overwrite=true)
	{
		$pairs = array();
		foreach ($values as $name => $value)
			$pairs[] = implode(',', array(
				base64_encode($name),
				base64_encode($value)
			));
		$contents = implode(';', $pairs);
		if ($this->locked) {
			rewind($this->locked);
			$f = $this->locked;
		} else {
			if ($overwrite)
				$f = fopen($this->path, 'c');
			else
				$f = fopen($this->path, 'x');
			if (!$f) {
				if (!$overwrite)
					return false;
				throw new SERIA_Exception('Unable to open file for writing: '.$this->path);
			}
			if (!flock($f, LOCK_EX)) {
				fclose($f);
				throw new SERIA_Exception('Unable to lock file for writing: '.$this->path);
			}
		}
		ftruncate($f, 0);
		$hash = sha1($contents);
		if ($contents)
			$contents .= ';'.$hash;
		else
			$contents = $hash;
		while (($len = strlen($contents)) > 0) {
			$wr = fwrite($f, $contents, $len);
			if ($wr === false) {
				if ($this->locked)
					$this->locked = null;
				flock($f, LOCK_UN);
				fclose($f);
				throw new SERIA_Exception('Unable to write to file: '.$this->path);
			}
			if ($wr > 0) {
				if ($wr == $len)
					$contents = '';
				else
					$contents = substr($contents, $wr);
			}
		}
		if (!$this->locked) {
			flock($f, LOCK_UN);
			fclose($f);
		} else
			rewind($this->locked);
		$this->dirty = false;
		return true;
	}

	public function lock()
	{
		if ($this->locked)
			throw new SERIA_Exception('Already locked');
		if ($this->dirty)
			throw new SERIA_Exception('Discards data');
		$this->readValues(true);
	}
	public function unlock()
	{
		if ($this->locked === null)
			throw new SERIA_Exception('Not locked');
		flock($this->locked, LOCK_UN);
		fclose($this->locked);
		$this->locked = null;
	}
	public function save($unlock=false)
	{
		if ($this->dirty)
			$this->writeValues($this->vars);
		if ($unlock)
			$this->unlock();
	}

	public function __destruct()
	{
		if ($this->locked)
			$this->unlock();
	}

	public function set($name, $value)
	{
		if ($this->vars === null)
			$this->readValues();
		$this->vars[$name] = serialize($value);
		$this->dirty = true;
	}
	public function get($name)
	{
		if ($this->vars === null)
			$this->readValues();
		if (isset($this->vars[$name]))
			return unserialize($this->vars[$name]);
		else
			return null;
	}
}

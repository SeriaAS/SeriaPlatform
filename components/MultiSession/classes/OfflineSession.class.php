<?php

/**
 * This class can be used to manipulate other sessions (identified by id)
 * while still maintaining (or looks like we are still maintaining) the
 * current session.
 *
 * @author Jan-Espen Pettersen
 *
 */
class OfflineSession
{
	protected $data = array();
	protected $sid = false;

	/**
	 * Be very aware of the fact that this momentarily modifies the session cookie.
	 * There is a slight possibility of that the user is gaining insight unintentionally.
	 *
	 * @param unknown_type $sid
	 * @return unknown_type
	 */
	public function __construct($sid=false)
	{
		$this->sid = $sid;
		if ($this->sid === false) {
			$this->sid = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
			$this->data = array();
			$this->initSession($this->sid);
		} else {
			$this->data = $this->readSession($this->sid);
			if (SERIA_DEBUG) {
				SERIA_Base::debug('OfflineSession(): Dumping session '.$this->sid.':');
				foreach ($this->data as $name => $value)
					SERIA_Base::debug($name.': '.$value);
			}
		}
	}
	protected function switchSession($sid)
	{
		if (!session_id())
			session_start();
		$old = session_id();
		session_write_close();
		session_id($sid);
		session_start();
		return $old;
	}
	protected function initSession($sid)
	{
		$old = $this->switchSession($sid);
		$_SESSION = array();
		$this->switchSession($old);
	}
	protected function readSession($sid)
	{
		$old = $this->switchSession($sid);
		$data = $_SESSION;
		$this->switchSession($old);
		return $data;
	}

	public function set($name, $value)
	{
		$this->data[$name] = $value;
	}
	public function get($name)
	{
		return $this->data[$name];
	}
	public function exists($name)
	{
		return isset($this->data[$name]);
	}
	public function clear($name)
	{
		unset($this->data[$name]);
	}

	public function save()
	{
		$old = $this->switchSession($this->sid);
		$_SESSION = $this->data;
		$this->switchSession($old);
	}
}

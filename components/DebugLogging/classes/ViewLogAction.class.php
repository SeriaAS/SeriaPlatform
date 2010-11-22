<?php

class ViewLogAction extends SERIA_ActionUrl
{
	protected $id = false;
	protected $messages = false;
	protected $data = false;
	protected $logfile = false;

	public function __construct($view_id, $sysparams, $logfile=false)
	{
		parent::__construct('viewLogId', $view_id);
		$this->id = $view_id;
		$this->data = $sysparams;
		$this->logfile = $logfile;
	}

	protected function extractMessages()
	{
		if ($this->logfile === false) {
			DL_debug_logging_lock();
			$logfile = DEBUG_LOGFILE;
		} else
			$logfile = $this->logfile;
		if (file_exists($logfile))
			$logfile_contents = file_get_contents($logfile);
		else
			$logfile_contents = '';
		if ($this->logfile === false)
			DL_debug_logging_unlock();
		$logfile_contents = str_replace("\n\r", "\n", $logfile_contents);
		$logfile_contents = str_replace("\r\n", "\n", $logfile_contents);
		$logfile_contents = str_replace("\r", "\n", $logfile_contents);
		$loglines = explode("\n", $logfile_contents);
		foreach ($loglines as $ln) {
			$prefix = strpos($ln, ': ');
			if ($prefix !== false) {
				$id = substr($ln, 0, $prefix);
				if ($id == $this->id) {
					$dataFieldIdent = 'sysparams:';
					$dataFieldIdentLen = strlen($dataFieldIdent);
					if (substr($logdata['msg'], 0, $dataFieldIdentLen) != $dataFieldIdent) {
						$msg = unserialize(base64_decode(substr($ln, $prefix + 2)));
						$this->messages[] = $msg;
					}
				}
			}
		}
	}

	public static function getAll($num=false)
	{
		SERIA_Base::debug('ViewLogAction::getAll()');
		if ($num === false) {
			DL_debug_logging_lock();
			$logfile = DEBUG_LOGFILE;
		} else
			$logfile = DEBUG_LOGFILE.'.'.$num;
		if (file_exists($logfile))
			$logfile_contents = file_get_contents($logfile);
		else
			$logfile_contents = '';
		if ($num === false)
			DL_debug_logging_unlock();
		if (!$logfile_contents)
			return array();
		SERIA_Base::debug('I have a logfile now!');
		$logfile_contents = str_replace("\n\r", "\n", $logfile_contents);
		$logfile_contents = str_replace("\r\n", "\n", $logfile_contents);
		$logfile_contents = str_replace("\r", "\n", $logfile_contents);
		$loglines = explode("\n", $logfile_contents);
		$ids = array();
		foreach ($loglines as $ln) {
			$prefix = strpos($ln, ': ');
			if ($prefix !== false) {
				$id = substr($ln, 0, $prefix);
				$msg = unserialize(base64_decode(substr($ln, $prefix + 2)));
				$dataFieldIdent = 'sysparams:';
				$dataFieldIdentLen = strlen($dataFieldIdent);
				if (substr($msg['msg'], 0, $dataFieldIdentLen) == $dataFieldIdent) {
					if (!isset($ids[$id]))
						$ids[$id] = unserialize(base64_decode(substr($msg['msg'], $dataFieldIdentLen)));
				}
			}
		}
		$actions = array();
		foreach ($ids as $id => $sysparams)
			$actions[] = new self($id, $sysparams, $num !== false ? $logfile : false);
		return $actions;
	}

	public function getViewData()
	{
		return $this->data;
	}
	public function getLog()
	{
		if ($this->messages === false)
			$this->extractMessages();
		return $this->messages;
	}
	public function getTimestamp()
	{
		return $this->data['timestamp'];
	}
	public function getPageUrl()
	{
		if (isset($this->data['$_SERVER']) && isset($this->data['$_SERVER']['REQUEST_URI']))
			return new SERIA_URL($this->data['$_SERVER']['REQUEST_URI']);
		else
			return false;
	}
	public function getServerHost()
	{
		if (isset($this->data['$_SERVER']) && isset($this->data['$_SERVER']['SERVER_NAME']))
			return new SERIA_URL($this->data['$_SERVER']['SERVER_NAME']);
		else
			return false;
	}
}

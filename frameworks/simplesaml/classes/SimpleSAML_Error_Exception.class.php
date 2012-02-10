<?php
class SimpleSAML_Error_Exception extends Exception {
	protected $code;
	protected $cause;
	protected $backtrace;

	public function __construct($message, $code = 0, Exception $cause = NULL)
	{
		$this->code = $code;
		$this->cause = $cause;
		$this->backtrace = array(
			'NIL:NIL (Not implemented backtrace)'
		);
		parent::__construct($message);
	}

	public static function fromException(Exception $e)
	{
		if ($e instanceof SimpleSAML_Error_Exception)
			return $e;
		else
			return new SimpleSAML_Error_UnserializableException($e);
	}

	protected function initBacktrace(Exception $exception)
	{
	}


	public function getBacktrace()
	{
		return $this->backtrace;
	}


	public function getCause()
	{
		return $this->cause;
	}


	public function getClass()
	{
		return get_class($this);
	}


	public function format()
	{
		return array('SimpleSAML_Error_Exception: '.$this->message.' ('.$this->getFile().':'.$this->getLine().')');
	}


	public function logError()
	{
	}


	public function logWarning()
	{
	}


	public function logInfo()
	{
	}


	public function logDebug()
	{
	}


	public function __sleep()
	{
	}

}

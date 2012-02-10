<?php

require_once(dirname(__FILE__).'/SimpleSAML_Error_Exception.class.php');

/**
 *
 * Steal SimpleSAML errors and display them as a SERIA_Exception instead.
 * @author janespen
 *
 */
class SimpleSAML_Error_Error extends SimpleSAML_Error_Exception {
	protected $errorCode;
	protected $cause;
	protected $parameters;

	public function __construct($errorCode, Exception $cause = NULL) {
		$this->errorCode = $errorCode;
		if (is_array($this->errorCode)) {
			$this->parameters = $this->errorCode;
			$this->errorCode = $this->parameters[0];
			unset($this->parameters[0]);
			$this->cause = null;
		} else
			$this->parameters = array();
		parent::__construct($msg, -1, $cause);
	}


	public function getErrorCode() {
		return $this->errorCode;
	}


	public function getCause() {
		return $this->cause;
	}


	protected function setHTTPCode() {
		SERIA_Base::debug('SimpleSAML ERROR: HTTP ERROR CODE WANTED!');
	}


	public function show() {
		SERIA_Hooks::dispatch(SimplesamlLibrary::UNHANDLED_ERROR_HOOK, $this);
		if ($this->cause) {
			echo 'Unhandled SimpleSAML error: '.$e->getMessage();
			print_r($this->cause->getTrace());
		}
		echo "===========\n";
		debug_print_backtrace();
		die();
	}
}

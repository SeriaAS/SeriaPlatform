<?php

class SERIA_RollbackRequiredException extends SERIA_Exception
{
	public function __construct()
	{
		parent::__construct('A sub-transaction has been rolled back! This transaction is not possible to commit and is now read-only!');
	}
}

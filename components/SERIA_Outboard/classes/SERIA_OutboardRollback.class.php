<?php

class SERIA_OutboardRollback extends Exception
{
	public function __construct()
	{
		parent::__construct('Roll back the transaction please!');
	}
}
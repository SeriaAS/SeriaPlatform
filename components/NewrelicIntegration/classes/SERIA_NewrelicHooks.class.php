<?php

class SERIA_NewrelicHooks
{
	public static function maintain()
	{
		newrelic_ignore_transaction();
	}
}

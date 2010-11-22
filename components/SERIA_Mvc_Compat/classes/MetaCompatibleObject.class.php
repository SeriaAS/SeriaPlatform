<?php

/**
 * See the SERIA_Mvc/Meta documentation for details.
 *
 * @author Jan-Espen Pettersen
 *
 */
abstract class MetaCompatibleObject
{
	public abstract static function Meta();
	public abstract function get($name);
	public abstract function set($name, $value);
	public abstract function save();
	public abstract function delete();
}
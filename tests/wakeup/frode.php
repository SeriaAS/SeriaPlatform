<?php
	class A
	{
		var $child;

		function __construct()
		{
		}
	}

	class B
	{
		var $name = 'Frode';

		function test($params) { echo $params; }
	}

	/**
	*	Special proxy class that intercepts 
	*
	*/
	class SORM_ProxyObject
	{
		var $ref;
		var $original;
		var $parent;

		function __construct(&$ref, &$parent)
		{
			$this->ref =& $ref;
			$this->original =& $parent;
			$this->parent =& $parent;

		}

		function &__call($method, $params)
		{
			$this->SORM_Load();
			return call_user_func_array(array($this->original,$method),$params);
		}

		function &__get($property)
		{
			$this->SORM_Load();
			return $this->original->$property;
		}

		function __set($property, $value)
		{
			$this->SORM_Load();
			return $this->original->$property = $value;
		}

		/**
		*	Will load the referenced object, then replace the owner objects reference to this object
		*/
		function SORM_Load()
		{
			$keep = $this->parent;
			$this->parent = $this->ref;
		}
	}

	$a = new A();

	$a->child = new SORM_ProxyObject(new B, $a->child);

//$a->child->SORM_Load();

	$test =& $a->child;

echo serialize($test)."\n";
$test->test("HALO");
echo "\n";
echo serialize($test)."\n";

	echo "Time: ".(microtime(true)-$t)."\n";

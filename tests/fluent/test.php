<?php
	class A implements Iterator
	{
		function current() { return 1; }
		function key() { return 0; }
		function valid() { return true; }
		function next() { return false; }
		function rewind() { return true; }
	}

	class B extends A {
	}

	$a = new B;
	foreach($a as $k => $v)
		echo "$k = $v\n";

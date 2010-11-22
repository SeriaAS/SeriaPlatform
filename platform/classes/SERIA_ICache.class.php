<?php
	interface SERIA_ICache {
		function __construct($namespace='');
		function get($name);
		function set($name, $value, $expires=1800);
	}

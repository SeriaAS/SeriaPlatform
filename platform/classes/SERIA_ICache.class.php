<?php
	interface SERIA_ICache {
		public function __construct($namespace='');
		public function get($name);
		public function set($name, $value, $expires=1800);
		public function deleteAll();
	}

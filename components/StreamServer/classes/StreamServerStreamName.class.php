<?php
	class StreamServerStreamName extends ActiveResource {
		protected $url = '';
		protected $username;
		protected $password;
		protected $singular_name = 'stream_name';
		protected $plural_name = 'stream_names';
		
		private static $_url;
		private static $_username;
		private static $_password;
		
		public function __construct() {
			$this->url = self::$_url;
			$this->username = self::$_username;
			$this->password = self::$_password;
		}
		
		public static function setUrl($url) {
			self::$_url = $url;
		}
		public static function setAuth($username, $password) {
			self::$_username = $username;
			self::$_password = $password;
		}
		
		
		public static function create() {
			$object = new StreamServerStreamName();
			return $object->object_create();
		}
		
		public static function all() {
			$object = new StreamServerStreamName();
			return $object->object_all();
		}
		public static function find($id) {
			$object = new StreamServerStreamName();
			return $object->object_find($id);
		}
	}
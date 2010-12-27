<?php
	class StreamServer extends ActiveResource {
		protected $url = '';
		protected $username;
		protected $password;
		protected $singular_name = 'wowza_server';
		protected $plural_name = 'wowza_servers';
		
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
			$object = new StreamServer();
			return $object->object_create();
		}
		
		public static function all() {
			$object = new StreamServer();
			return $object->object_all();
		}
		public static function find($id) {
			$object = new StreamServer();
			return $object->object_find($id);
		}
		
		public function streamNames() {
			$selectedStreamNames = array();
			$streamNames = StreamServerStreamName::all();
			foreach ($streamNames as $streamName) {
				if ($streamName->wowza_server_id == $this->id) {
					$selectedStreamNames[] = $streamName;
				}
			}
			
			return $selectedStreamNames;
		}
	}
<?php
	class StreamServer extends ActiveResource {
		protected $url = 'http://streamserverinterface.seria.net';
		protected $username;
		protected $password;
		protected $singular_name = 'wowza_server';
		protected $plural_name = 'wowza_servers';
		
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
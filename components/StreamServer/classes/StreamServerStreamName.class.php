<?php
	class StreamServerStreamName extends ActiveResource {
		protected $url = 'http://localhost:3000';
		protected $username;
		protected $password;
		protected $singular_name = 'stream_name';
		protected $plural_name = 'stream_names';
		
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
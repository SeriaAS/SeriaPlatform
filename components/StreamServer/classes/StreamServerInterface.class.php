<?php
	class StreamServerInterface {
		/**
		 * 
		 * Create a new stream server name on server specified by server key
		 * 
		 * @param string $serverKey Server key
		 * @param string $prefix Stream prefix
		 */
		public static function createStreamName($serverKey, $prefix) {
			$streamServers = StreamServer::all();
			foreach ($streamServers as $streamServer) {
				if ($streamServer->key == $serverKey) {
					$name = $prefix . rand(0,10000);
					$publishname = $prefix . rand(0,10000) . 'publish';
					
					$streamName = StreamServerStreamName::create();
					$streamName->name = $name;
					$streamName->publishname = $publishname;
					$streamName->wowza_server_id = $streamServer->id;
					
					if ($streamName->save()) {
						return $streamName;
					}
				}
			}
			
			return false;
		}
		
		/**
		 * 
		 * Delete a stream name
		 * 
		 * @param string $serverKey
		 * @param string $name
		 */
		public static function deleteStreamName($serverKey, $name) {
			$streamServers = StreamServer::all();
			$streamNames = StreamServerStreamName::all();
			foreach ($streamServers as $streamServer) {
				if ($streamServer->key == $serverKey) {
					foreach ($streamNames as $streamName) {
						if ($streamName->wowza_server_id == $streamServer->id) {
							if ($streamName->name == $name) {
								$streamName->destroy();
								return true;
							}
						}
					}
				}
			}
			
			return false;
		}
	}
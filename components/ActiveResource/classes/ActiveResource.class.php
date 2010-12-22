<?php
	class ActiveResource {
		protected $url;
		protected $username;
		protected $password;
		protected $plural_name;
		protected $singular_name;
		
		protected $_attributes;
		
		/**
		 * 
		 * Create multiple objects from returned XML from API
		 * 
		 * @param string $xml
		 * @return array
		 */
		protected function _createObjectsFromXml($xml) {
			$sxml = simplexml_load_string($xml);
			
			if ($sxml->{$this->_xmlizeName($this->singular_name)}) {
				$objects = array();
				
				foreach ($sxml->{$this->_xmlizeName($this->singular_name)} as $objectSXml) {
					$object = $this->_createObjectFromXml($objectSXml->asXML());
					if ($object) {
						$objects[] = $object;
					}
				}
				
				return $objects;
			}
		}
		
		/**
		 * 
		 * Create single object from returned XML from API
		 * 
		 * @param string $xml
		 * @return Object
		 */
		protected function _createObjectFromXml($xml) {
			$sxml = simplexml_load_string($xml);
			
			$className = get_class($this);
			$object = new $className();
			$object->_populateFromSimpleXml($sxml);
			return $object;
		}
		
		/**
		 * 
		 * Send request to API
		 * 
		 * @param string $method
		 * @param string $url
		 * @param string $data
		 */
		public function _request($method, $url, $data = '') {
			$requestUrl = rtrim($this->url, '/') . '/' . $url;
			$curl = curl_init($requestUrl);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'SERIA/ActiveResourceComponent');
			if ($this->username != '') {
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
			}
			switch (strtoupper($method)) {
				case 'GET':
					curl_setopt($curl, CURLOPT_HTTPGET, true);
					break;
				case 'POST':
				case 'PUT':
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/xml',
						'Content-Length' => strlen($data)
					));
					break;
				default:
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
					break;
			}
			
			$data = curl_exec($curl);
				
			if (curl_errno()) {
				throw new ActiveResourceException(curl_error($curl), curl_errno($curl));
			}
			
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			return array($data, $code);
		}
		
		/**
		 * 
		 * Get all objects from API
		 * 
		 * @return array
		 */
		public function object_all() {
			list($xmlData, $code) = $this->_request('GET', $this->plural_name . '.xml');
			
			if ($code == 200) {
				try {
					$objects = $this->_createObjectsFromXml($xmlData);
					
					return $objects;
				} catch (Exception $exception) {
					throw new ActiveResourceException('Resource not found: ' . $exception->getMessage(), 500);
				}
			} else {
				throw new ActiveResourceException('Resource not found.', $code);
			}
		}
		
		/**
		 * 
		 * Get object by ID
		 * 
		 * @param integer $id
		 * @return Object
		 */
		public function object_find($id) {
			if (!is_numeric($id)) {
				throw new Exception('ID is not numeric');
			}
			
			list($xmlData, $code) = $this->_request('GET', $this->plural_name . '/' . $id . '.xml');
			
			if ($code != 200) {
				throw new ActiveResourceException('Resource not found', $code);
			}
			
			$object = $this->_createObjectFromXml($xmlData);
			return $object;
		}		
		
		public function object_create() {
			list($data, $code) = $this->_request('GET', $this->plural_name . '/new.xml');
			if ($code == 200) {
				return $this->_createObjectFromXml($data);
			}
		}
				
		private function _getAllAttributes() {
			$attributes = $this->_attributes;
		}
		
		public function __construct() {
		}
		
		public function __get($name) {
			$name = $this->_xmlizeName($name);
			if (isset($this->_attributes[$name])) {
				list($type, $value) = $this->_attributes[$name];
				switch ($type) {
					case 'string':
						return $value;
						break;
					case 'integer':
						return intval($value);
						break;
					case 'boolean':
						return ($value == 'true');
						break;
					default:
						return $value;
						break;
				}
			}
		}
		
		public function __set($name, $value) {
			$name = $this->_xmlizeName($name);
			if (isset($this->_attributes[$name])) {
				switch ($this->_attributes[$name][0]) {
					case 'integer':
						$value = intval($value);
						break;
					case 'boolean':
						if ($value == 'true') {
							$value = true;
						} else {
							$value = false;
						}
						break;
				}
				$this->_attributes[$name][1] = $value;
			}
		}
		
		private function _xmlizeName($name) {
			return str_replace('_', '-', $name);
		}
		private function _unxmlizeName($name) {
			return str_replace('-', '_', $name);
		}
		
		/**
		 * 
		 * Create XML from object for XML API
		 * 
		 * @return string
		 */
		private function _createXml() {
			$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$xml .= '<' . $this->_xmlizeName($this->singular_name) . '>';
			
			foreach ($this->_attributes as $name => $data) {
				list($type, $value) = $data;
				$value = htmlspecialchars($value);
				$type = htmlspecialchars($type);
				
				$xml .= '<' . $name . ' type="' . $type . '">';
				$xml .= $value;
				$xml .= '</' . $name . '>';
			}
			
			$xml .= '</' . $this->_xmlizeName($this->singular_name) . '>';

			return $xml;
		}
		
		/**
		 * 
		 * Populate object from SimpleXML object from XML API
		 * 
		 * @param $sxml SimpleXML Object
		 */
		private function _populateFromSimpleXml($sxml) {
			foreach ($sxml as $name => $element) {
				$attributes = $element->attributes();
				$type = (string) $attributes['type'];
				if ($type == '') {
					$type = 'string';
				}
				$this->_attributes[$name] = array($type, (string) $element);
			}
		}
		
		/**
		 * 
		 * Save object to API
		 * 
		 */
		public function save() {
			$xml = $this->_createXml();
			
			$this->id = $this->_attributes['id'][1];
			
			if ($this->id) {
				$method = 'PUT';
				$url = $this->plural_name . '/' . $this->id . '.xml';
			} else {
				$method = 'POST';
				$url = $this->plural_name . '.xml';
			}
			
			list($response, $code) = $this->_request($method, $url, $xml);
			if ($code == 201 || $code == 200) {
				$this->_populateFromSimpleXml(simplexml_load_string($response));
				
				return true;
			} else {
				throw new ActiveResourceException('Unable to save record', $code);
			}
		}
		
		/**
		 * 
		 * Send destroy request to API
		 * 
		 */
		public function destroy() {
			$id = intval($this->_attributes['id'][1]);
			$url = $this->plural_name . '/' . $id . '.xml';
			
			list($response, $code) = $this->_request('DELETE', $url);
			if ($code == 200) {
				return true;
			} else {
				throw new ActiveResourceException('Unable to delete record', $code);
			}
		}
	}
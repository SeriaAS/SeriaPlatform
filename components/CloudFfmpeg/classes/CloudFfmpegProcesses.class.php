<?php
	class CloudFfmpegProcesses {
		protected $_url;
		protected $_username;
		protected $_password;
		
		public function __construct($url, $username = '', $password = '') {
			$this->_url = $url;
			$this->_username = $username;
			$this->_password = $password;
		}
		
		public function create() {
			list($data, $code) = $this->_request('GET', 'ffmpeg_processes/new.xml');
			if ($code == 200) {
				return $this->_createObjectFromXml($data);
			}
		}
		
		/**
		 * 
		 * Create multiple CloudFfmpegProcess objects from returned XML from API
		 * 
		 * @param string $xml
		 * @return array
		 */
		protected function _createObjectsFromXml($xml) {
			$sxml = simplexml_load_string($xml);
			
			if ($sxml->{'ffmpeg-process'}) {
				$objects = array();
				
				foreach ($sxml->{'ffmpeg-process'} as $ffmpegProcessSxml) {
					$object = $this->_createObjectFromXml($ffmpegProcessSxml->asXML());
					if ($object) {
						$objects[] = $object;
					}
				}
				
				return $objects;
			}
		}
		
		/**
		 * 
		 * Create single CloudFfmpegProcess object from returned XML from API
		 * 
		 * @param string $xml
		 * @return CloudFfMpegProcess
		 */
		protected function _createObjectFromXml($xml) {
			$sxml = simplexml_load_string($xml);
			
			if (((string) $sxml->deleted) == 'false') {
				$cloudFfmpegProcess = new CloudFfmpegProcess($this);
				$cloudFfmpegProcess->_populateFromSimpleXml($sxml);
				return $cloudFfmpegProcess;
			}
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
			$curl = curl_init($this->_url . '/' . $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'SERIA/CloudFfmpegComponent');
			if ($this->_username != '') {
				curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($curl, CURLOPT_USERPWD, $this->_username . ':' . $this->_password);
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
				throw new CloudFfmpegProcessException(curl_error($curl), curl_errno($curl));
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
		public function all() {
			list($xmlData, $code) = $this->_request('GET', 'ffmpeg_processes.xml');
			
			if ($code == 200) {
				try {
					$objects = $this->_createObjectsFromXml($xmlData);
					
					return $objects;
				} catch (Exception $exception) {
					throw new CloudFfmpegProcessException('Resource not found: ' . $exception->getMessage(), 500);
				}
			} else {
				throw new CloudFfmpegProcessException('Resource not found.', $code);
			}
		}
		
		/**
		 * 
		 * Get object by ID
		 * 
		 * @param integer $id
		 * @return CloudFfmpegProcess
		 */
		public function id($id) {
			if (!is_numeric($id)) {
				throw new Exception('ID is not numeric');
			}
			
			list($xmlData, $code) = $this->_request('GET', 'ffmpeg_processes/' . $id . '.xml');
			
			if ($code != 200) {
				throw new CloudFfmpegProcessException('Resource not found', $code);
			}
			
			$object = $this->_createObjectFromXml($xmlData);
			return $object;
		}
	}
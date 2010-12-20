<?php
	class CloudFfmpegProcess {
		private $_cloudFfmpegProcesses;
		
		public $id = 0;
		public $arguments = '';
		public $created_at = '';
		public $inputfile = '';
		public $outputfile = '';
		public $last_check_time = '';
		public $restart = false;
		public $running = false;
		public $updated_at = '';
		public $server_id = 0;
		
		protected $_attributes;
		
		protected function _getAllAttributes() {
			$attributes = $this->_attributes;
		}
		
		public function __construct($cloudFfmpegProcesses) {
			if (get_class($cloudFfmpegProcesses) != 'CloudFfmpegProcesses') {
				throw new CloudFfmpegProcessException('Use CloudFfmpegProcesses->create', 5000);
			}
			
			$this->_cloudFfmpegProcesses = $cloudFfmpegProcesses;
		}
		
		/**
		 * 
		 * Create XML from object for XML API
		 * 
		 * @return string
		 */
		protected function _createXml() {
			$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$xml .= '<ffmpeg-process>';
			
			foreach ($this->_attributes as $name => $data) {
				list($type, $value) = $data;
				$value = htmlspecialchars($value);
				$type = htmlspecialchars($type);
				
				$xml .= '<' . $name . ' type="' . $type . '">';
				$xml .= $value;
				$xml .= '</' . $name . '>';
			}
			
			$xml .= '</ffmpeg-process>';

			return $xml;
		}
		
		/**
		 * 
		 * Populate object from SimpleXML object from XML API
		 * 
		 * @param $sxml SimpleXML Object
		 */
		public function _populateFromSimpleXml($sxml) {
			foreach ($sxml as $name => $element) {
				$attributes = $element->attributes();
				$type = (string) $attributes['type'];
				if ($type == '') {
					$type = 'string';
				}
				$this->_attributes[$name] = array($type, (string) $element);
			}
			
			$this->id = intval($sxml->id);
			$this->arguments = (string) $sxml->arguments;
			$this->created_at = strtotime((string) $sxml->{'created-at'});
			$this->inputfile = (string) $sxml->inputfile;
			$this->outputfile = (string) $sxml->outputfile;
			$this->last_check_time = strtotime((string) $sxml->{'last-check-time'});
			$this->restart = ((string) $sxml->restart == 'true');
			$this->running = ((string) $sxml->running == 'true');
			$this->updated_at = strtotime((string) $sxml->{'updated-at'});
			$this->server_id = intval($sxml->{'server-id'});
		}
		
		protected function _updateAttributesFromObject() {
			$this->_attributes = array_merge($this->_attributes, array(
				'arguments' => array('string', $this->arguments),
				'inputfile' => array('string', $this->inputfile),
				'outputfile' => array('string', $this->outputfile),
				'restart' => array('boolean', ($this->running ? 'true' : 'false'))
			));
		}
		
		/**
		 * 
		 * Save object to API
		 * 
		 */
		public function save() {
			$this->_updateAttributesFromObject();
			
			$xml = $this->_createXml();
			
			$this->id = $this->_attributes['id'][1];
			
			if ($this->id) {
				$method = 'PUT';
				$url = 'ffmpeg_processes/' . $this->id . '.xml';
			} else {
				$method = 'POST';
				$url = 'ffmpeg_processes.xml';
			}
			
			list($response, $code) = $this->_cloudFfmpegProcesses->_request($method, $url, $xml);
			if ($code == 201 || $code == 200) {
				$this->_populateFromSimpleXml(simplexml_load_string($response));
				
				return true;
			} else {
				throw new CloudFfmpegProcessException('Unable to save record', $code);
			}
		}
		
		/**
		 * 
		 * Send destroy request to API
		 * 
		 */
		public function destroy() {
			$id = intval($this->_attributes['id'][1]);
			$url = 'ffmpeg_processes/' . $id . '.xml';
			
			list($response, $code) = $this->_cloudFfmpegProcesses->_request('DELETE', $url);
			if ($code == 200) {
				return true;
			} else {
				throw new CloudFfmpegProcessException('Unable to delete record', $code);
			}
		}
	}	
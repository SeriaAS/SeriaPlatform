<?php
	class SERIA_Widget extends SERIA_ActiveRecordController implements SERIA_EventListener {
		protected $guid;
		protected $type;
		protected $operationMode;
		protected $record;
		protected $attachedEvents = array();
		public $isNew = false;
		protected $__urlTail;
		
		/*
		 * Code for data storage system for storing small amounts of data for current widget
		 */
		protected $data = false;
		protected $modifiedData = false;
		
		private function readData() {
			if ($this->data === false) {
				$this->data = array();
				$this->modifiedData = array();
				
				$data = SERIA_WidgetDataRecords::find_all('criterias', array('widget_id' => $this->record->id));
				foreach ($data as $dataObject) {
					$this->data[$dataObject->key] = unserialize($dataObject->value);
					$this->modifiedData[$dataObject->key] = false;
				}
			}
		}
		
		protected function get($key) {
			$this->readData();
			
			return $this->data[$key];
		}
		
		protected function set($key, $value) {
			$this->readData();
			
			$dataRecord = SERIA_WidgetDataRecords::find_first_by_key($key, array('criterias' => array('widget_id' => $this->record->id)));
			if (!$dataRecord) {
				$dataRecord = new SERIA_WidgetDataRecord();
				$dataRecord->widget_id = $this->record->id;
				$dataRecord->key = $key;
			}
			$dataRecord->value = serialize($value);
			if ($dataRecord->save()) {
				$this->data[$key] = $value;
				return true;
			}
			
			return false;
		}
		
		/*
		 * End data storage system
		 */
		
		
		public function __construct($owner, $type, $operationMode=false) {
			if (is_object($owner)) {
				$this->guid = serialize($owner->getObjectId());
			} else {
				$this->guid = (string) $owner;
			}
			$this->type = $type;
			$this->operationMode = $operationMode;
			parent::__construct('widget');

			$widgetRecord = SERIA_WidgetRecords::find_first_by_guid($this->guid, 'criterias', array('type' => $type));
			if (!$widgetRecord) {
				$widgetRecord = new SERIA_WidgetRecord();
				$widgetRecord->type = $this->type;
				$widgetRecord->guid = $this->guid;
				$widgetRecord->save();
				$this->record = $widgetRecord;
				
				$this->initialize();
				$this->isNew = true;
				
				if (is_object($owner)) {
					$this->attachObject($owner);
				}
			} else {
				$this->record = $widgetRecord;
				$this->wakeup();
			}
		}
		
		public function setUrlTail($tail) {
			$this->__urlTail = $tail;
		}
		
		protected function initialize() {}
		protected function sleep() {}
		protected function wakeup() {}
		
		// Implemented for SERIA_EventListener
		public function getObjectId() {
			return array('SERIA_Widget', 'createObject', $this->record->id);
		}
		public static function createObject($id) {
			if (!$id) {
				throw new SERIA_Exception('No ID specified');
			}
			
			$widgetRecord = SERIA_WidgetRecords::find($id);
			if (!$widgetRecord) {
				throw new SERIA_Exception('Widget not found');
			}
			
			$name = $widgetRecord->type;
			$owner = SERIA_NamedObjects::getInstanceOf($widgetRecord->guid);
			$widget = self::getWidget($name, $owner);
			
			return $widget;
		}
		// End implementation
		
		protected static function getClassName($name) {
			$className = $name . 'Widget';
			
			return $className;
		}
		
		public static function loadWidgetClass($name) {
			$widgetPaths = array(SERIA_ROOT . '/widgets/', SERIA_ROOT . '/seria/platform/widgets/');
			
			foreach ($widgetPaths as $widgetRootPath) {
				$widgetPath = $widgetRootPath . '/' . $name . '/';
				if (file_exists($widgetPath)) {
					break;
				}
			}
			
			if (!$widgetPath || !file_exists($widgetPath)) {
				throw new SERIA_Exception('Widget not found');
			}
			
			require_once($widgetPath . 'controller.php');
			$className = self::getClassName($name);
			return array($className, $widgetPath);
		}
		
		public static function getWidget($name, $owner, $operationMode=false) {
			list($className, $widgetPath) = self::loadWidgetClass($name);
			
			if (!is_string($owner) && !is_object($owner)) {
				throw new SERIA_Exception('Owner is not a string or an object');
			}
			
			$widget = new $className($owner, $name, $operationMode);
			
			$localViewPath = SERIA_ROOT . '/widgetviews/' . $name . '/';
			if (file_exists($localViewPath)) {
				$widget->setViewPath($localViewPath);
			}
			$widget->setSecondViewPath($widgetPath . '/view/');
			return $widget;
		}
		
		public function render($forceAction = null) {
			$this->processRequest($forceAction);
		}

		public function output($forceAction = null) {
			ob_start();
			try {
				$this->render($forceAction);
				return ob_get_clean();
			} catch (Exception $e) {
				ob_end_flush();
				throw $e;
			}
		}
		
		protected function delete() {
			if ($this->record) {
				$this->record->delete();
			}
		}
		
		protected function processRequest($forceAction = null) {
			if (!$forceAction) {
				$action = 'index';
				
				$params = $_GET[$this->getHttpKey()];
				if ($params) {
					if ($params = base64_decode($params)) {
						if ($params = unserialize($params)) {
							$action = $params['action'];
						}
					}
				}
			} else {
				$action = $forceAction;
			}
			$this->request($action, $params);
		}

		public function getId()
		{
			return $this->record->id;
		}

		public function getNamedObject()
		{
			return SERIA_NamedObjects::getInstanceOf($this->guid);
		}

		public function getOperationMode()
		{
			return $this->operationMode;
		}

		public function getWidgetDirname()
		{
			$className = get_class($this);
			$className = substr($className, 0, strlen($className) - strlen('Widget'));
			return $className;
		}
		public function getWidgetName()
		{
			return strtolower($this->getWidgetDirname());
		}
		
		public function attachObject($object) {
			if ($this->isNew) {
				foreach ($this->attachedEvents as $eventName => $method) {
					$object->addEventListener($eventName, $this);
				}
			}
		}
		
		protected function getHttpKey() {
			return $this->getWidgetName() . '_' . $this->guid;
		}
		
		public function getUrl($action = '', $params = array()) {
			$widgetName = $this->getWidgetName();
			$params['action'] = $action;
			
			$args = '?' . urlencode($this->getHttpKey()) . '=' . urlencode(base64_encode(serialize($params))) . '&';
			foreach ($_GET as $key => $value) {
				if ($key != $this->getHttpKey()) {
					$args .= urlencode($key) . '=' . urlencode($value) . '&';
				}
			}
			
			if ($_SERVER['HTTPS']) {
				$protocol = 'https';
			} else {
				$protocol = 'http';
			}
			
			$urlparts = explode('?', $_SERVER['REQUEST_URI']);
			$url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $urlparts[0] . $args;
			
			$url .= $this->__urlTail;
			
			return $url;
		}
		
		public function catchEvent(SERIA_EventDispatcher $source, $eventName) {
			if (method_exists($this, $eventName)) {
				$methodName = $this->attachedEvents[$eventName];
				$this->$methodName($source);
			}
		}
		
		public function __destruct() {
			$this->sleep();
		}
	}
?>

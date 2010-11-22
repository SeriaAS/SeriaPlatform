<?php
	class SERIA_ActiveRecordController {
		private $__template;
		private $__templatePath;
		private $__secondTemplatePath;
		private $__controller;
		
		public function __construct($name) {
			$this->__controller = $name;
			$this->__templatePath = SERIA_ROOT . '/views/' . $this->__controller . '/';
			$this->__secondTemplatePath = SERIA_ROOT . '/seria/' . $this->__controller . '/';
		}
		
		public function setViewPath($path) {
			$this->__templatePath = $path;
		}
		protected function setSecondViewPath($path) {
			$this->__secondTemplatePath = $path;
		}
		
		public function request($action = 'index', $arguments = array()) {
			 $this->__template = $action;
			
			$methodName = 'action_' . $action;
			if (method_exists($this, $methodName)) {
				$this->$methodName($arguments);
				$this->parseTemplate();
			} else {
				throw new SERIA_Exception('No actionhandler for action ' . $action . ' found');
			}
		}
		
		private function parseTemplate($_templateName = false) {
			if ($_templateName === false) {
				$_templateName = $this->__template;
			}
			
			foreach (get_object_vars($this) as $var => $value) {
				if ($var[0] != '_') {
					$$var = $value;
				}
			}
			$viewPath = '';
			$primaryViewPath = $this->__templatePath . $_templateName . '.php';
			if ($this->__templatePath && file_exists($primaryViewPath)) {
				$viewPath = $primaryViewPath;
			} else {
				if ($this->__secondTemplatePath) {
					$viewPath = $this->__secondTemplatePath . $_templateName . '.php';
				}
			}
			
			if (!$viewPath) {
				throw new SERIA_Exception('View not found');
			}
			require($viewPath);
		}
		
		protected function renderPartial($name) {
			$this->parseTemplate('partial_' . $name);
		}
		
	}
?>

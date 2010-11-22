<?php
	class SERIA_HtmlArDatagrid {
		protected $datasource;
		protected $columnHeadings; // Headings for columns visible to end user
		protected $sortAbles = array(); // Sortable columns
		protected $arguments;
		protected $customColumns = array();
		protected $columnCallbacks = array();
		public $deleteAvailable = true;
		protected $columnCssClass = array();
		protected $addBatchSelect;
		protected $batchSelectId;
		protected $columnClickUrlTemplate = array();
		
		public function addBatchSelect($id, $switch = true) {
			$this->addBatchSelect = (bool) $switch;
			$this->batchSelectId = $id;
		}
		
		public function setColumnClickUrlTemplate($column, $url) {
			if (is_array($column)) {
				foreach ($column as $item) {
					$this->setColumnClickUrlTemplate($item, $url);
				}
			} else {
				$this->columnClickUrlTemplate[$column] = $url;
			}
		}
		
		public function __construct($datasource, $columnHeadings, $customColumns = array(), SERIA_Locale $locale = NULL) {
			if($locale === NULL)
				$this->locale = SERIA_Locale::getLocale();
			else
				$this->locale = $locale;
				
			if (!is_array($datasource) && (get_class($datasource) != 'SERIA_ActiveRecordSet')) {
				throw new SERIA_Exception('Wrong argument type for data source');
			}
				
			$this->datasource = $datasource;
			foreach ($columnHeadings as $id => $name) {
				$callback = '';
				if (is_array($name)) {
					list($name, $callback) = $name;
				}
				
				$this->columnHeadings[$id] = $name;
				$this->columnCallbacks[$id] = $callback;
			}
			$this->customColumns = $customColumns;
		}
		
		public function setColumnCssClass($column, $class) {
			$this->columnCssClass[$column] = $class;
		}
		
		public function render($htmlClassName="") {
			list($start, $end) = $this->createWrapping($htmlClassName);

			$html = $start . $this->createHeader() . $this->createBody() . $end;
			
			
			return $html;
		}
		
		protected function createWrapping($htmlClassName="") {
			$start = '<table class="datagrid grid '.$htmlClassName.'">';
			$end = '</table>';
			
			return array($start, $end);
		}
		
		protected function createHeader() {
			$header  = '<thead><tr class="tableHeader">';
			if ($this->addBatchSelect) {
				$header .= '<th class="batch"><input type="checkbox" onclick="jQuery(\'.batchSelect_' . $this->batchSelectId . '\').attr(\'checked\', this.checked?\'checked\':\'\');"></th>';
			}
			foreach ($this->columnHeadings as $name => $heading) {
				$header .= '<th class="'.$name.'">' . $heading . '</th>';
			}
			$header .= '</tr></thead>';
			
			return $header;
		}
		
		protected function createBody() {
			$html = '';
			$count = 0;
			foreach ($this->datasource as $object) {
				$count++;
				$className = $object->getTableRowCssClass();
				$onclick = '';
				$trClasses = array($className);
				
				$html .= '<tr class="' . implode(' ', $trClasses) . '">';
				if ($this->addBatchSelect) {
					$html .= '<td><input value="1" class="batchSelect_' . $this->batchSelectId . '" id="' . $this->batchSelectId . '_' . $object->{$object->primaryKey} . '" type="checkbox" name="' . $this->batchSelectId . '[' . $object->{$object->primaryKey} . ']" /></td>';
				}
				
				foreach ($this->columnHeadings as $name => $null) {
					if (in_array($name, array_keys($this->customColumns))) {
						$value = $this->customColumns[$name];
						$value = str_replace('{ID}', $object->{$object->primaryKey}, $value);
					} else {
						if (strpos($name, '.') !== false) {
							$parts = explode('.', $name);
							$source = '$object';
							foreach ($parts as $part) {
								$source .= '->' . $part;
							}
						} else {
							$source = '$object->$name';
						}
						eval('$value = ' . $source . ';');
						if ($callback = $this->columnCallbacks[$name]) {
							$value = call_user_func($callback,$value);
						}
						$value = htmlspecialchars($value);
					}
					
					if(isset($object->columnTypes[$name]))
					{
						switch($object->columnTypes[$name])
						{
							case "DATE" : $value = $this->locale->sqlToString($value);
								break;
							case 'TIMEDIFF':
								$value = $this->locale->sqlToTimeDiff($value);
								break;
						}
					}
					
					if ($this->columnCssClass[$name]) {
						$classes = array((string) $this->columnCssClass[$name]);
					} else {
						$classes = array();
					}
					
					$onclick = '';
					if ($url = $this->columnClickUrlTemplate[$name]) {
						$url = str_replace('{ID}', $object->{$object->primaryKey}, $url);
						$url = json_encode($url);
						$onclick = 'onclick=\'location.href=' . $url . '\'';
						$classes[] = 'clickable';
					}
					
					
					$html .= '	<td class="' . implode(' ', $classes) . '" ' . $onclick . '>' . $value . '</td>';
				}
				
				$html .= '</tr>';
			}
			
			if ($count) {
			} else {
				$colSpan = sizeof($this->columnHeadings);
				if ($this->addBatchSelect) {
					$colSpan += 1;
				}
				$html .= '<tr class="tableNoElements"><td colspan="' . $colSpan . '">' . _t('No elements') . '</td></tr>';
			}
			
			return $html;
		}
	}
?>

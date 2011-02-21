<?php
	/**
	*	Create a Grid user interface for browsing and managing a SERIA_MetaQuery
	*/
	class SERIA_MetaGrid
	{
		protected $_query, $_buttons = array(), $rowClick=false, $_activeOrdering = false;

		function __construct(SERIA_MetaQuery $query)
		{
			$this->_query = $query;
		}

		/**
		 *
		 * Returns an array of information about a SERIA_MetaGrid. This is not
		 * actually only a SERIA_MetaGrid object, but also info about the call to
		 * the output method that generated an anctual display of a SERIA_MetaGrid.
		 *
		 * @param string $key
		 * @return array
		 */
		public static function restoreFromCache($key)
		{
			$cache = new SERIA_Cache('SERIA_MetaGrid');
			$data = $cache->get($key);
			if ($data)
				return unserialize($data);
			else
				return null;
		}

		function addButton($caption, $url, $weight=0)
		{
			$this->_buttons[] = array('caption' => $caption, 'url' => $url, 'weight' => $weight);
			return $this;
		}

		/**
		*	Makes all rows clickable with the default template. Format:
		*	->rowClick('edit.php?id=%id%')
		*/
		function rowClick($urlTemplate)
		{
			$this->_rowClick = $urlTemplate;
			return $this;
		}

		public function userRequestedOrdering($order)
		{
			if ($this->_activeOrdering != $order)
				$this->_activeOrdering = $order;
			else
				$this->_activeOrdering = $order.' DESC';
			$this->_query->order($this->_activeOrdering);
		}

		/**
		 *
		 * Output only the tbody contents in order to make it useable by AJAX calls
		 * to modify parameters to the underlying query.
		 * @param unknown_type $columnSpec
		 * @param unknown_type $templateOrCallback
		 * @param unknown_type $pageSize
		 * @throws SERIA_Exception
		 */
		public function outputTBodyContent($columnSpec, $templateOrCallback = NULL, $pageSize=false)
		{
			$metaSpec = $this->_query->getSpec();
			$fieldSpec = $metaSpec['fields'];

			$columnFields = array();
			$columnWidths = array();

			$r = '';

			foreach($columnSpec as $fieldName => $spec)
			{
				if(is_numeric($fieldName))
				{
					$columnFields[] = $fieldName = $spec;
					$columnWidths[] = $width = false;
				}
				else
				{
					$columnFields[] = $fieldName;
					$columnWidths[] = $width = $spec;
				}
			}

			$rowNumber = 0;

			if(is_callable($templateOrCallback))
			{
				foreach($this->_query as $object)
				{
					$rowNumber++;
					$r .= call_user_func($templateOrCallback, $object);
				}
			}
			else if($templateOrCallback !== NULL)
			{
				$className = $this->_query->className;

				preg_match_all('|%([a-zA-Z0-9_]*)%|', $templateOrCallback, $matches);

				$find = array();
				$get = array();

				foreach($matches[0] as $key => $word)
				{
//					if($matches[1][$key] != $metaSpec['primaryKey'])
//					{
						$find[] = $word;
						$get[] = $matches[1][$key];
//					}
				}
				$find[] = '%'.$metaSpec['primaryKey'].'%';

				try
				{

					foreach($this->_query as $object)
					{
						$values = array();
						foreach($get as $word)
							$values[] = $object->get($word, true);
						$values[] = $object->MetaBackdoor('get_key');
						$rowNumber++;
						$r .= str_replace($find, $values, $templateOrCallback);
					}
				}
				catch (SERIA_Exception $e)
				{
					throw new SERIA_Exception('Most likely you have not specified all used fields in the ::Meta() method of your MetaObject. The failed property was "'.$word.'".');
				}

			}
			else
			{
				if($this->_rowClick)
				{
					$m = call_user_func($this->_query->className, "Meta");
					$find = array_keys($m['fields']);
					$find2 = array();
					if(isset($m['primaryKey']) && !isset($m['fields'][$m['primaryKey']]))
					{ // field name of primary key must be replacable.
						$find[] = $m['primaryKey'];
					}
					else if(!isset($m['fields']['id']))
					{ // id must be replacable, even if it is not specified in fields
						$find[] = 'id';
					}
					foreach($find as $k => $v)
					{
						$find[$k] = "%".$v."%";
						$find2[] = rawurlencode("%".$v."%");
					}
				}
				foreach($this->_query as $row)
				{
					$rowNumber++;
					if($this->_rowClick)
					{
						$replace = array();
						foreach($find as $k => $v)
						{
							// possibly this gives error in case of objects returned, should
							// then use $row->get($k)->_toString()
							$replace[] = rawurlencode($row->get(trim($v, "%")));
						}
						$url = str_replace($find,$replace,$this->_rowClick);
						$url = str_replace($find2, $replace, $url);
						$r .= "<tr onclick=\"location.href='".$url."'\">";
					}
					else
					{
						$r .= "<tr>";
					}
					foreach($columnFields as $i => $fieldName)
					{
						$r .= "<td>".(!empty($fieldSpec[$fieldName]['tpl'])?str_replace('%%', htmlspecialchars($row->get($fieldName, true)), $fieldSpec[$fieldName]['tpl']):htmlspecialchars($row->get($fieldName, true)))."</td>";
					}
					$r .= "</tr>";
				}
			}

			if($rowNumber < $pageSize)
			{ // fill in empty rows for user friendlyness
				for(; $rowNumber < $pageSize; $rowNumber++)
					$r .= '<tr class="empty"><td>&nbsp;</td><td></td></tr>';
			}

			return $r;
		}

		/**
		 *
		 * Caches the SERIA_MetaGrid object along with parameters to the output-method. In order
		 * to recreate the output-call in AJAX calls.
		 * @param unknown_type $columnSpec
		 * @param unknown_type $templateOrCallback
		 * @param unknown_type $pageSize
		 */
		public function serializeOutputCall($columnSpec, $templateOrCallback = NULL, $pageSize=false)
		{
			/*
			 * Cache a serialized object to allow AJAX-operations.
			 */
			$serialized = serialize(array(
				'REQUEST_URI' => $_SERVER['REQUEST_URI'], /* Override the REQUEST_URI in AJAX calls to allow SERIA_Url::current() and similar. */
				'object' => $this,
				'callableTemplate' => is_callable($templateOrCallback),
				'args' => array(
					'columnSpec' => $columnSpec,
					'templateOrCallback' => $templateOrCallback,
					'pageSize' => $pageSize
				)
			));
			$serialize_key = sha1($serialized);
			$cache = new SERIA_Cache('SERIA_MetaGrid');
			if (!$cache->get($serialize_key))
				$cache->set($serialize_key, $serialized, 360000);
			return $serialize_key;
		}

		function output($columnSpec, $templateOrCallback = NULL, $pageSize=false)
		{
			$serialize_key = $this->serializeOutputCall($columnSpec, $templateOrCallback, $pageSize);

			$metaSpec = $this->_query->getSpec();
			$fieldSpec = $metaSpec['fields'];

			SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/components/SERIA_Mvc/js/SERIA_MetaGrid.js');
			$table_id = sha1(mt_rand().mt_rand().$serialize_key);
			$r = "<script type='text/javascript'>\n"
			   . " <!--\n"
			   . "   if (typeof(SERIA_MetaGrid_serialized) == 'undefined')\n"
			   . "    var SERIA_MetaGrid_serialized = {};\n"
			   . '   SERIA_MetaGrid_serialized['.SERIA_Lib::toJSON($table_id).'] = '.SERIA_Lib::toJSON($serialize_key).";\n"
			   . " -->\n"
			   . "</script>\n";
			
			$r .= '<table id="'.$table_id.'" class="grid" style="width: 100%"><thead><tr>';

			$columnFields = array();
			$columnWidths = array();

			foreach($columnSpec as $fieldName => $spec)
			{
				if(is_numeric($fieldName))
				{
					$columnFields[] = $fieldName = $spec;
					$columnWidths[] = $width = false;
				}
				else
				{
					$columnFields[] = $fieldName;
					$columnWidths[] = $width = $spec;
				}

				if(!isset($fieldSpec[$fieldName]))
				{
					$r .= "<th ".($width!==false?'style="width:'.$width.(strpos($width, 'px')===false?'px':'').'"':'').">".htmlspecialchars(strip_tags($fieldName))."</th>";
				}
				else
				{
					if(!isset($fieldSpec[$fieldName]['caption']))
						throw new SERIA_Exception('Property "caption" not specified for attribute "'.$fieldName.'" '.$this->_query->className);
					$heading = htmlspecialchars(strip_tags($fieldSpec[$fieldName]['caption']));
					if (isset($fieldSpec[$fieldName]['sortable']) && $fieldSpec[$fieldName]['sortable'])
						$heading = "<a onclick=\"".htmlspecialchars('SERIA.MetaGrid.sortTableBy(document.getElementById('.SERIA_Lib::toJSON($table_id).'), '.SERIA_Lib::toJSON($fieldName).'); return false;')."\" href=''>".$heading."</a>";
					$r .= "<th ".($width!==false?'style="width:'.$width.(strpos($width, 'px')===false?'px':'').'"':'').">".$heading."</th>";
				}
			}

			$r .= "</tr></thead><tbody>";

			$r .= $this->outputTBodyContent($columnSpec, $templateOrCallback, $pageSize);

			$r .= "</tbody></table>";

			if(sizeof($this->_buttons))
			{
				$buttons = array();
				foreach($this->_buttons as $button)
				{
					$buttons[] = '<input type="button" value="'.htmlspecialchars($button['caption']).'" onclick="location.href=\''.$button['url'].'\'">';
				}
				$r .= SERIA_GuiVisualize::toolbar($buttons);
			}

			return $r;
		}
	}

<?php
	class SERIA_FluentGrid
	{
		private $_query, $_buttons = array(), $rowClick=false;

		function __construct(SERIA_FluentQuery $query)
		{
			$this->_query = $query;
		}

		function addButton($caption, $url, $weight=0)
		{
			$this->_buttons[] = array('caption' => $caption, 'url' => $url, 'weight' => $weight);
			return $this;
		}

		function rowClick($urlTemplate)
		{
			$this->_rowClick = $urlTemplate;
		}

		function output($columnSpec, $templateOrCallback = NULL)
		{
			$fieldSpec = $this->_query->getFieldSpec();

			$r = '<table class="grid" style="width: 100%"><thead><tr>';

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
					$r .= "<th ".($width!==false?'style="width:'.$width.'"':'').">".htmlspecialchars($fieldName)."</th>";
				}
				else
				{
					if(!isset($fieldSpec[$fieldName]['caption']))
						throw new SERIA_Exception('Property "caption" not specified for attribute "'.$fieldName.'" '.$this->_query->className);
					$r .= "<th ".($width!==false?'style="width:'.$width.'"':'').">".htmlspecialchars($fieldSpec[$fieldName]['caption'])."</th>";
				}
			}

			$r .= "</tr></thead><tbody>";

			if(is_callable($templateOrCallback))
			{
				foreach($this->_query as $object)
				{
					$r .= $templateOrCallback($object);
				}
			}
			else if($templateOrCallback !== NULL)
			{
				$className = $this->_query->className;
				$fis = $this->_query->getFieldSpec();
				eval('
					$fls = '.$className.'::fluentSpec();
				');

				preg_match_all('|%([a-zA-Z0-9_]*)%|', $templateOrCallback, $matches);

				$find = array();
				$get = array();

				foreach($matches[0] as $key => $word)
				{
					if($matches[1][$key] != $fls['primaryKey'])
					{
						$find[] = $word;
						$get[] = $matches[1][$key];
					}
				}
				$find[] = '%'.$fls['primaryKey'].'%';



				try
				{
					foreach($this->_query as $object)
					{
						$values = array();
						foreach($get as $word)
							$values[] = $object->get($word);
						$values[] = $object->getKey();
						$r .= str_replace($find, $values, $templateOrCallback);
					}
				}
				catch (SERIA_Exception $e)
				{
					throw new SERIA_Exception('Most likely you have not specified all used fields in the ::getFieldSpec() method of your FluentObject. The failed property was "'.$word.'".');
				}
			}
			else
			{
				foreach($this->_query as $row)
				{
					$r .= "<tr>";
					foreach($columnFields as $i => $fieldName)
					{
						$r .= "<td>".(!empty($fieldSpec[$fieldName]['tpl'])?str_replace('%%', htmlspecialchars($row->get($fieldName)), $fieldSpec[$fieldName]['tpl']):htmlspecialchars($row->get($fieldName)))."</td>";
					}
					$r .= "</tr>";
				}
			}

			$r .= "</tbody></table>";

			if(sizeof($this->_buttons))
			{
				$r .= '<div class="gridButtons">';
				foreach($this->_buttons as $button)
				{
					$r .= '<input type="button" value="'.htmlspecialchars($button['caption']).'" onclick="location.href=\''.$button['url'].'\'">';
				}
				$r .= '</div>';
			}

			return $r;
		}

		
	}

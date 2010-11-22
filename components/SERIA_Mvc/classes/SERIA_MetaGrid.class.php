<?php
	/**
	*	Create a Grid user interface for browsing and managing a SERIA_MetaQuery
	*/
	class SERIA_MetaGrid
	{
		private $_query, $_buttons = array(), $rowClick=false;

		function __construct(SERIA_MetaQuery $query)
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

		function output($columnSpec, $templateOrCallback = NULL, $pageSize=false)
		{
			$metaSpec = $this->_query->getSpec();
			$fieldSpec = $metaSpec['fields'];

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
					$r .= "<th ".($width!==false?'style="width:'.$width.(strpos($width, 'px')===false?'px':'').'"':'').">".htmlspecialchars(strip_tags($fieldName))."</th>";
				}
				else
				{
					if(!isset($fieldSpec[$fieldName]['caption']))
						throw new SERIA_Exception('Property "caption" not specified for attribute "'.$fieldName.'" '.$this->_query->className);
					$r .= "<th ".($width!==false?'style="width:'.$width.(strpos($width, 'px')===false?'px':'').'"':'').">".htmlspecialchars(strip_tags($fieldSpec[$fieldName]['caption']))."</th>";
				}
			}

			$r .= "</tr></thead><tbody>";

			$rowNumber = 0;

			if(is_callable($templateOrCallback))
			{
				foreach($this->_query as $object)
				{
					$rowNumber++;
					$r .= $templateOrCallback($object);
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
				foreach($this->_query as $row)
				{
					$rowNumber++;
					$r .= "<tr>";
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

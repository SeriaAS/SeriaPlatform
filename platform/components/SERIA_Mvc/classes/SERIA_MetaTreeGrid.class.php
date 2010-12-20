<?php
	/**
	*	Create a Grid user interface for browsing and managing a SERIA_MetaQuery
	*/
	class SERIA_MetaTreeGrid extends SERIA_MetaGrid
	{
		protected $_parentIdColumn;

		function __construct(SERIA_MetaQuery $query, $parentIdColumn)
		{
			if(!$parentIdColumn) throw new Exception('Syntax error; $parentIdColumn is a required arameter.');
			$this->_parentIdColumn = $parentIdColumn;
			parent::__construct($query);
		}

		/**
		*	@param $templateOrCallback	Either a string with %columnName% or a callback that will receive a SERIA_MetaObject as a parameter
		*/
		function output($columnSpec, $templateOrCallback = NULL, $pageSize=false, $parentId=NULL, $columnFields=NULL, $columnWidths=NULL, $_depth=0)
		{
			$metaSpec = $this->_query->getSpec();
			$fieldSpec = $metaSpec['fields'];

			if($_depth===0)
			{
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
			}
			else
			{
				$r = '';
			}

			// GENERATE THE TABLE ROWS

			$rowNumber = 0;
			// clone the query object
			$query = unserialize(serialize($this->_query));
			// FETCH CHILD ROWS IF THIS IS A SUB TABLE
			if($parentId===NULL)
			{
				$query->where('('.$this->_parentIdColumn.' IS NULL OR '.$this->_parentIdColumn.' = 0)');
			}
			else
			{
				$query->where($this->_parentIdColumn.'= :_parentId', array('_parentId' => $parentId));
				if($query->count()===0) return NULL;
			}

			if($templateOrCallback === NULL)
			{ // create the template for the developer
				$method = 'template';
				$templateOrCallback = '<tr>'; // template for each row

				$find = array();
				$get = array();

				foreach ($columnSpec as $fieldName) {
					$templateOrCallback .= '<td>%'.$fieldName.'%</td>';
					$find[] = '%'.$fieldName.'%';
					$get[] = $fieldName;
				}
				$templateOrCallback .= '</tr>';

				$find[] = '%'.$metaSpec['primaryKey'].'%';
			}
			else if(is_callable($templateOrCallback))
			{ // use the developer provided callback
				$method = 'callback';
			}
			else
			{ // use the developer provided template
				$method = 'template';
				preg_match_all('|%([a-zA-Z0-9_]*)%|', $templateOrCallback, $matches);

				$find = array();
				$get = array();

				foreach($matches[0] as $key => $word)
				{
					$find[] = $word;
					$get[] = $matches[1][$key];
				}
				$find[] = '%'.$metaSpec['primaryKey'].'%';
			}

			if($this->_rowClick)
			{
				preg_match_all('|%([a-zA-Z0-9_]*)%|', $this->_rowClick, $matches);

				$rowClickFind = array();
				$rowClickGet = array();

				foreach($matches[0] as $key => $word)
				{
					$rowClickFind[] = $word;
					$rowClickGet[] = $matches[1][$key];
				}
				$rowClickFind[] = '%'.$metaSpec['primaryKey'].'%';
			}


//			$expander = "<span class='texticon' onclick='document.getElementById(\"childGrid%id%\").style.display=\"block\";this.innerHTML=\"-\";event.cancelBubble=true;event.stopPropagation();alert(123);return false;'>+</span>";
//			$expander = "<span class='texticon' onclick='document.getElementById(\"childrenGrid%id%\").style.display=\"block\"; event.cancelBubble=true;event.stopPropagation();alert(123);return false;'>+</span>";

			$expander = '<span style="position:absolute"><span class="texticon" style="position:relative; left:-20px;font-family: courier, monospace;font-weight:bold;" onclick="if(this._son) jQuery(\'#childrenGrid%id%\').fadeOut(400); else jQuery(\'#childrenGrid%id%\').fadeIn(400); this._son = !this._son; jQuery(this).html(this._son?\'-\':\'+\'); event.cancelBubble=true;event.stopPropagation();">+</span></span>';
			$collapser = '<span style="position:absolute"><span class="texticon" style="position:relative; left:-20px;font-family: courier, monospace;font-weight:bold;" onclick="%COLLAPSERCLICK%;event.cancelBubble=true;event.stopPropagation();">-</span></span>';
			$marker = '<span style="position:absolute"><span class="texticon" style="position:relative; left:-20px;font-family: courier, monospace;font-weight:bold;" onclick="event.cancelBubble=true;event.stopPropagation();">&nbsp;</span></span>';

			// Generate the rows
			$rows = '';
			foreach($query as $object)
			{
				// generate the row
				switch($method)
				{
					case 'template' :
						$replace = array();
						foreach($get as $key)
							$replace[] = $object->get($key);
						$replace[] = $object->MetaBackdoor('get_key');
						$row = str_replace($find, $replace, $templateOrCallback);
						break;
					case 'callback' :
						$row = call_user_func($templateOrCallback, $object);
						break;
				}

				$children = new SERIA_MetaTreeGrid($this->_query, $this->_parentIdColumn);
				$childrenGrid = $children->output($columnSpec, $templateOrCallback, $pageSize, $object->MetaBackdoor('get_key'), $columnFields, $columnWidths, $_depth+1);

				$row = trim($row);

				$tr = new SERIA_HtmlTag($row);
				$trConsumed = SERIA_HtmlTag::getBytesConsumed();
				$rest = substr($row, $trConsumed);

				$td = new SERIA_HtmlTag($rest, true);
				$tdConsumed = SERIA_HtmlTag::getBytesConsumed();
				$rest = substr($rest, $tdConsumed);

				$style = 'padding-left:'.(20+($_depth*10)).'px';
				if($existing = $td->get('style'))
				{
					$td->set('style', rtrim($existing,';').';'.$style);
				}
				else
				{
					$td->set('style', $style);
				}

				if($this->_rowClick)
				{ // i must insert the rowClick code in $tr
					$toInsert = $this->_rowClick;
					$rowClickReplace = array();
					foreach($rowClickGet as $key)
						$replace[] = $object->get($key);
					$rowClickReplace[] = $object->MetaBackdoor('get_key');

					$toInsert = str_replace($rowClickFind, $rowClickReplace, $toInsert);

					if($existing = $tr->get('onclick'))
					{
						$tr->set('onclick', rtrim($toInsert,';').";".$existing);
					}
					else
					{
						$tr->set('onclick', $toInsert);
					}
				}


				if($childrenGrid)
				{
					$row = $tr->__toString().$td->__toString().str_replace('%id%',$object->MetaBackdoor('get_key'),$expander).$rest;
					$rows .= $row;
					$rows .= '</tbody><tbody id="childrenGrid'.$object->MetaBackdoor('get_key').'" style="border-top: 2px solid #999; border-bottom: 1px solid #999;display: none">'.$childrenGrid.'</tbody><tbody>';
				}
				else
				{
					$row = $tr->__toString().$td->__toString().$marker.$rest;
					$rows .= $row;
				}

// deeper?

			}
/*

			if(is_callable($templateOrCallback))
			{
				foreach($query as $object)
				{
					$rowNumber++;
					$row = $templateOrCallback($object);
					// inject first column that allows expanding
					$p = stripos($row, "<td");
					$p = strpos($row, ">", $p+1)+1;
					if($p===false)
						throw new SERIA_Exception('Template must include the &lt;td&gt;-tag. Will inject a custom &lt;td&gt;-tag first in the html for expanding the tree.');

					$children = new SERIA_MetaTreeGrid($this->_query, $this->_parentIdColumn);
					$childrenGrid = $children->output($columnSpec, $templateOrCallback, $pageSize, $object->MetaBackdoor('get_key'), $columnFields, $columnWidths, $_depth++);

					if($childrenGrid)
					{
						$after = '</tbody><tbody id="gridchild'.$object->MetaBackdoor('get_key').'" style="border-top: 2px solid #999; border-bottom: 1px solid #999;display: none">'.$childrenGrid.'</tbody><tbody>';
						$icon = '<span class="texticon" style="font-family: courier, monospace;font-weight:bold;" onclick="if(this._son) jQuery(\'#gridchild'.$object->MetaBackdoor('get_key').'\').fadeOut(400); else jQuery(\'#gridchild'.$object->MetaBackdoor('get_key').'\').fadeIn(400); this._son = !this._son; jQuery(this).html(this._son?\'-\':\'+\'); event.cancelBubble=true;event.stopPropagation();">+</span>';
					}
					else
					{
						$after = '';
						$icon = '<span class="texticon" style="font-family: courier, monospace;font-weight:bold;">&nbsp;</span>';
					}

					$row = substr($row, 0, $p)."$icon".substr($row, $p);
					$r .= $row.$after;
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
					$find[] = $word;
					$get[] = $matches[1][$key];
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

						$row == str_replace($find, $values, $templateOrCallback);

						$p = stripos($row, "<td");
						if($p===false)
							throw new SERIA_Exception('Template must include the &lt;td&gt;-tag. Will inject a custom &lt;td&gt;-tag first in the html for expanding the tree.');
						$row = substr($row, 0, $p)."<td>".str_replace('%EXPANDERCLICK%',$click,$expander)."</td>".substr($row, $p);
						$r .= $row;
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
					if(isset($m['primaryKey']) && !isset($m['fields'][$m['primaryKey']]))
					{ // field name of primary key must be replacable.
						$find[] = $m['primaryKey'];
					}
					else if(!isset($m['fields']['id']))
					{ // id must be replacable, even if it is not specified in fields
						$find[] = 'id';
					}
					foreach($find as $k => $v)
						$find[$k] = "%".$v."%";
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
						$r .= "<tr onclick=\"location.href='".str_replace($find,$replace,$this->_rowClick)."'\">";
					}
					else
					{
						$r .= "<tr>";
					}
					$r .= "<td>".str_replace('%EXPANDERCLICK%',$click,$expander)."</td>";
					foreach($columnFields as $i => $fieldName)
					{
						$r .= "<td>".(!empty($fieldSpec[$fieldName]['tpl'])?str_replace('%%', htmlspecialchars($row->get($fieldName, true)), $fieldSpec[$fieldName]['tpl']):htmlspecialchars($row->get($fieldName, true)))."</td>";
					}
					$r .= "</tr>";
				}
			}
*/

			if($rowNumber < $pageSize)
			{ // fill in empty rows for user friendlyness
				for(; $rowNumber < $pageSize; $rowNumber++)
					$rows .= '<tr class="empty"><td colspan="'.sizeof($columnFields).'">&nbsp;</td></tr>';
			}

			$r .= $rows;

			if($_depth===0)
			{
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
			}

//$r = str_replace('%REPLACE%', '<tr><td colspan="4">'.str_replace('%REPLACE%','',$r).'</td></tr>', $r);

			return $r;
		}

		
	}

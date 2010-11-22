<?php

class SERIA_Table
{
	private $settings;
	private $theadDone = false;
	private $tdata = false;
	private $sortdata = false;
	private $inFooter = false;
	private $valueEval = false;
	private $outputY = false;

	private $lastKeyIndex = false;
	private $keyIndexField = false;
	private $keyIndex = array();

	private $templates = array(
		"default" => array(
			"gridWidth" => 150,
			"headingTextAlign" => "left",
			"title" => "",
			"sortable" => true
		),
		"text" => array(
			"gridWidth" => 200
		),
		"number" => array(
			"gridWidth" => 60
		),
		"date" => array(
			"gridWidth" => 80
		),
		"datetime" => array(
			"gridWidth" => 120
		),
		"checkbox" => array(
			"gridWidth" => 30,
			"value" => 'return "<input type=\'checkbox\' name=\'".$cellName."\' value=\'".$value."\'>";'
		)
	);

	function __construct($settings, $tdata=false, $sortdata=false)
	{
		$this->settings = $settings;

		foreach($this->settings["fields"] as $name => $field) {
			if (isset($field["type"])) {
				$ftype = $field["type"];
				if (!isset($this->templates[$ftype]))
					$ftype = false;
			} else
				$ftype = false;
			if ($ftype !== false) {
				$typeDef = $this->templates[$ftype];
				$field = SERIA_Lib::array_merge_recursive_unique($typeDef, $field);
			}
			$typeDef = $this->templates["default"];
			$this->settings["fields"][$name] = SERIA_Lib::array_merge_recursive_unique($typeDef, $field);
			if (isset($this->settings["fields"][$name]["value"]))
				$this->valueEval = true;
			if (isset($this->settings['fields'][$name]['keyIndex']) && $this->settings['fields'][$name]['keyIndex'] === true)
				$this->keyIndexField = $name;
		}
		if ($tdata!=false)
			$this->tdata = $tdata;
		else
			$this->tdata = array();
		if ($sortdata!=false) {
		        $numsort = min(count($tdata), count($sortdata));
			if ($numsort == count($sortdata))
			        $this->sortdata = $sortdata;
			else {
			        $this->sortdata = array();
				for ($i = 0; $i < $numsort; $i++)
				        $this->sortdata[$i] = $sortdata[$i];
				while ($i < count($this->tdata)) {
				        $this->sortdata[$i] = false;
				        $i++;
				}
			}
		} else {
			$this->sortdata = array();
			for ($i = 0; $i < count($this->tdata); $i++)
			    	$this->sortdata[$i] = false;
		}
		$outputY = 0;
		if (!isset($this->settings['html_id']))
			$this->settings['html_id'] = 'SERIA_Table_'.mt_rand();
	}

	public function addRow($row, $sortdata=false)
	{
		if ($this->valueEval) {
			$defs = array();
			foreach ($this->settings["fields"] as $name => $field) {
				$field["__fieldName"] = $name;
				$defs[] = $field;
			}
			$insrow = array();
			$colspans = array();
			$spanindex = 0;
			$defIndex = 0;
			$rowData = array();
			foreach ($row as $name => $cell) {
				$rowData[$name] = $cell;
				if ($name === "colspans") {
					$colspans = $cell;
					$spanindex = 0;
					continue;
				}
				if (isset($colspans[$spanindex])) {
					$colspan = $colspans[$spanindex];
					$spanindex++;
					if ($colspan < 1)
						$colspan = 1;
				} else
					$colspan = 1;
				if (isset($defs[$defIndex]["value"])) {
					$cellName = $name;
					$name = $defs[$defIndex]["__fieldName"];
					$value = $cell;
					$rowData[$cellName] = eval($defs[$defIndex]["value"]);
					$name = $cellName;
				}
				$defIndex += $colspan;
			}
			$this->tdata[] = $rowData;
		} else
			$this->tdata[] = $row;
		$this->sortdata[] = $sortdata;
	}
	public function addRows($rows, $sortdata)
	{
		for ($i = 0; isset($rows[$i]); $i++) {
		    	if ($sortdata !== false && isset($sortdata[$i]))
			   	$sdata = $sortdata[$i];
			else
				$sdata = false;
			$this->addRow($rows[$i], $sdata);
		}
	}

	private function outputTHead()
	{
		$hasSortable = false;
		foreach($this->settings["fields"] as $field) {
			if (isset($field['sortable']) && $field['sortable']) {
				$hasSortable = true;
				break;
			}
		}
		$tableHTMLClasses = array('grid');
		if ($hasSortable)
			$tableHTMLClasses[] = 'tablesorter';
		$table_params = array(" class='".implode(' ', $tableHTMLClasses)."'");
		if (isset($this->settings["html_id"]))
			$table_params[] = " id='".$this->settings['html_id']."'";
		$output = "<table".implode('',$table_params).">\n\t<thead>\n\t\t<tr>";
		foreach ($this->settings["fields"] as $field) {
			$style = " style='width: " . $field["gridWidth"] . "px; text-align: " . $field["headingTextAlign"] . ";";
			$style .= "'";
			$output .= "<th" . $style . ">" . $field["title"] . "</th>";
		}
		$output .= "</tr>\n\t</thead>\n\t<tbody>\n";
		$this->theadDone = true;
		return $output;
	}
	public function processPartial($rows=false)
	{
		if ($rows !== false)
			$this->addRows($rows);
		$output = "";
		if (!$this->theadDone)
			$output .= $this->outputTHead();
		$outputY = 0;
		$rowIndex = 0;
		$defs = array();
		foreach ($this->settings["fields"] as $name => $field) {
			$field["__fieldName"] = $name;
			$defs[] = $field;
		}
		while (isset($this->tdata[$outputY])) {
		      	$row = $this->tdata[$outputY];
			if (isset($this->sortdata[$outputY])) {
				$sortdata = $this->sortdata[$outputY];
				if ($sortdata === false)
				   	$sortdata = array();
			} else
				$sortdata = array();
			$output .= "\t\t<tr";
			if (isset($row['class']))
				$output .= ' class=\''.$row['class'].'\'';
			$output .= '>';
			$colspans = array();
			$spanindex = 0;
			$colindex = 0;
			foreach ($row as $name => $cell) {
				if ($name === "colspans") {
					$colspans = $cell;
					$spanindex = 0;
					continue;
				}
				if ($name === 'class')
				   	continue;
		      		$coldef = $defs[$colindex];
		      		$anchor_pre = '';
		      		$anchor_post = '';
		      		if ($this->keyIndexField !== false && $this->keyIndexField == $coldef['__fieldName']) {
		      			if (isset($sortdata[$name]) && $sortdata[$name] !== false)
		      				$str = $sortdata[$name];
		      			else
		      				$str = $cell;
		      			$fch = substr($str, 0, 1);
		      			if ($fch != $this->lastKeyIndex) {
		      				$this->lastKeyIndex = $fch;
		      				$this->keyIndex[] = $fch;
		      				$anchor_pre = '<a name=\'letter_'.strtolower($fch).'\'>';
		      				$anchor_post = '</a>';
		      			}
		      		}
				if (isset($colspans[$spanindex])) {
					$colspan = $colspans[$spanindex];
					$spanindex++;
					if ($colspan < 1)
						$colspan = 1;
				} else
					$colspan = 1;
				if (isset($sortdata[$name]) && $sortdata[$name] !== false)
					$sortcode = "<input type='hidden' value='".$sortdata[$name]."'>";
				else
					$sortcode = '';
				$td_params = array();
				if ($colspan != 1)
					$td_params[] = " colspan='".$colspan."'";
				if (isset($this->settings['clickCallback']))
					$td_params[] = " onclick='".$this->settings['clickCallback']."("
					             . $colindex . "," . $outputY . ");'";
				$output .= "<td " . implode('', $td_params) . ">" . $sortcode . $anchor_pre . $cell . $anchor_post . "</td>";
				$colindex += $colspan;
			}
			$output .= "</tr>\n";
			$outputY++;
		}
		$this->tdata = array();
		$rowIndex = 0;
		$sortdata = array();
		while (isset($this->sortdata[$outputY])) {
			$sortdata[$rowIndex] = $this->sortdata[$outputY];
			$outputY++;
			$rowIndex++;
		}
		$this->sortdata = $sortdata;
		return $output;
	}

	/*
	 * Run processPartial before this to flush the queue of rows.
	 */
	public function processFooter($rows=false)
	{
		if ($this->theadDone)
			$output = "";
		else 
			$output = $this->outputTHead();
		if (!$this->inFooter)
			$output .= "\t</tbody>\n\t<tfoot>\n";
		$this->inFooter = true;
		return $output . $this->processPartial($rows);
	}

	private function outputCellCSV($cellText)
	{
		$len = strlen($cellText);
		$plain = true;
		for ($i = 0; $i < $len; $i++) {
			$chval = ord($cellText[$i]);
			if (($chval >= ord('a') &&
			     $chval <= ord('z')) ||
			    ($chval >= ord('A') &&
			     $chval <= ord('Z')) ||
			    ($chval >= ord('0') &&
			     $chval <= ord('9')))
				continue;
			$plain = false;
			break;
		}
		if ($plain)
			return $cellText;
		return '"' . str_replace('"', '""', $cellText) . '"';
	}
	private function outputRowCSV($row)
	{
		$output = array();
		foreach ($row as $cellText)
			$output[] = $this->outputCellCSV($cellText);
		return implode(',', $output)."\n";
	}
	private function outputTHeadCSV()
	{
		$row = array();
		foreach ($this->settings["fields"] as $field)
			$row[] = $field['title'];
		$this->theadDone = true;
		return $this->outputRowCSV($row);
	}
	public function processCSV($rows=false)
	{
		if ($rows !== false)
			$this->addRows($rows);
		$output = "";
		if (!$this->theadDone)
			$output .= $this->outputTHeadCSV();
		$outputY = 0;
		$rowIndex = 0;
		while (isset($this->tdata[$outputY])) {
		      	$row = $this->tdata[$outputY];
			if (isset($this->sortdata[$outputY])) {
				$sortdata = $this->sortdata[$outputY];
				if ($sortdata === false)
				   	$sortdata = array();
			} else
				$sortdata = array();
			$colspans = array();
			$spanindex = 0;
			$colindex = 0;
			$csvRow = array();
			foreach ($row as $name => $cell) {
				if ($name === "colspans") {
					$colspans = $cell;
					$spanindex = 0;
					continue;
				}
				if (isset($colspans[$spanindex])) {
					$colspan = $colspans[$spanindex];
					$spanindex++;
					if ($colspan < 1)
						$colspan = 1;
				} else
					$colspan = 1;
				$td_params = array();
				$csvRow[] = $cell;
				for ($i = 1; $i < $colspan; $i++)
					$csvRow[] = '';
				$colindex += $colspan;
			}
			$output .= $this->outputRowCSV($csvRow);
			$outputY++;
		}
		$this->tdata = array();
		$rowIndex = 0;
		$sortdata = array();
		while (isset($this->sortdata[$outputY])) {
			$sortdata[$rowIndex] = $this->sortdata[$outputY];
			$outputY++;
			$rowIndex++;
		}
		$this->sortdata = $sortdata;
		return $output;
	}

	/*
	 * Always run output last to flush rows and terminate the HTML code for the table.
	 */
	public function output()
	{
		if (!$this->theadDone)
			$tdata = $this->tdata;
		else
			$tdata = array();
		$output = $this->processPartial();
		if (!$this->inFooter)
			$output .= "\t</tbody>\n";
		else
			$output .= "\t</tfoot>\n";
		$output .= "</table>\n"; 
		$this->tdata = $tdata;
		$this->theadDone = false;
		$this->inFooter = false;
		if (isset($this->settings['html_id'])) {
			$hasSortable = false;
			foreach($this->settings["fields"] as $field) {
				if (isset($field['sortable']) && $field['sortable']) {
					$hasSortable = true;
					break;
				}
			}
			$output .= "
<script type='text/javascript'>";
			if ($hasSortable)
				$output .= "
	$('#".$this->settings['html_id']."').tablesorter({
		textExtraction : function (node) {
			if (node.firstChild && node.firstChild.value)
				return node.firstChild.value;
			else
				return node.innerHTML;
		}
	});";
			if ($this->keyIndexField !== false)
				$output .= "
	document.getElementById('".$this->settings['html_id']."').onkeypress = function (ewt) {
		if (!ewt  && window.event) ewt = window.event;
		var chpress = String.fromCharCode(ewt.keyCode);
		window.location.hash = 'letter_'+chpress;
	}";
	$output .= "
</script>\n";
		}
		return $output;
	}
	public function outputCSV()
	{
		if (!$this->theadDone)
			$tdata = $this->tdata;
		else
			$tdata = array();
		$output = $this->processCSV();
		$this->tdata = $tdata;
		$this->theadDone = false;
		$this->inFooter = false;
		return $output;
	}
}

?>

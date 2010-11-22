<?php

class SERIA_DivBuilder extends SERIA_HTMLElement
{
	private $columns = false;
	private $rows = array();
	private $partRow = false;
	private $reverse = false;

	protected $ie6table = false; /* Use <table> for IE6 */

	public function __construct($settings)
	{
		$this->columns = $settings['columns'];
		$this->reverse = isset($settings['reverse']) && $settings['reverse'];
	}

	public function addContent($content)
	{
		if ($this->partRow == false) {
			$this->partRow = array();
			$index = count($this->rows);
		} else
			$index = count($this->rows)-1;
		$this->partRow[] = $content;
		$this->rows[$index] = $this->partRow;
		if (count($this->partRow) == count($this->columns))
			$this->partRow = false;
	}
	public function addChild($elem)
	{
		$this->addContent($elem);
	}
	public function addRow($row)
	{
		$i = 0;
		$trow = array();
		foreach ($row as $col) {
			if ($i >= count($this->columns))
				break;
			$trow[] = $col;
			$i++;
		}
		while ($i < count($this->columns)) {
			$trow[] = '';
			$i++;
		}
		$this->rows[count($this->rows)] = $trow;
	}
	public function addRows($rows)
	{
		foreach ($rows as $row) {
			if ($row !== false)
				$this->addRow($row);
		}
	}

	/**
	 * Sets wether <table> structures should be used to force IE6 to represent in a tabular structure.
	 * 
	 * @param $ie6table boolean
	 * @return unknown_type
	 */
	public function setIE6Table($ie6table)
	{
		$this->ie6table = $ie6table;
		return $this;
	}

	public function output()
	{
		$widthSet = true;
		$width = 0;
		foreach ($this->columns as $col) {
			if (!$widthSet)
				throw new Exception('All columns but the last must have width set.');
			if (isset($col['width']))
				$width += $col['width'];
			else
				$widthSet = false;
		}
		$style = '';
		if ($widthSet)
			$style .= 'width: '.$width.'px;';
		if ($style != '')
			$style = ' style=\''.$style.'\'';
		$output = '<div'.$style.">\n";
		$rows = $this->rows;
		if ($this->reverse)
			$rows = array_reverse($rows);
		foreach ($rows as $row) {
			$output .= "\t<div style='clear: both; overflow: hidden; width: 100%;'>\n";
			if ($this->ie6table)
				$outrow = seria_bml_iecond('lt IE 7')->addChild('<table cellspacing=\'0\'><tr>')->output();
			else
				$outrow = '';
			$i = 0;
			$cols = $this->columns;
			if ($this->reverse)
				$cols = array_reverse($cols);
			foreach ($cols as $col) {
				$cell = "\t\t<div style='float: left; height: 1px; min-height: 1px; height: auto !important;";
				if (isset($col['width']))
					$cell .= ' width: '.$col['width'].'px;';
				$classname = get_class($row[$i]);
				if (method_exists($row[$i], 'output'))
					$itemcode = $row[$i]->output();
				else
					$itemcode = $row[$i];
				$cell .= "'>\n".$itemcode."\n\t\t</div>\n";
				if ($this->ie6table)
					$cell = (seria_bml_iecond('lt IE 7')->addChild('<td style=\'vertical-align: top;\'>')->output()).$cell.(seria_bml_iecond('lt IE 7')->addChild('</td>')->output());
				$outrow .= $cell;
				$i++;
			}
			if ($this->ie6table)
				$outrow .= seria_bml_iecond('lt IE 7')->addChild('</tr></table>')->output();
			$output .= $outrow;
			$output .= "\t</div>\n";
		}
		return $output . "</div>\n";
	}
}

?>
<?php

class SERIA_BMLCompatDisplayTable extends SERIA_BMLElement
{
	private $numColumns;
	private $children = array();
	private $caption = false;
	private $tree = false;

	public function __construct($numColumns)
	{
		parent::__construct();
		$this->numColumns = $numColumns;
	}

	public function addChild($object)
	{
		$this->children[] = $object;
		$this->tree = false;
		return $this;
	}
	public function setText($str)
	{
		$this->caption = $str;
		$this->tree = false;
		return $this;
	}
	public function getText()
	{
		return $this->caption;
	}

	public function output()
	{
		if ($this->tree === false) {
			$children = $this->children; /* Make a copy */
			$rows = array();
			while ($children) {
				$row = array(seria_bml_iecond('lt IE 8')->addChild('<tr>'));
				for ($i = 0; $i < $this->numColumns; $i++)
					$row[] = seria_bml()->addChildren(array(
						seria_bml_iecond('lt IE 8')->addChild('<td>'),
						seria_bml('div')->setStyle('display', 'table-cell')->addChild($children ? array_shift($children) : seria_bml()),
						seria_bml_iecond('lt IE 8')->addChild('</td>')
					));
				$row[] = seria_bml_iecond('lt IE 8')->addChild('</tr>');
				$rows[] = seria_bml('div')->setStyle('display', 'table-row')->addChildren($row);
			}
			$this->tree = seria_bml('div')->setStyle('display', 'table')->addChildren(array(
				seria_bml_iecond('lt IE 8')->addChild('<table>'),
				seria_bml()->addChildren($rows),
				seria_bml_iecond('lt IE 8')->addChild('</table>')
			));
		}
		
		return $this->tree->output();
	}
}

?>
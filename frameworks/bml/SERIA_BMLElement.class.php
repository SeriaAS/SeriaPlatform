<?php

class SERIA_BMLElement
{
	private $elTag = false;
	private $params = array();
	private $text = false;
	private $children = array();
	private $style = array();

	protected $endPrefix = '</';

	public $noEndTag = false;

	public function __construct($elementTag=false, $params=array())
	{
		$this->elTag = $elementTag;
		foreach ($params as $parnam => $param) {
			if ($param !== false)
				$this->params[$parnam] = $parnam . '="' . htmlspecialchars($param) . '"';
			else {
				if (SERIA_XHTML != 0)
					$this->params[$parnam] = $parnam.'=""';
				else
					$this->params[$parnam] = $parnam;
			}
		}
	}

	public function setText($text)
	{
		$this->text = $text;
		return $this; /* jQuery inspired */
	}
	public function addChild($child)
	{
		if ($child !== false)
			$this->children[] = $child;
		return $this; /* jQuery inspired */
	}
	public function addChildren($children)
	{
		foreach ($children as $child)
			$this->addChild($child);
		return $this; /* jQuery inspired */
	}

	public function addInvisibleAccessText($text)
	{
		$this->addChild(new SERIA_BMLInvisibleAccessText($text));
		return $this; /* jQuery inspired */
	}

	public function setAttr($var, $val)
	{
		$this->params[$var] = $var . '="' . htmlspecialchars($val) . '"';
		return $this; /* jQuery inspired */
	}
	public function getAttr($var)
	{
		if (!isset($this->params[$var]))
			return false;
		$str = $this->params[$var];
		$len = strlen($var) + 2;
		$prefix = substr($str, 0, $len);
		$str = substr($str, $len);
		if ($prefix != ($var.'="'))
			throw new Exception('getAttr expects certain formatting rules inside the BMLElement-class');
		$len = strlen($str);
		$postfix = substr($str, $len - 1);
		if ($postfix != '"')
			throw new Exception('getAttr expects certain formatting rules inside the BMLElement-class ('.$str.','.$postfix.')');
		$str = substr($str, 0, $len - 1);
		return htmlspecialchars_decode($str);
	}
	public function setStyle($var, $val)
	{
		$this->style[$var] = $val;
		return $this; /* jQuery inspired */
	}

	/* Various style helpers */
	public function setWidth($width)
	{
		$len = strlen($width);
		if ($len > 0 && $width[$len-1] != '%' && $width[$len-1] != 'x')
			$width .= 'px';
		$this->setStyle('width', $width);
		return $this; /* jQuery inspired */
	}
	public function setHeight($height)
	{
		$len = strlen($height);
		if ($len > 0 && $height[$len-1] != '%' && $height[$len-1] != 'x')
			$height .= 'px';
		$this->setStyle('height', $height);
		return $this; /* jQuery inspired */
	}
	public function setBorder($borderSize, $borderColor, $borderStyle='solid')
	{
		$len = strlen($borderSize);
		if ($len > 0 && $borderSize[$len-1] != '%' && $borderSize[$len-1] != 'x')
			$borderSize .= 'px';
		$this->setStyle('border', $borderSize.' '.$borderStyle.' '.$borderColor);
		return $this; /* jQuery inspired */
	}

	/*
	 * Seria extensions..
	 */
	public function setContextMenu($contextmenu)
	{
		if (strlen($contextmenu)) {
			$t_contextmenu = trim($contextmenu);
			if (substr($t_contextmenu, 0, 5) == 'mnu="') {
				$t_contextmenu = substr($t_contextmenu, 5, -1);
				$this->setAttr('mnu', $t_contextmenu);
			} else
				$this->setAttr('mnu', $contextmenu);
		}
		return $this;
	}

	public function output()
	{
		$content = '';
		if ($this->elTag !== false) {
			$content .= '<'.$this->elTag;
			$params = $this->params;
			if (count($this->style) != 0) {
				$style = array();
				foreach ($this->style as $var => $val)
					$style[] = $var.':'.$val;
				$params[] = 'style="'.implode(';', $style).'"';
			}
			if (count($params) != 0)
				$content .= ' '.implode(' ', $params);
			if ($this->noEndTag && SERIA_XHTML != 0)
				$content .= ' /';
			$content .= '>';
		}
		if ($this->text !== false)
			$content .= htmlspecialchars($this->text);
		foreach ($this->children as $child) {
			if (!is_string($child) && method_exists($child, 'output'))
				$content .= $child->output();
			else
				$content .= $child;
		}
		if ($this->elTag !== false && !$this->noEndTag)
			$content .= $this->endPrefix.$this->elTag.'>';
		return $content;
	}
}

?>

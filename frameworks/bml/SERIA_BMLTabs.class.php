<?php

class SERIA_BMLTabs extends SERIA_BMLElement
{
	private $tabs = array();

	function __construct($tabsin)
	{
		foreach ($tabsin as $tabinfo) {
			if (!isset($tabinfo['id']))
				$tabinfo['id'] = 'tabid'.mt_rand();
			$this->tabs[] = array(
				'name' => $tabinfo['name'],
				'id' => $tabinfo['id'],
				'children' => array()
			);
			if (isset($tabinfo['contents']))
				$this->addChildToId($tabinfo['id'], $tabinfo['contents']);
		}
	}

	function addChildToIndex($tabindex, $child)
	{
		$this->tabs[$tabindex]['children'][] = $child;
	}
	function addChildToId($tabid, $child)
	{
		foreach ($this->tabs as $index => $info) {
			if ($info['id'] == $tabid)
				$this->addChildToIndex($index, $child);
		}
	}

	private function outputChildren($children)
	{
		$contents = '';
		foreach ($children as $child) {
			if (method_exists($child, 'output'))
				$contents .= $child->output();
			else
				$contents .= $child;
		}
		return $contents;
	}
	function output()
	{
		$contents = '<div class=\'tabs\'><ul>';
		foreach ($this->tabs as $info) {
			$contents .= '<li><a href=\'#'.$info['id'].'\'><span>'.$info['name'].'</span></a></li>';
			$foot .= '<div id=\''.$info['id'].'\'>'.$this->outputChildren($info['children']).'</div>';
		}
		$contents .= '</ul></div>';
		return $contents.$foot;
	}
}

?>
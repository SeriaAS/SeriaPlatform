<?php
/**
 *	Autoload classes from bml folder
 */
SERIA_Base::addClassPath(SERIA_ROOT."/seria/frameworks/bml/*.class.php");

/**
 * Fast function to create BML (HTML/XHTML JEP-sys) objects
 *
 * @param string $elementType
 * @param array $params
 * @param boolean $noendtag
 * @return SERIA_BMLElement
 */
function seria_bml($elementType=false, $params=array(), $noendtag=false)
{
	if ($noendtag) {
		$elem = new SERIA_BMLElement($elementType, $params);
		$elem->noEndTag = true;
		return $elem;
	}
	return new SERIA_BMLElement($elementType, $params);
}

function seria_bml_hidden($name, $value)
{
	return new SERIA_BMLElement('input', array(
		'type' => 'hidden',
		'name' => $name,
		'value' => $value
	));
}
function seria_bml_ahref($location)
{
	return new SERIA_BMLElement('a', ($location !== false ? array('href' => $location) : array()));
}

function seria_bml_encap($element, $attrs, $objects)
{
	if ($attrs === false)
		$attrs = array();
	$elem = new SERIA_BMLElement($element, $attrs);
	if ($objects !== false) {
		if (is_array($objects))
			return $elem->addChildren($objects);
		else
			return $elem->addChild($objects);
	} else
		return $elem;
}

/**
 * Enter description here...
 *
 * @param array $colwidth
 * @param array $rows
 */
function seria_divbuilder($colwidth, $rows)
{
	$settings = array(
		'columns' => array(
		)
	);
	foreach ($colwidth as $width)
		$settings['columns'][] = array('width' => $width);
	$divb = new SERIA_DivBuilder($settings);
	$divb->addRows($rows);
	return $divb;
}

function seria_bml_iecond($expr='IE')
{
	$iecond = new SERIA_BMLIECond($expr);
	return $iecond;
}

function seria_bml_displaytable($columns=2)
{
	$dt = new SERIA_BMLCompatDisplayTable($columns);
	return $dt;
}

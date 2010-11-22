<?php

class ArrayXmlParser
{
	public static function push(&$array, &$object)
	{
		$array[] =& $object;
	}
	public static function &pop(&$array)
	{
		end($array);
		$key = key($array);
		$value =& $array[$key];
		unset($array[$key]);
		return $value;
	}
	public static function parseToTree($xmldata)
	{
		$xmlParser = xml_parser_create();
		xml_parse_into_struct($xmlParser, $xmldata, $vals, $index);
		xml_parser_free($xmlParser);
		$tree = array(
			'values' => array(),
			'children' => array()
		);
		$stack = array();
		$current =& $tree;
		$level = 0;
		SERIA_Base::debug('Starting XML tree parser..');
		foreach ($vals as $val) {
			SERIA_Base::debug($val['tag'].' type '.$val['type'].' at level '.$val['level']);
			if ($val['level'] == $level && $val['type'] == 'cdata') {
				SERIA_Base::debug('CDATA at level '.$level);
				if (isset($val['value']) && $val['value']) {
					$current['values'][] = $val['value'];
				}
				continue;
			}
			switch ($val['type']) {
				case 'open':
					$obj = array(
						'name' => strtolower($val['tag']),
						'values' => array(),
						'children' => array()
					);
					$current['children'][] =& $obj;
					self::push($stack, $current);
					unset($current);
					$current =& $obj;
					unset($obj);
					$level++;
					if ($level != $val['level'])
						throw new SERIA_Exception('Invalid level for opening!');
					SERIA_Base::debug('Opened '.$obj['name'].' at level '.$level);
					break;
				case 'close':
					SERIA_Base::debug('Closing '.$current['name'].' at level '.$level);
					unset($current);
					$current =& self::pop($stack);
					if ($level != $val['level'])
						throw new SERIA_Exception('Invalid level for closing!');
					$level--;
					SERIA_Base::debug('Tag closed.');
					break;
				case 'complete':
					$level++;
					if ($level != $val['level'])
						throw new SERIA_Exception('Invalid level for complete!');
					SERIA_Base::debug('Adding '.$val['tag'].' with value '.$val['value'].' at level '.$level);
					$current['children'][] = array(
						'name' => strtolower($val['tag']),
						'values' => array($val['value']),
						'children' => array()
					);
					$level--;
					break;
				case 'cdata':
					if ($val['level'] < $level)
						SERIA_Base::debug('Ignored downlevel ('.$val['level'].'<'.$level.') CDATA: '.$val['value']);
					else
						throw new SERIA_Exception('Unexpected CDATA at level '.$val['level'].' when expecting level '.$level);
					break;
				default:
					throw new SERIA_Exception('Unexpected '.$val['type'].' element (level='.$val['level'].',current='.$level.')!');
			}
		}
		return $tree;
	}
}
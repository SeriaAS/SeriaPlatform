<?php
class SERIA_FluentCompiler
{
	function loadClass($filename)
	{
		$contents = file_get_contents($filename);
		$code = SERIA_FluentCompiler::fluentRewrite($contents);
		if(eval('?'.'>'.$code)===false) 
			throw new SERIA_Exception('SERIA_FluentCompiler: An error was encountered parsing '.$filename.'. Did you implement the "isDeletable"-function?');;
	}

	/**
	*	Takes source code for a php class file where the class extends SERIA_FluentObject
	*	and 
	*/
	function fluentRewrite($source)
	{
		// PARSING OF THE CLASS
		$start = strpos($source, '/*FLUENT*/');
		$end = strpos($source, '/*FLUENT*/', $start+10);
		$before = substr($source, 0, $start);
		$code = substr($source, $start+10, $end-$start-10);
		$after = substr($source, $end+10);

		$preg = preg_match_all('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/i', $before, $matches);
		$found = 0;
		foreach($matches[0] as $match)
		{
			if(!empty($match))
			{
				$lc = strtolower($match);
				if($found === 1)
				{
					$class = $match;
					$found++;
				}
				else if($found === 3)
				{
					$extendedClass = $match;
					break;
				}
				else if($lc==='class' || $lc==='extends')
				{
					$found++;
				}
			}
		}

		if($extendedClass != 'SERIA_FluentObject')
			throw new Exception('We can\'t parse classes that does not extend SERIA_FluentObject');

		// need to eval this code. Alternative would be to require the class file, but that would declare the class before we
		// rewrite it.
		$struct = eval($code);

		$inject = "
	private static \$_Fluent = false;
	public static function _Fluent() {
		if(self::\$_Fluent !== false) return self::\$_Fluent;
		return self::\$_Fluent = self::Fluent();
	}
	public static function fluentSpec() { 
		\$data = self::_Fluent();
		if(!isset(\$data['selectWhere'])) \$data['selectWhere'] = NULL;
		if(!isset(\$data['deleteWhere'])) \$data['deleteWhere'] = NULL;
		if(!isset(\$data['updateWhere'])) \$data['updateWhere'] = NULL;
		return array(
			'table' => \$data['table'], 	
			'primaryKey' => 'id', 
			'selectWhere' => \$data['selectWhere'], 
			'deleteWhere' => \$data['deleteWhere'],
			'updateWhere' => \$data['updateWhere'],
		);
	}
	public static function createObject(\$p) { return SERIA_Fluent::createObject('".$class."', \$p); }
	public static function fromDB(\$row) { return SERIA_Fluent::createObject('".$class."', \$row); }
	public static function getFieldSpec() { 
		\$data = self::_Fluent();
		return \$data['fields'];
	}";

		$injectPos = strpos($source, "{", strpos($source, 'SERIA_FluentObject')+18)+1;
		$classCode = substr($source, 0, $injectPos).'/*START INJECTED BY SERIA_FluentCompiler*/'.$inject.'/*END INJECTED BY SERIA_FluentCompiler*/'.substr($source, $injectPos);
		return $classCode;
	}
}

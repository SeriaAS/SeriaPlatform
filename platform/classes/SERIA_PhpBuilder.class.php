<?php
/**
*	This class is made for building and modifying a file of PHP-code. The initial purpose
*	is to help in maintaining the SERIA_PRIV_ROOT/SERIA_PLATFORM.php file.
*/

class SERIA_PhpBuilder
{
	private $filename;
	private $lines;
	private static $instances = array();

	/**
	*	Create or retrieve an instance of SERIA_PhpBuilder to modify the selected file
	*
	*	@param string $filename 	Path to PHP-file you wish to modify
	*/
	public static function createObject($filename)
	{
		if(!isset(self::$instances[$filename]))
			self::$instances[$filename] = new SERIA_PhpBuilder($filename, false);

		return self::$instances[$filename];
	}

	/**
	*	Never create instances of this object yourself, as it means there may be multiple instances of this object in one process,
	*	which will give us trouble with file locking.
	*	@param string $filename 	Path to PHP-file you wish to modify
	*/
	public function __construct($filename, $direct=true)
	{
		if($direct) throw new SERIA_Exception('Only use SERIA_PhpBuilder trough SERIA_PhpBuilder::createObject($filename);');
		$this->filename = $filename;
		if(file_exists($this->filename))
		{
			$contents = trim(file_get_contents($this->filename));
			if(strpos($contents, "<"."?php")===false)
				$contents = "<"."?php\n".$contents;
			$this->lines = explode("\n", $contents);
		}
		else
			$this->lines = array('<'.'?php');
	}

	public function save()
	{
		return file_put_contents($this->filename, implode("\n", $this->lines));
	}

	/**
	*	The parameter expects a quoted string. If the path you wish to add is /some/path,
	*	insert the value "'/some/path'". This allows you to insert paths that use constants
	*	such as SERIA_ROOT.'/some/path'.
	*
	*	@param string $path	The path to the file you wish to include
	*/
	public function addInclude($path)
	{
		$hash = md5($path);
		$this->_removeHash($hash);
		$this->lines[] = 'include('.$path.');';
		return $this;
	}

	/**
	*	It is very important that the path specified here is identical to the way you wrote the path when
	*	calling addInclude($path)
	*
	*	@param string $path	The path to the file you no longer wish to include
	*/
	public function removeInclude($path)
	{
		$hash = md5($path);
		$this->_removeHash($hash);
		return $this;
	}

	/**
	*	Remove an include by its path. Remember that the way you type it decides if there will
	*	be duplicate lines in the file.
	*
	*	@param array $path	The path to the variable. Example: $instance->addVariable(array('GLOBALS','seria','platform'),NULL) will insert $GLOBALS['seria']['platform']=NULL;
	*	@param mixed $value	The value to assign to the variable.
	*	@return SERIA_PhpBuilder
	*/
	public function addVariable(array $path, $value)
	{
		$string = self::_buildVariableName($path);
		$hash = md5($string);
		$this->_removeHash($hash);
		$string .= '='.var_export($value,true).';//SERIA_PhpBuilder:hash:'.$hash;
		$this->lines[] = $string;
		return $this;
	}

	/**
	*	Remove a variable by its variable name.
	*
	*	@param array $path	The path to the variable. Example: $instance->addVariable(array('GLOBALS','seria','platform'),NULL) will insert $GLOBALS['seria']['platform']=NULL;
	*	@param mixed $value	The value to assign to the variable.
	*	@return SERIA_Perservere
	*/
	public function removeVariable(array $path)
	{
		$string = self::_buildVariableString($path);
		$hash = md5($string);
		$this->_removeHash($hash);
		return $this;
	}

	private function _removeHash($hash)
	{
		foreach($this->lines as $key => $line)
		{
			if(strpos($line, '//SERIA_PhpBuilder:hash:'.$hash)!==false)
				unset($this->lines[$key]);
		}
	}

	private static function _buildVariableName(array $path)
	{
		$string = '$'.array_shift($path);
		while(sizeof($path)>0)
			$string .= '[\''.array_shift($path).'\']';
		return $string;
	}
}

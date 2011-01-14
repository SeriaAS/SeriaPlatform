<?php
	/**
	*	Provides Dictionary support for various sources of data to Seria Platform and components.
	*
	*	To add a dictionary, listen to the SERIA_DICTIONARY_GET_HOOK($dictionaryName), and return an object instance of SERIA_Dictionary
	*	@author Frode Børli
	*	@package platform
	*/
	class SERIA_SystemDictionariesManifest {
		const SERIAL = 1;
		const NAME = 'dictionaries';
		public static $classPaths = array(
			'classes/*.class.php',
		);
	}

        /**
        *       Return an instance of SERIA_Dictionary for the required name.
        *       @param string $dictionaryName
        */
        define('SERIA_DICTIONARY_GET_HOOK', 'SERIA_DICTIONARY_GET_HOOK');
	SERIA_Hooks::listen('SERIA_DICTIONARY_GET_HOOK', 'seria_systemdictionary_load', 1000000);
	/**
	*	Loads dictionaries that are defined in the dictionaries/ folder.
	*/
	function seria_systemdictionary_load($dictionaryName)
	{
		if(file_exists(SERIA_ROOT.'/seria/components/SERIA_SystemDictionaries/dictionaries/'.$dictionaryName.'.ini'))
		{
			$config = parse_ini_file(SERIA_ROOT.'/seria/components/SERIA_SystemDictionaries/dictionaries/'.$dictionaryName.'.ini');
			$config['file'] = SERIA_ROOT.'/seria/components/SERIA_SystemDictionaries/dictionaries/'.$config['file'];
			$className = $config['class'];
			return self::$dictionaries[$dictionaryName] = new $className($config);
		}
	}


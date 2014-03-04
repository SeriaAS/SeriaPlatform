<?php
	/**
	 *
	 * Modern GUI component.
	 *
	 * @author Frode Borli, Jon-Eirik Pettersen
	 * @package SERIA_ModernGui
	 *
	 */
	class SERIA_ModernGUIManifest
	{
		const SERIAL = 1;
		const NAME = 'seriamoderngui';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
			)
		);
	}

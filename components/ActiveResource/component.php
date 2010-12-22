<?php
	/**
	 * 
	 * Component for querying and manipulation of Ruby On Rails ActiveResources.
	 * @author Jon-Eirik Pettersen
	 * @package activeresource
	 *
	 */
	class ActiveResourceManifest
	{
		const SERIAL = 1;
		const NAME = 'activeresource';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
			)
		);
	}

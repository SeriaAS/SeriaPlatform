<?php
	/**
	 *
	 * Component for a log parser, will read logs from SERIA_PRIV_ROOT/SERIA_Logger/incoming/
	 * and aggregate statistical data from it and make this data available to other components and
	 * applications trough SERIA_Counter.
	 *
	 * @author Joakim Eide, Frode Borli
	 * @package SERIA_Logging
	 *
	 */
	class SERIA_LoggingManifest
	{
		const SERIAL = 1;
		const NAME = 'serialogging';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
			)
		);

		 public static $menu = array(
			'controlpanel/other/statistics' => array(
				'title' => 'Bandwidth usage',
				'description' => 'View bandwidth usage',
				'page' => 'serialogging/bandwidthUsage',
			),
		);
	}

	SERIA_Hooks::listen(SERIA_MAINTAIN_30_MINUTES_HOOK, array('SERIA_Logging', 'processLogFiles'));

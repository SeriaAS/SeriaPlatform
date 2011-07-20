<?php

/**
 *
 * Legacy sitemenu code.
 *
 * @author Various Authors
 * @package SERIA_SiteMenu
 *
 */
class SERIA_SiteMenuManifest
{
	const SERIAL = 1;
	const NAME = 'seriasitemenu';

	public static $classPaths = array(
		'classes/*.class.php',
	);
	public static $database = array(
		'creates' => array(
		)
	);
	public static $menu = array(
	);
}

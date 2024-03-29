<?php

class SERIA_ScriptLoader
{
	private static $scripts = array(
		"jQuery-tablesorter" => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => "/seria/libs/jquery/jquery-tablesorter/jquery.tablesorter.min.js",
					'css' => array(
						'/seria/libs/jquery/jquery-tablesorter/themes/blue/style.css'
					)
				)
			)
		),
		"jQuery-thickbox" => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => "/seria/libs/jquery/thickbox/thickbox.js",
					'css' => array(
						"/seria/libs/jquery/thickbox/thickbox.css"
					)
				)
			)
		),
		'jQuery-flashembed' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => '/seria/libs/jquery/jquery-flashembed/tools.flashembed-1.0.3.min.js'
				)
			)
		),
		'jQuery-treeview' => array(
			'versions' => array(
				
				'1.4' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3',
							'preferred' => '1.2.3'
						)
					),
					'filename' => "/seria/platform/js/jquery/jquery-treeview/jquery.treeview.min.js",
					'css' => array(
						"/seria/platform/js/jquery/jquery-treeview/jquery.treeview.css"
					)
				)
			)
		),
		'jQuery-ui-core' => array(
			'versions' => array(
				'0' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => "/seria/libs/jquery/ui/ui.base.js"
				),
				'1.5.3' => array(
					'depends' => array(),
				),
				'1.6rc???' => array(
					'depends' => array(),
				),
				'1.6rc5' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.3'
						)
					),
					'filename' => "/seria/libs/jQuery-1.3/ui/ui.core.js",
					'css' => array(
						"/seria/libs/jQuery-1.3/themes/default/ui.core.css",
						"/seria/libs/jQuery-1.3/themes/default/ui.theme.css",
					)
				),
				'1.8.2' => array('depends' => array()),
			),
			'default' => array(
				'preferred' => '1.5.3'
			)
		),
		'jQuery-ui-draggable' => array(
			'versions' => array(
				'1.5.3' => array(
					'depends' => array(),
				),
				'1.6rc???' => array(
					'depends' => array(),
				),
				'1.6rc5' => array(
					'filename' => '/seria/libs/jQuery-1.3/ui/ui.draggable.js',
					'depends' => array(
						array(
							'name' => 'jQuery-ui-core',
							'minimum' => '1.6rc5',
						),
					),
				),
				'1.8.2' => array('depends' => array()),
			),
		),
		'jQuery-ui-droppable' => array(
			'versions' => array(
				'1.5.3' => array(
					'depends' => array(),
				),
				'1.6rc???' => array(
					'depends' => array(),
				),
				'1.6rc5' => array(
					'filename' => '/seria/libs/jQuery-1.3/ui/ui.droppable.js',
					'depends' => array(
						array(
							'name' => 'jQuery-ui-core',
							'minimum' => '1.6rc5',
						),
					)
				),
				'1.8.2' => array('depends' => array()),
			),
		),
		'jQuery-ui-sortable' => array(
			'versions' => array(
				'1.5.3' => array(
					'depends' => array(),
				),
				'1.6rc???' => array(
					'depends' => array(),
				),
				'1.6rc5' => array(
					'filename' => '/seria/libs/jQuery-1.3/ui/ui.sortable.js',
					'depends' => array(
					),
				),
				'1.8.2' => array(
					'depends' => array(),
				),
			),
		),
		'jQuery-ui-tabs' => array(
			'versions' => array(
				'0' => array(
					'depends' => array(
						array(
							'name' => 'jQuery-ui-core',
							'maximum' => '0'
						)
					),
					'filename' => "/seria/libs/jquery/ui/tabs/ui.tabs.min.js",
					'css' => array(
						"/seria/libs/jquery/ui/tabs/ui.tabs.css"
					)
				),
				'1.5.3' => array(
					'depends' => array()
				),
				'1.6rc???' => array(
					'depends' => array(),
				),
				'1.6rc5' => array(
					'depends' => array(
						array(
							'name' => 'jQuery-ui-core',
							'minimum' => '1.6rc5'
						)
					),
					'filename' => '/seria/libs/jQuery-1.3/ui/ui.tabs.js',
					'css' => array(
						"/seria/libs/jQuery-1.3/themes/default/ui.tabs.css"
					)
				),
				'1.8.2' => array('depends' => array()),
			),
		),
		'jQuery-ui' => array(
			'versions' => array(
				'1.5.3' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.6',
							'maximum' => '1.2.6'
						),
					),
					'provides' => array(
						array(
							'name' => 'jQuery-ui-core',
						),
						array(
							'name' => 'jQuery-ui-tabs'
						),
						array(
							'name' => 'jQuery-ui-draggable',
						),
						array(
							'name' => 'jQuery-ui-droppable',
						),
					),
					'filename' => '/seria/libs/jquery/ui-all/jquery-ui-personalized-1.5.3.min.js',
					'css' => array(
						'/seria/libs/jquery/ui-all/theme/ui.all.css'
					)
				),
				'1.6rc???' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.3.1',
							'maximum' => '1.3.1'
						),
					),
					'provides' => array(
						array(
							'name' => 'jQuery-ui-core',
						),
						array(
							'name' => 'jQuery-ui-tabs'
						),
						array(
							'name' => 'jQuery-ui-draggable',
						),
						array(
							'name' => 'jQuery-ui-droppable',
						),
					),
					'filename' => '/seria/libs/jQuery-1.3/ui/jquery.ui.all.js',
					'css' => array(
						'/seria/libs/jQuery-1.3/themes/default/ui.all.css'
					)
				),
				'1.8.2' => array(
					'depends' => array(array('name' => 'jQuery', 'minimum' => '1.4.2')),
					'provides' => array(
						array(
							'name' => 'jQuery-ui-core',
						),
						array(
							'name' => 'jQuery-ui-tabs'
						),
						array(
							'name' => 'jQuery-ui-draggable',
						),
						array(
							'name' => 'jQuery-ui-droppable',
						),
						array(
							'name' => 'jQuery-ui-sortable',
						),
					),
					'filename' => '/seria/platform/js/jquery/js/jquery-ui-1.8.2.custom.min.js',
					'css' => array('/seria/platform/js/jquery/css/custom-theme/jquery-ui-1.8.2.custom.css'),
				),
			),
			'default' => array(
				'preferred' => '1.8.2'
			)
		),
		'platform-widgets' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						),
						array(
							'name' => 'SERIA-Platform-Common'
						),
						array(
							'name' => 'SERIA-Filearchive-2'
						)
					),
					'filename' => "/seria/platform/js/widgets.js"
				)
			)
		),
		'Timer' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'SERIA-Platform-Common'
						)
					),
					'filename' => '/seria/platform/js/ietimer.js'
				)
			)
		),
		'Autosize' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => '/seria/platform/js/autosize.js'
				)
			)
		),
		'date_browser' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => '/seria/widgets/datepicker/datepicker.js'
				)
			)
		),
		'file_browser' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => '/seria/widgets/fileselect/fileselect.js'
				)
			)
		),
		'tinymce_editor' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => '/seria/widgets/tinymce/tinymce.js'
				)
			)
		),
		
		'jQuery-include' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => '/seria/libs/jquery-include.js'
				)
			)
		),

		'Date-Extensions' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => '/seria/libs/Date-Extensions/date.js'
				)
			)
		),
		'jQuery-datepicker' => array(
			'versions' => array(
				'20090405' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3' /* According to docs actually 1.2.1, but we don't have it */
						),
						array(
							'name' => 'Date-Extensions'
						)
					),
					'filename' => '/seria/libs/jQuery-datepicker-20090405/jquery.datePicker.js',
					'css' => array(
						'/seria/libs/jQuery-datepicker-20090405/datePicker.css'
					)
				)
			)
		),
		'Facebook-Share' => array(
			'versions' => array(
				array(
					'depends' => array(),
					'filename' => 'http://static.ak.fbcdn.net/connect.php/js/FB.Share'
				)
			)
		),
		'SERIA-Platform-Public' => array(
			'versions' => array(
				'0-private' => array(
					/* Provided by SERIA-Platform-Private */
					'depends' => array()
				),
				'0-public' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'filename' => '/seria/platform/js/public.js'
				)
			)
		),
		'SERIA-Platform-Private' => array(
			'versions' => array(
				'0-private' => array(
					'depends' => array(
						array(
							'name' => 'jQuery',
							'minimum' => '1.2.3'
						)
					),
					'provides' => array(
						array(
							'name' => 'SERIA-Platform-Public'
						)
					),
					'filename' => array(
						'/seria/platform/js/private.js',
						'/seria/platform/js/menu.js',
						'/seria/platform/js/mnuX.js',
					),
					'css' => array(
						'/seria/platform/js/mnuX.css'
					)
				)
			)
		),
		'SERIA-Platform-Common' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'SERIA-Platform-Public'
						)
					),
					'filename' => '/seria/platform/js/common.js'
				)
			)
		),
		'SERIA-Filearchive-2' => array(
			'versions' => array(
				array(
					'depends' => array(
						array(
							'name' => 'SERIA-Platform-Common'
						)
					),
					'filename' => '/seria/filearchive2/js/filearchive2.js.php'
				)
			)
		)
	);

	protected static $ident; /* The identifier of the current script table (caching) */
	protected static $loadOrder = NULL;
	protected static $loadScript = array();
	protected static $doneInit = false;

	protected static $loadLog = array(); /* SERIA_DEBUG */

	protected static $cache;

	protected static $safeMode = 0;

	public static function provideScript($name, $version, $info)
	{
		if (!isset(self::$scripts[$name]))
			self::$scripts[$name] = array();
		if (!isset(self::$scripts[$name]['versions']))
			self::$scripts[$name]['versions'] = array();
		if (!isset(self::$scripts[$name]['versions'][$version]))
			self::$scripts[$name]['versions'][$version] = $info;
		else
			throw new Exception('Duplicate provider for '.$name.'-'.$version);
		SERIA_Base::debug('SERIA_ScriptLoader: Adding '.$name.'-'.$version);
		self::$doneInit = false; /* reinit load-order */
	}
	public static function enterSafeMode()
	{
		self::$safeMode++;
	}
	public static function exitSafeMode()
	{
		self::$safeMode--;
	}

	protected static function initSolver()
	{
		SERIA_ScriptLoader_Solver::setScripts(self::$scripts);
	}
	public static function init()
	{
		if (self::$doneInit)
			return;
		SERIA_Base::debug('Starting script loader..');
		self::initSolver();
		self::$doneInit = true;
		self::$cache = new SERIA_Cache('SERIA_ScriptLoader');
		self::$ident = sha1(serialize(self::$scripts)).'_ordercache';
		try {
			self::$loadOrder = unserialize(self::$cache->get(self::$ident));
		} catch (Exception $e) {
			SERIA_Base::debug('OOOPS: FAILED TO LOAD SCRIPT-LOADER CACHE: '.$e->getMessage());
			self::$loadOrder = null;
		}
		if (!self::$loadOrder) {
			SERIA_Base::debug('Need to calculate safe load order (caching)');
			try {
				$loadOrder = SERIA_ScriptLoader_Solver::getSafeLoadOrder();
				try {
					self::$cache->set(self::$ident, serialize($loadOrder), 30*24*3600);
				} catch (Exception $e) {
					SERIA_Base::debug('OOOPS: FAILED TO SAVE SCRIPT-LOADER CACHE: '.$e->getMessage());
				}
			} catch (Exception $e) {
				SERIA_Base::debug('SERIA_ScriptLoader: Failed to generate '.self::$ident.' load order, waiting for valid dependency tree...');
				$loadOrder = null;
			}
			if ($loadOrder !== null)
				self::$loadOrder = $loadOrder;
		}
		if (SERIA_COMPATIBILITY < 3) {
			SERIA_Hooks::listen('SERIA_Template::outputHandler', array('SERIA_ScriptLoader', 'doLoads'));

			/*
			 * Reserve space in the template for script includes: should be included before eventually other scripts in the head.
			 */
			SERIA_Template::head('SERIA_ScriptLoader', '');
			SERIA_Base::debug('Script loader is ready..');
		}
	}

	/**
	 * SERIA_COMPATIBILITY>=3 has to request and include the head content in a main-template-file. No auto..
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getHeadContent()
	{
		//print_r(self::$scripts); die();
		self::init();
		if (!self::$loadScript)
			return '';
		if (self::$loadOrder == NULL)
			return '';
		try {
			$loadHash = sha1(SERIA_HTTP_ROOT.serialize(self::$loadScript).self::$ident);
			try {
				$loadHtml = self::$cache->get($loadHash);
			} catch (Exception $e) {
				SERIA_Base::debug('OOOPS: FAILED TO LOAD SCRIPT-LOADER CACHE: '.$e->getMessage());
				$loadHtml = null;
			}
			if (!$loadHtml) {
				SERIA_Base::debug('SERIA_ScriptLoader has not cached this situation. Need to calculate dependencies..');
				foreach (self::$loadOrder as $load) {
					if (isset(self::$loadScript[$load])) {
						foreach (self::$loadScript[$load] as $callLoad) {
							SERIA_Template::debugMessage('SERIA_ScriptLoader loading script: '.$load);
							SERIA_ScriptLoader_Solver::userLoadScript($load, $callLoad['preferred'], $callLoad['minimum'], $callLoad['maximum']);
						}
					}
				}
				$loadHtml = SERIA_ScriptLoader_Solver::getJS().SERIA_ScriptLoader_Solver::getCSS();
				try {
					self::$cache->set($loadHash, $loadHtml, 30*24*3600);
				} catch (Exception $e) {
					SERIA_Base::debug('OOOPS: FAILED TO SAVE SCRIPT-LOADER CACHE: '.$e->getMessage());
				}
			} else
				SERIA_Base::debug('SERIA_ScriptLoader has cached this situation. Can load directly.');
			if ($loadHtml)
				return $loadHtml;
		} catch (Exception $e) {
			if (SERIA_DEBUG) {
				SERIA_Base::debug("SERIA_ScriptLoader has failed. This is the load log:");
				foreach (self::$loadLog as $load) {
					SERIA_Base::debug("***************************************<br/>\n");
					SERIA_Base::debug('Script: '.$load['name']."<br/>\n");
					SERIA_Base::debug('Preferred version: '.($load['preferred'] !== false ? $load['preferred'] : 'NULL')."<br/>\n");
					SERIA_Base::debug('Minimum version: '.($load['minimum'] !== false ? $load['minimum'] : 'NULL')."<br/>\n");
					SERIA_Base::debug('Maximum version: '.($load['maximum'] !== false ? $load['maximum'] : 'NULL')."<br/>\n");
					SERIA_Base::debug("#######################################<br/>\n");
					SERIA_Base::debug($load['backtrace']);
					SERIA_Base::debug("***************************************<br/>\n");
				}
			}
			throw $e;
		}
		return '';
	}

	/**
	 * SERIA_COMPATIBILITY < 3: Automatic load will take place through the SERIA_Template.
	 */
	public static function doLoads()
	{
		static $loaded = false;

		if ($loaded)
			return;
		$loaded = true;
		$loadHtml = self::getHeadContent();
		if ($loadHtml)
			SERIA_Template::head('SERIA_ScriptLoader', $loadHtml);
		SERIA_Base::debug('SERIA_ScriptLoader is done.');
	}
	public static function reset()
	{
		self::$loadScript = array();
	}
	public static function loadScript($name, $preferred=false, $minimum=false, $maximum=false)
	{
/*
ob_start destroys SERIA_GUI::start() because of output buffering inside output buffer handler
		if (SERIA_DEBUG) {
			ob_start();
			debug_print_backtrace();
			$backtrace = ob_get_clean();
			self::$loadLog[] = array(
				'name' => $name,
				'preferred' => $preferred,
				'minimum' => $minimum,
				'maximum' => $maximum,
				'backtrace' => $backtrace
			);
		}
*/
		try {
			self::init();
			if (!isset(self::$loadScript[$name]))
				self::$loadScript[$name] = array();
			self::$loadScript[$name][] = array(
				'preferred' => $preferred,
				'minimum' => $minimum,
				'maximum' => $maximum
			);
		} catch (Exception $e) {
			if (self::$safeMode)
				return;
			throw $e;
		}
	}

	public static function sScriptTag($tag)
	{
		$name = $tag->get('name');
		if (!$name)
			throw new SERIA_Exception('s:script must have the name attribute');
		$version = $tag->get('version');
		if (!$version) {
			$minimum = $tag->get('minimum');
			$preferred = $tag->get('preferred');
			$maximum = $tag->get('maximum');
			if (!$minimum)
				$minimum = false;
			if (!$preferred)
				$preferred = false;
			if (!$maximum)
				$maximum = false;
		} else {
			$minimum = $version;
			$preferred = false;
			$maximum = $version;
		}
		self::loadScript($name, $preferred, $minimum, $maximum);
		return '';
	}
}

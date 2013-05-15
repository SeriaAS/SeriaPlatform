<?php
	/**
	*	INSTALLATION SCRIPT FOR SERIA PLATFORM. IS EXECUTED WHEN THE FILE SERIA_PRIV_ROOT.'/SERIA_PLATFORM.php' DOES NOT EXIST.
	*/
	$gui = new SERIA_Gui('Installation', true); // true means do not allow other apps and components to embed.

	if(isset($_GET['prevent_loop']))
	{ // a loop might occur if the install script fails when trying to access delete_me.php-files, and they do not exist
		die("0");
	}

	$errors = array();

	/**
	*	CREATE REQUIRED FOLDERS AND CHECK IF THEY ARE WRITABLE
	*
	*	/files		Publicly accessible files, not executable trough browser but readable.
	*	/files/dyn	Publicly accessible files, executable trough browser.
	*	/files/priv	Privately accessible files, not available trough browser.
	*/

	if(!SERIA_READ_ONLY) {
		$paths = array(
			SERIA_FILES_ROOT => array('The root folder for most files written by Seria Platform. If you wish to relocate files you can use symlinks from here.'),
			SERIA_DYN_ROOT => array('Files here can be accessed by the browser, and can be executable code - for example a PHP script.'),
			SERIA_PRIV_ROOT => array('The location of files that should not be readable for the world. Take measures to prevent users from reading this folder.'),
			SERIA_TMP_ROOT => array('Location of temporary files. Will automatically be garbage collected.'),
			SERIA_CACHE_ROOT => array('Location of cached files. Will automatically be garbage collected.'),
			SERIA_LOG_ROOT => array('Location of log files. Files in this folder will be zipped and archived automatically.'),
			SERIA_DYNAMICCLASS_ROOT => array('Dynamic classes for activerecord'),
		);
		$mask = umask(0);
		try {
			foreach($paths as $path => $spec)
			{
				$result = array(
					'action' => 'Create folder "'.$path.'"',
					'description' => $spec[0],
				);
				clearstatcache();
				if(!file_exists($path))
				{
					if(!mkdir($path, 0777, true))
						$result['error'] = 'Unable to create folder "'.$path.' Permission denied.';
				}
				else if(!is_dir($path))
				{
					$result['error'] = 'The path "'.$path.'" is not a directory. Check your configuration.';
				}
				else if(!seria_is_writable($path))
				{
					$result['error'] = 'The path "'.$path.'" is not writable by me. My user id is "'.seria_getUser().'". Try "<em>chown -R '.seria_getUser().' '.$path.'</em>\".';
				}
				$log[] = $result;
			}
		} catch (Exception $e) {
			umask($mask);
			SERIA_Base::displayErrorPage('500', 'Installation script error', $e->getMessage());			
		}
		umask($mask);

		// Check that files in /files are not executable
		$result = array(
			'action' => 'HTTP fetch on "'.SERIA_FILES_HTTP_ROOT.'"',
			'description' => 'Is "'.SERIA_FILES_HTTP_ROOT.'" accessible?',
		);
		clearstatcache();
		file_put_contents(SERIA_FILES_ROOT.'/delete_me.php', '<'.'?php echo "1";');

		if(SERIA_FILES_DELAY) sleep(SERIA_FILES_DELAY); // there might be some propagation delay


		if(($response = SERIA_WebBrowser::fetchUrlContents(SERIA_FILES_HTTP_ROOT.'/delete_me.php?prevent_loop='.rawurlencode($_SERVER['REQUEST_URI']), 5))!==false)
		{
			// try to resolve this issue
			clearstatcache();
			file_put_contents(SERIA_FILES_ROOT.'/.htaccess', 'RemoveHandler .php .phtml .php3
RemoveType .php .phtml .php3
php_flag engine off');
			if(SERIA_FILES_DELAY) sleep(SERIA_FILES_DELAY); // there might be some propagation delay

			if(($response = SERIA_WebBrowser::fetchUrlContents(SERIA_FILES_HTTP_ROOT.'/delete_me.php?prevent_loop=1', 5))!==false)
			{ // unable to resolve with .htaccess
				if(trim($response)=='1') 
				{
					unlink(SERIA_FILES_ROOT.'/.htaccess');
					if(!isset($_GET['hack'])) $result['error'] = 'PHP files in '.SERIA_FILES_HTTP_ROOT.' are executable. I tried creating a .htaccess file, but it did not work.';
				}
				else
				{
					$result['notice'] = 'I created the file '.SERIA_FILES_ROOT.'/.htaccess and disabled php.';
				}
			}
			else
			{ // unable to access file
				if(!isset($_GET['hack'])) $result['error'] = 'PHP files in '.SERIA_FILES_HTTP_ROOT.' are executable. I tried to resolve it using a .htaccess file, but it did not work to insert <em>php_flag engine off</em> there, but it seems your host supports .htaccess.';
			}
		}
		else
		{
			//@todo Check if other files are accessible.
			$result['notice'] = 'I could not access the file "'.SERIA_FILES_HTTP_ROOT.'/delete_me.php" trough the web. <br>Please consult your configuration if you think this will be a problem. PHP files must NOT be executable in this folder, but making PHP-files unaccessible is equally secure.';
		}
		@unlink(SERIA_FILES_ROOT.'/delete_me.php');
		$log[] = $result;

		$result = array(
			'action' => 'HTTP fetch on "'.SERIA_DYN_HTTP_ROOT.'"',
			'description' => 'Is dynamically created PHP files accessible?',
		);

		clearstatcache();
		file_put_contents(SERIA_DYN_ROOT.'/delete_me.php', '<'.'?php echo "1";');
		if(SERIA_FILES_DELAY) sleep(SERIA_FILES_DELAY); // there might be some propagation delay

		if(($response = SERIA_WebBrowser::fetchUrlContents(SERIA_DYN_HTTP_ROOT.'/delete_me.php?prevent_loop=1', 5))!==false)
		{
			if(trim($response)!='1')
			{ // try to enable php via .htaccess
				clearstatcache();
				file_put_contents(SERIA_DYN_ROOT.'/.htaccess', 'AddHandler application/x-httpd-php .php
AddType application/x-httpd-php .php
php_flag engine on');
				if(SERIA_FILES_DELAY) sleep(SERIA_FILES_DELAY); // there might be some propagation delay
				if(($response = SERIA_WebBrowser::fetchUrlContents(SERIA_DYN_HTTP_ROOT.'/delete_me.php?prevent_loop', 5))!==false)
				{ // fetched it
					if(trim($response)!='1')
					{ // not PHP
						$result['error'] = 'PHP files in "<em>'.SERIA_DYN_HTTP_ROOT.'</em>" is not executable. Please resolve this.';
						unlink(SERIA_DYN_ROOT.'/.htaccess');
					}
					else
					{
						$result['notice'] = 'I created the file "<em>'.SERIA_DYN_ROOT.'/.htaccess</em>" to enable php-files in "<em>'.SERIA_DYN_HTTP_ROOT.'</em>".';
					}
				}
				else
				{ // file not accessible
					$result['error'] = 'I created the file "<em>'.SERIA_DYN_ROOT.'/.htaccess</em>" but php-files still are not working in that directory. Please make sure .php-files are executable by the webserver from "<em>'.SERIA_DYN_HTTP_ROOT.'</em>".';
					unlink(SERIA_DYN_ROOT.'/.htaccess');
				}
			}
		}
		else
		{
			$result['error'] = 'I could not access the file "'.SERIA_DYN_HTTP_ROOT.'/delete_me.php" trough the web. Please consult your configuration.';
		}
		@unlink(SERIA_DYN_ROOT.'/delete_me.php');
		$log[] = $result;
	}


	/**
	*	Called when Seria Platform is installed. Allows you to create folders and other stuff that you need.
	*/
	clearstatcache();
/*
	@TODO: Cannot be done this way because the dev might disable and enable components freely. -frode

	$components = glob(SERIA_ROOT."/seria/components/*", GLOB_ONLYDIR);
	foreach($components as $component) if(file_exists($component."/install.php"))
		require($component."/install.php");
*/
	$contents = "<table class='grid'><thead><tr><th>Action</th><th>Result</th></tr></thead><tbody>";
	$errors = false;
	foreach($log as $l)
	{
		$contents .= "<tr ".(!empty($l['error']) ? 'class="error"':'')."><td>".$l['action']."</td><td>".(empty($l['error']) ? 'OK'.(empty($l['notice'])?"":"<br><em>".$l['notice']."</em>") : $l['error'])."</td></tr>";
		if(!empty($l['error']))
			$errors = true;
	}
	$contents .= "</tbody></table>";

	if(!$errors)
	{
		$contents = "<h1 class='legend'>Seria Platform installed successfully</h1><p>Please <a href='".SERIA_HTTP_ROOT."/seria/platform/maintain.php'>run the maintain script</a> to continue. The installation has finished.</p>".$contents;		
		require(SERIA_ROOT.'/seria/platform/install/base.php');
		$version = 1;
		SERIA_Base::debug('Seria Install version '.(isset($GLOBALS["seria_install"]) ? ($GLOBALS["seria_install"]['version']) : '-'));
		if (!isset($GLOBALS["seria_install"]) || $GLOBALS["seria_install"]['version'] < $version) {

			$phpBuilder = SERIA_PhpBuilder::createObject(SERIA_PRIV_ROOT.'/SERIA_PLATFORM.php');
			$phpBuilder->addVariable(array('GLOBALS','seria_install','version'), 1);
			$phpBuilder->addVariable(array('GLOBALS','seria_install','timestamp'), time());
			$phpBuilder->save();

		}
		SERIA_WebBrowser::fetchUrlContents(SERIA_HTTP_ROOT.'/seria/platform/maintain.php', 1);
	}
	else
	{
		$contents = "<h1 class='legend'>Seria Platform not installed on ".SERIA_HTTP_ROOT."</h1><p>Please resolve the issues below and <a href='javascript:location.href=location.href'>reload</a> this page to check that everything is OK.</p>".$contents;
	}

	echo $gui->contents($contents)->output();
	die();

	function seria_is_writable($path)
	{
		/**
		*	Note that PHPs documentation states that is_writable can be used with both files and folders. This is implemented because of comments
		*	implying that it is not true.
		*/
		if(!file_exists($path))
			return false;								// file to check must exist
		if(is_dir($path))
		{
			while(file_exists($filename = $path.'/tmp_'.(mt_rand(0,999999999))));

			file_put_contents($filename, 'delete me');
			clearstatcache();
			if(!file_exists($filename))
				return false;							// was unable to create file
			unlink($filename);
			return true;
		}
		else
		{
			return is_writable($path);						// use PHPs own function for existing files
		}
	}

	function seria_getUser()
	{
		if(function_exists('posix_getpwuid'))
		{
			$tmp = posix_getpwuid();
			if($tmp === false)
			{
				return trim(`whoami`);
			}
			return $tmp['name'];
		}
		else if($tmp = getenv('USERNAME'))
		{
			return $tmp;
		}
		else
		{
			return '*Unknown*';
		}
	}

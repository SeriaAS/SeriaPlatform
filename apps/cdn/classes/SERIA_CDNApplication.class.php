<?php

	class SERIA_CDNApplication extends SERIA_Application
	{
		private $active = false;

		function setActive($state)
		{
			$this->active = $state;
		}

              // returns a string that uniquely identifies the application. Two applications that are incompatible can never share the unique name
                function getId() { return 'seria_cdn'; }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/cdn'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }

                // returns a string with the name of the application. This string should be translated before it is returned.
                function getName() { return _t('Seria CDN');}

                function getObjectId()
                {
                        return array("SERIA_Applications","getApplication", $this->getApplicationId());
                }

		function __construct()
		{
		}

		function guiEmbed($gui)
		{
			$gui->addMenuItem('cdn', $this->getName(), _t("Along with a caching reverse proxy, Seria CDN can speed up the performance of most websites."), SERIA_HTTP_ROOT.'/seria/apps/cdn/', SERIA_HTTP_ROOT.'/seria/apps/cdn/icon.png');
			if(SERIA_Base::hasRight('cdn_edit_servers'))
			{
				$gui->addMenuItem('cdn/servers', _t("Servers"), _t("Edit servers and hosts that are allowed to access your CDN."), SERIA_HTTP_ROOT.'/seria/apps/cdn/servers.php');
				$gui->addMenuItem('cdn/servers/list', _t("Servers"), _t("View servers and hosts that are allowed to access your CDN."), SERIA_HTTP_ROOT.'/seria/apps/cdn/servers.php');
				$gui->addMenuItem('cdn/servers/edit', _t("Add server"), _t("Add a backend server for your CDN."), SERIA_HTTP_ROOT.'/seria/apps/cdn/server.php');
			}
			if(SERIA_Base::hasRight('cdn_view_statistics'))
				$gui->addMenuItem('cdn/statistics', _t('Statistics'), _t("View usage statistics for your CDN."), SERIA_HTTP_ROOT.'/seria/apps/cdn/stats.php');
/*
			return array(
				'url' => SERIA_HTTP_ROOT.'/seria/apps/cdn/',
				'icon' => SERIA_HTTP_ROOT.'/seria/apps/cdn/icon.png',
				'caption' => $this->getName(),
				'active' => $this->active,
				'weight' => -100,
			);
*/
		}

		function router($url)
		{
			$hostname = str_replace("--",".",substr($_SERVER['HTTP_HOST'], 0, -strlen(SERIACDN_HOST)-1));

			try
			{
				$host = SERIA_CDNHostname::createByHostname($hostname);
				$b = new SERIA_WebBrowser();
				$b->navigateTo('http://'.$host->get('hostname').$url);
				$headers = $b->fetchHeaders();
				if($b->responseCode != 200)
				{
					SERIA_Base::displayErrorPage('400', $b->responseCode.' '.$b->responseString, 'Seria CDN only forwards standard requests that give a response code of 200. Received HTTP/1.1 '.$b->responseCode.' '.$b->responseString);
				}

				if(isset($headers['Content-Type']))
				{
					header('Content-Type: '.$headers['Content-Type']);
//					echo('Content-Type: '.$headers['Content-Type']);
				}

				if(isset($_GET['seria_ttl']))
					$ttl = intval($_GET['seria_ttl']);
				else {
// try to parse ttl from $headers
					$ttl = 60;
				}

				header('X-Powered-By: SeriaCDN/1.0');
				header('Cache-Control: max-age='.$ttl.', must-revalidate');
				header('Age: 0');
				
//				header('Pragma: public');

				SERIA_Template::override($headers['Content-Type'], file_get_contents('http://'.$host->get('hostname').$url));

				die();
			}
			catch (SERIA_Exception $e)
			{
				SERIA_Base::displayErrorPage(403, $e->getMessage(), _t('We are sorry, but the domain <strong>%domain%</strong> is illegal or has not been configured to be served by Seria CDN.', array('domain' => $hostname)));
			}

			return $t;
		}

	        function userEdit($form, $user)
	        {
	                if(!$user->isAdministrator())
	                        $form->subForm('CDNRights', new SERIA_CDNRightsForm($user), -1000);
	        }
	}

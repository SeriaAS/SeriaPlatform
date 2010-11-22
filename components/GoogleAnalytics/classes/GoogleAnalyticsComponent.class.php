<?php

class GoogleAnalyticsComponent extends SERIA_Component
{
	function getId()
	{
		return 'GoogleAnalyticsComponent';
	}
	function getName()
	{
		return _t('Google Analytics component');
	}
	function embed()
	{
		SERIA_Router::instance()->addRoute('GoogleAnalyticsComponent', 'Configure Google Analytics', array($this, 'showGoogleAnalyticsConfigure'), 'googleanalytics/configure');
	}
	public function init()
	{
		SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, array($this, 'guiEmbed'));
		if (SERIA_Base::viewMode() == 'public') {
			$ga = $this->getGoogleAnalyticsId();
			if ($ga !== null) {
				$template = new SERIA_MetaTemplate();
				$template->addVariable('googleAnalyticsId', $ga);
				SERIA_Template::headEnd('googleAnalytics', $template->parse($this->getInstallationPath().'/templates/googleanalytics_script.php'));
			}
		}
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	/**
	 *
	 * Callback when SERIA_Gui requests set-up. Please don't call.
	 * @param SERIA_Gui $gui
	 */
	public function guiEmbed(SERIA_Gui $gui)
	{
		$gui->addMenuItem('controlpanel/settings/googleanalytics', _t('Google Analytics'), _t('Configure Google Analytics on this site.'), SERIA_HTTP_ROOT.'?route=googleanalytics/configure', $this->getInstallationPath().'/icon.png', 100);
	}

	/**
	 *
	 * Get the Google Analytics Id.
	 * @return string Google analytics ID or null.
	 */
	public function getGoogleAnalyticsId()
	{
		$ga = SERIA_Base::getParam('GoogleAnalyticsId');
		if ($ga)
			return $ga;
		else
			return null;
	}

	/**
	 *
	 * Set the Google Analytics Id.
	 * @param string $ga The new Google Analytics Id or null to disable Google Analytics.
	 */
	public function setGoogleAnalyticsId($ga)
	{
		SERIA_Base::setParam('GoogleAnalyticsId', $ga);
	}

	/**
	 *
	 * Get an action-form for configuring Google Analytics.
	 * @return SERIA_ActionForm Action-form object.
	 */
	public function configureGoogleAnalyticsAction()
	{
		$action = new SERIA_ActionForm('GoogleAnalyticsConfigure');
		$spec = array(
			'googleAnalyticsEnabled' => array(
				'caption' => _t('Google Analytics:'),
				'fieldtype' => 'checkbox',
				'type' => 'tinyint(1)',
				'validator' => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array(0,1)))),
				'value' => $this->getGoogleAnalyticsId() ? true : false
			),
			'googleAnalyticsId' => array(
				'caption' => _t('Google Analytics Id: '),
				'fieldtype' => 'text',
				'type' => 'text',
				'validator' => new SERIA_Validator(array()),
				'helptext' => _t('Copy and paste from the code generated by Google Analytics. A text id like UA-????????-?'),
				'value' => $this->getGoogleAnalyticsId()
			)
		);
		foreach ($spec as $name => $fspec)
			$action->addField($name, $fspec, $fspec['value']);
		if ($action->hasData()) {
			/*
			 * Automatic validation
			 */
			$errors = array();
			foreach ($spec as $name => $fspec) {
				$error = $fspec['validator']->isInvalid($action->get($name));
				if ($error !== false)
					$errors[$name] = $error;
			}

			/*
			 * Specialized validation
			 */
			if (!count($errors)) {
				if ($action->get('googleAnalyticsEnabled')) {
					/* Enabled, validate fields based on that */
					$validator = new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED)
					));
					$error = $validator->isInvalid($action->get('googleAnalyticsId'));
					if ($error !== false)
						$errors['googleAnalyticsId'] = $error;
				}
			}

			/*
			 * Save data.
			 */
			if (!count($errors)) {
				if ($action->get('googleAnalyticsEnabled'))
					$this->setGoogleAnalyticsId($action->get('googleAnalyticsId'));
				else
					$this->setGoogleAnalyticsId(null);
				$action->success = true;
			} else
				$action->errors = $errors;
		}
		return $action;
	}

	/**
	 *
	 * Callback for router, please don't call.
	 */
	public function showGoogleAnalyticsConfigure()
	{
		$template = new SERIA_MetaTemplate();
		echo $template->parse($this->getInstallationPath().'/pages/configure.php');
		die();
	}
}

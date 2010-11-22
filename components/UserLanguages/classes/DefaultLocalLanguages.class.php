<?php

class DefaultLocalLanguages
{
	protected static $preLocales = array(
		'en-GB' => 'English (United Kingdom)',
		'en-US' => 'English (United States)',
		'nb-NO' => "Norsk bokm\303\245l",
		'nn-NO' => 'Norsk nynorsk'
	);


	public static function getDefaultLanguageLocales()
	{
		return self::$preLocales;
	}

	/**
	 *
	 * Get add default language actions. array(locale-id => local-name)
	 * @return array Add locale actions
	 */
	public static function getAddDefaultLanguageActions()
	{
		$actions = array();
		foreach (self::$preLocales as $id => $name) {
			$actions[$id] = new SERIA_ActionUrl('addDefaultLanguage', $id);
			if ($actions[$id]->invoked()) {
				$newLocale = new AvailableUserLocale();
				$newLocale->set('strid', $id);
				$newLocale->set('displayName', $name);
				SERIA_Meta::save($newLocale);
			}
		}
		return $actions;
	}
	/**
	 *
	 * Returns a associative array with (url => local-name) pairs.
	 * @return array Available default languages.
	 */
	public static function getAddDefaultLanguageActionUrls()
	{
		$actions = self::getAddDefaultLanguageActions();
		$urls = array();
		foreach ($actions as $id => $action) {
			if ($action->invoked()) {
				header('Location: '.$action->removeFromUrl(SERIA_Url::current()));
				die();
			}
			$urls[$action->__toString()] = self::$preLocales[$id];
		}
		return $urls;
	}
}
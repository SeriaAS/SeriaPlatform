<?php

class UserLanguagePreference
{
	/**
	 * 
	 * This hook is thrown when a user changes the preferred language, arguments are SERIA_User and Iso639Local.
	 * @var string
	 */
	const UPDATE_LANGUAGE_HOOK = 'UserLanguagePreference::updateLanguage';

	protected $user;

	/**
	 *
	 * Create a language locale preference object.
	 * @param SERIA_User $userObject The user. Default: current logged in user.
	 */
	public function __construct(SERIA_User $userObject=null)
	{
		if ($userObject !== null) {
			if (!SERIA_Base::isAdministrator() && (!SERIA_Base::user() || $userObject->get('id') != SERIA_Base::user()->get('id')))
				throw new SERIA_AccessDeniedException('Access denied!', 1);
		} else {
			$userObject = SERIA_Base::user();
			if (!$userObject)
				throw new SERIA_AccessDeniedException('Not logged in!', 2);
		}
		$this->user = $userObject;
	}

	/**
	 *
	 * Get user language locale object. Returns null if not set.
	 * @return Iso639Local
	 */
	public function getLocale()
	{
		$localeStr = $this->user->getMeta('languageLocale');
		if (!$localeStr)
			return null;
		return new Iso639Local($localeStr);
	}
	/**
	 *
	 * Get the string identifying the current language locale. Returns null if it is not set.
	 * @return string
	 */
	public function getLocaleString()
	{
		$locale = $this->getLocale();
		if ($locale === null)
			return null;
		return $locale->__toString();
	}
	/**
	 *
	 * Get the language of this user. Null if not set.
	 * @return Iso639Language
	 */
	public function getLanguage()
	{
		$locale = $this->getLocale();
		if ($locale === null)
			return null;
		return $locale->getLanguage();
	}
	/**
	 *
	 * Get the string identifying the language of this user (alpha2 or aplha3). Null if not set.
	 * @return string
	 */
	public function getLanguageString()
	{
		$language = $this->getLanguage();
		if ($language === null)
			return null;
		return $language->__toString();
	}
	/**
	 *
	 * Get the country of this user's language. Null if not set.
	 * @return Iso3166Country
	 */
	public function getCountry()
	{
		$locale = $this->getLocale();
		if ($locale === null)
			return null;
		return $locale->getCountry();
	}
	/**
	 *
	 * Get the string identifying the country of this user's language. Null if not set.
	 * @return string
	 */
	public function getCountryString()
	{
		$country = $this->getCountry();
		if ($country === null)
			return null;
		return $country->__toString();
	}

	/**
	 *
	 * Set the language locale for this user.
	 * @param mixed $locale Can be either an AvailableUserLocale, Iso639Local object, or a string
	 */
	public function setLanguageLocale($locale)
	{
		if (!$locale) {
			$this->user->setMeta('languageLocale', '');
			return;
		}
		if ($locale instanceof AvailableUserLocale)
			$locale = $locale->getIso639Local();
		if (!($locale instanceof Iso639Local)) {
			if (!is_string($locale))
				throw new SERIA_Exception('Language locale must be specified by either an Iso639Local object or a string.');
			$locale = new Iso639Local($locale);
		}
		if (!SERIA_Base::isAdministrator()) {
			/* Check whitelist */
			$whites = SERIA_Meta::all('AvailableUserLocale');
			$whitening = array();
			foreach ($whites as $white)
				$whitening[] = $white->getIso639Local()->__toString();
			if (!in_array($locale->__toString(), $whitening))
				throw new SERIA_AccessDeniedException('You are not allowed to set this language/locale. Must be enabled by the site administrator.', 1);
		}
		$this->user->setMeta('languageLocale', $locale->__toString());
		SERIA_Hooks::dispatch(UserLanguagePreference::UPDATE_LANGUAGE_HOOK, $this->user, $locale);
	}

	/**
	 *
	 * Get a set language locale action.
	 * @return SERIA_ActionForm
	 */
	public function getSetLanguageLocaleAction()
	{
		/*
		 * SERIA_ActionForm must have this as an array.
		 * ArrayAccess does not make is_array return true.
		 */
		$query = SERIA_Meta::all('AvailableUserLocale')->order('displayName');
		$values = array();
		foreach ($query as $value) {
			$values[$value->get('strid')] = $value;
			$codes[] = $value->getIso639Local()->__toString();
		}
		$value = $this->getLocaleString();
		if (!in_array($value, $codes))
			$value = null;
		$form = new SERIA_ActionForm('setLanguageLocaleAction');
		$spec = array(
			'caption' => _t('Select language: '),
			'validator' => new SERIA_Validator(array(
				array(SERIA_Validator::REQUIRED)
			)),
			'values' => $values,
			'value' => $value
		);
		$form->addField('languageLocale', $spec);
		if ($form->hasData()) {
			$error = $spec['validator']->isInvalid($form->get('languageLocale'));
			if (!$error) {
				try {
					$locale = new Iso639Local($form->get('languageLocale'));
				} catch (SERIA_Exception $e) {
					$error = $e->getMessage();
				}
			}
			if ($error)
				$form->errors = array('languageLocale' => $error);
			else {
				$form->errors = false;
				if ($form->get('languageLocale')) {
					$country = $locale->getCountry();
					if ($country !== null)
						$this->setLanguageLocale(SERIA_Meta::all('AvailableUserLocale')->where('language = :language AND country = :country', array(
							'language' => $locale->getLanguage()->getAlpha3(),
							'country' => $country->getAlpha2Code()
						))->current());
					else
						$this->setLanguageLocale(SERIA_Meta::all('AvailableUserLocale')->where('language = :language AND (country IS NULL OR country = :country)', array(
							'language' => $locale->getLanguage()->getAlpha3(),
							'country' => ''
						))->current());
				} else
					$this->setLanguageLocale(null);
			}
		}
		return $form;
	}
}

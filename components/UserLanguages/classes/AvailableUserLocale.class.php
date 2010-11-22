<?php

class AvailableUserLocale extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{available_user_locale_split}',
			'fields' => array(
				'language' => array('name required', _t('Aplha-3 language code: ')),
				'country' => array('name', _t('Alpha-2 country code: ')),
				'displayName' => array('name required', _t('Language name: '))
			)
		);
	}

	/*
	 * Compatibility with the strid combined field.
	 */
	public function set($name, $value)
	{
		switch ($name) {
			case 'strid':
				$locale = new Iso639Local($value);
				$this->set('language', $locale->getLanguage()->getAlpha3());
				$country = $locale->getCountry();
				if ($country !== null)
					$this->set('country', $country->getAlpha2Code());
				else
					$this->set('country', null);
				break;
			default:
				parent::set($name, $value);
		}
	}
	public function get($name)
	{
		switch ($name) {
			case 'strid':
				$country = $this->get('country');
				if (!$country)
					$country = null;
				$locale = new Iso639Local($this->get('language'), $country);
				return $locale->__toString();
				break;
			default:
				return parent::get($name);
		}
	}

	/**
	 *
	 * Get a locale-object.
	 * @return Iso639Local The object
	 */
	public function getIso639Local()
	{
		$country = $this->get('country');
		if (!$country)
			$country = null;
		return new Iso639Local($this->get('language'), $country);
	}

	/**
	 *
	 * Get add locale action form
	 */
	public static function getAddLocaleAction()
	{
		$locales = DefaultLocalLanguages::getDefaultLanguageLocales();
		$form = new SERIA_ActionForm('addLocaleAction');
		$spec = array(
			'caption' => _t('Select language locale: '),
			'validator' => new SERIA_Validator(array(
				array(SERIA_Validator::REQUIRED)
			)),
			'values' => $locales
		);
		$form->addField('strid', $spec);
		if ($form->hasData()) {
			$error = $spec['validator']->isInvalid($form->get('strid'));
			$c = null;
			if (!$error) {
				foreach ($locales as $c => $name) {
					if ($c == $form->get('strid'))
						break;
				}
				if ($c != $form->get('strid'))
					$error = _t('Invalid argument.');
			}
			if ($error) {
				$form->errors = array('strid' => $error);
				return $form;
			}
			$obj = new AvailableUserLocale();
			$obj->set('strid', $c);
			$obj->set('displayName', $name);
			SERIA_Meta::save($obj);
		}
		return $form;
	}

	/**
	 *
	 * Redirection action to an add language form
	 * @return SERIA_ActionUrl
	 */
	public static function getAddLocaleActionUrl()
	{
		$action = new SERIA_ActionUrl('addUserLangLocale');
		if ($action->invoked()) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=userlanguages/add');
			die();
		}
		return $action;
	}

	/**
	 *
	 * Get add custom locale action form
	 */
	public static function getAddCustomLocaleAction()
	{
		$importFields = array(
			'displayName'
		);
		$action = new SERIA_ActionForm('addCustomLocaleAction', new AvailableUserLocale(), $importFields);
		$languages = Iso639Language::getInfoTable();
		foreach ($languages as $alpha3 => &$info)
			$info = _t($info['english']);
		unset($info);
		$values = array(
			'language' => $languages,
			'country' => Iso3166Country::getAlpha2Table()
		);
		$myspec = array(
			'language' => array(
				'caption' => _t('Language: '),
				'values' => $values['language'],
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED)
				))
			),
			'country' => array(
				'caption' => _t('Country/region: '),
				'values' => $values['country'],
				'validator' => new SERIA_Validator(array(
				))
			)
		);
		foreach ($myspec as $name => $spec)
			$action->addField($name, $spec);
		if ($action->hasData()) {
			$obj = new self();

			/* validators */
			$errors = array();
			foreach ($myspec as $name => $spec) {
				$error = $spec['validator']->isInvalid($action->get($name));
				if ($error !== false)
					$errors[$name] = $error;
			}

			if (count($errors) == 0) {
				$language = $action->get('language');
				$country = $action->get('country');
				if (!$country)
					$country = null;
				$locale = new Iso639Local($language, $country);
				$obj->set('strid', $locale->__toString());
			}
			$obj->set('displayName', $action->get('displayName'));
			$action->errors = SERIA_Meta::validate($obj);
			if ($action->errors === false && count($errors) == 0)
				SERIA_Meta::save($obj);
			if ($action->errors !== false) {
				foreach ($importFields as $name) {
					if (isset($action->errors[$name])) {
						$errors[$name] = $action->errors[$name];
						unset($action->errors[$name]);
					}
				}
				if (count($errors) == 0 && count($action->errors) != 0)
					throw new SERIA_ValidationException('Unhandled validation errors', $action->errors);
			}
			if (count($errors) != 0)
				$action->errors = $errors;
		}
		return $action;
	}

	/**
	 *
	 * Redirection action to an add custom language form
	 * @return SERIA_ActionUrl
	 */
	public static function getAddCustomLocaleActionUrl()
	{
		$action = new SERIA_ActionUrl('addUserCustomLangLocale');
		if ($action->invoked()) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=userlanguages/addcustom');
			die();
		}
		return $action;
	}

	/**
	 * (non-PHPdoc)
	 * @see seria/components/SERIA_Mvc/classes/SERIA_MetaObject::__toString()
	 */
	public function __toString()
	{
		return $this->get('displayName');
	}
}
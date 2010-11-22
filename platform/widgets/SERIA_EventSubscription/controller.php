<?php

/*
 * Check PHP-module requirements..
 */
/* IMAP extension is required */
if (!function_exists('imap_binary'))
	throw new Exception('PHP-IMAP extension is required.');
/*
 * Check for PECL-HTTP module. If not define the required function here:
 */
if (!function_exists('http_build_url')) {
	define(HTTP_URL_REPLACE, 1); 
	define(HTTP_URL_JOIN_PATH, 2);
	define(HTTP_URL_JOIN_QUERY, 4);
	define(HTTP_URL_STRIP_USER, 8);
	define(HTTP_URL_STRIP_PASS, 16);
	define(HTTP_URL_STRIP_AUTH, HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS);
	define(HTTP_URL_STRIP_PORT, 32);
	define(HTTP_URL_STRIP_PATH, 64);
	define(HTTP_URL_STRIP_QUERY, 128);
	define(HTTP_URL_STRIP_FRAGMENT, 256);
	define(HTTP_URL_STRIP_ALL, HTTP_URL_STRIP_AUTH | HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_PATH | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT);
	function http_build_url($url, $parts, $flags = HTTP_URL_REPLACE, array &$newUrl = null)
	{
		if (is_string($url))
			$url = parse_url($url);
		if (is_string($parts))
			$parts = parse_url($parts);
		if (($flags & HTTP_URL_JOIN_PATH) != 0 && isset($parts['path']) && $parts['path']) {
			if (isset($url['path']) && $url['path']) {
				if ($parts['path'][0] != '/')
					$parts['path'] = '/'.$parts['path'];
				$firstPath = $url['path'];
				if (($pos = strrpos($firstPath, '/')) !== false)
					$firstPath = substr($firstPath, 0, $pos);
				$url['path'] = $firstPath.$parts['path'];
			} else
				$url['path'] = $parts['path'];
			unset($parts['path']);
		}
		if (($flags & HTTP_URL_JOIN_QUERY) != 0 && isset($parts['query']) && $parts['query']) {
			if (isset($url['query']) && $url['query'])
				$url['query'] = implode('&', array_merge(explode('&', $url['query']), explode('&', $parts['query'])));
			else
				$url['query'] = $parts['query'];
			unset($parts['query']);
		}
		foreach ($parts as $nam => $val) {
			if (($flags & HTTP_URL_REPLACE) != 0 || !isset($url[$nam]) || !$url[$nam])
				$url[$nam] = $val;
		}
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['user'] = null;
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['pass'] = null;
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['port'] = null;
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['path'] = null;
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['query'] = null;
		if (($flags & HTTP_URL_STRIP_USER) != 0)
			$url['fragment'] = null;
		if ($newUrl !== null)
			$newUrl = $url;
		$coded = (isset($url['scheme']) && $url['scheme'] ? $url['scheme'].'://' : '');
		if (isset($url['user'])) {
			$coded .= $url['user'];
			if (isset($url['pass']))
				$coded .= ':'.$url['pass'];
			$coded .= '@';
		}
		if (isset($url['host']) && $url['host'])
			$coded .= $url['host'];
		if (isset($url['port']) && $url['port'])
			$coded .= ':'.$url['port'];
		if (isset($url['path']) && $url['path'])
			$coded .= $url['path'];
		if (isset($url['query']) && $url['query'])
			$coded .= '?'.$url['query'];
		if (isset($url['fragment']) && $url['fragment'])
			$coded .= '#'.$url['fragment'];
		return $coded;
	}
}

function encode_header_value($value, $charset)
{
	$chunks = array();
	$len = strlen($value);
	while ($len > 0) {
		$splitLen = 96; /* /2=48 */
		do {
			$splitLen /= 2;
			$chunk = mb_substr($value, 0, $splitLen, $charset);
		} while (($chunkLen = strlen($chunk)) > 48);
		if ($len > $chunkLen) {
			$len -= $chunkLen; 
			$value = substr($value, $chunkLen, $len);
		} else
			$len = 0;
		$b64 = base64_encode($chunk);
		$chunks[] = '=?'.$charset.'?B?'.$b64.'?=';
	}
	return implode("\r\n ", $chunks);
}

class SERIA_EventSubscriptionForm extends SERIA_Form
{
	public $widget;
	public $dataHandler;

	/* More or less a hack to identify one of several duplicates as the
	 * form that actually was submitted.
	 */
	public $viewNumber; 

	public function __construct(&$widget, &$object)
	{
		parent::__construct();
		$this->dataHandler =& $object;
		$this->widget =& $widget;
	}

	public static function caption()
	{
		$article = $this->widget->getNamedObject();
		return _t('Subscribe to event: %EVENT%', array('EVENT' => $article->get('title')));
	}
	public function get($nam)
	{
		switch ($nam) {
			case 'form_viewNumber':
				return $this->viewNumber;
			case 'captcha':
				return '';
			default:
				if($this->validationData !== NULL && isset($this->validationData[$nam]))
					return $this->validationData[$nam];
				return $this->dataHandler->get($nam);
		}
	}
	public static function getFormSpec()
	{
		return array(
			'form_viewNumber' => array(
				'caption' => 'System-controller id field',
				'fieldtype' => 'hidden',
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED)
				), false)
			),
			'widget_id' => array(
				'caption' => 'System-controlled widget-id field',
				'fieldtype' => 'hidden',
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED)
				), false)
			),
			'name' => array(
				'fieldtype' => 'text',
				'caption' => _t('Name:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 100),
					array(SERIA_Validator::REQUIRED)
				), true)
			),
			'address' => array(
				'fieldtype' => 'text',
				'caption' => _t('Address:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 100)
				), true)
			),
			'zip' => array(
				'fieldtype' => 'text',
				'caption' => _t('Zip-code:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 20)
				), true)
			),
			'city' => array(
				'fieldtype' => 'text',
				'caption' => _t('City:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 50)
				), true)
			),
			'phone' => array(
				'fieldtype' => 'text',
				'caption' => _t('Phone:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 50)
				), true)
			),
			'orgNum' => array(
				'fieldtype' => 'text',
				'caption' => _t('Organization-number:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 50)
				), true)
			),
			'email' => array(
				'fieldtype' => 'text',
				'caption' => _t('E-mail:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 256),
					array(SERIA_Validator::EMAIL),
					array(SERIA_Validator::REQUIRED)
				), true)
			),
			'company' => array(
				'fieldtype' => 'text',
				'caption' => _t('Company:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 100)
				), true)
			),
			'billingAddress' => array(
				'fieldtype' => 'text',
				'caption' => _t('Billing address:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 100)
				), true)
			),
			'billingZip' => array(
				'fieldtype' => 'text',
				'caption' => _t('Zip-code:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 20)
				), true)
			),
			'billingCity' => array(
				'fieldtype' => 'text',
				'caption' => _t('City:'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::MAX_LENGTH, 50)
				), true)
			),
			'otherInfo' => array(
				'fieldtype' => 'text',
				'caption' => _t('Other info:'),
				'validator' => new SERIA_Validator(array(
				), false)
			),
			'captcha' => array(
				'fieldtype' => 'captcha',
				'caption' => _t('Type the numbers exactly:'),
			)
		);
	}
	public function _handle($data)
	{
		$publicFields = $this->dataHandler->getPublicFields();
		$spec = $this->getFormSpec();
		foreach (array_keys($spec) as $nam) {
			if (in_array($nam, $publicFields) && isset($data[$nam]))
				$this->dataHandler->$nam = $data[$nam];
		}
		if ($this->widget->get('deadline') && $this->widget->get('deadline') < time())
			throw new Exception(_t('The deadline for subscribing to this event has expired.'));
		if ($this->dataHandler->save() === false)
			throw new Exception(_t('Failed to subscribe to the event. (dbfail)'));
		if ($this->widget->get('participants') !== '' && $this->widget->countAllSubscribers() > $this->widget->get('participants')) {
			$this->dataHandler->delete();
			throw new Exception(_t('This event is full.'));
		}
		return true;
	}
}

class SERIA_EventSubscriptionRecord extends SERIA_ActiveRecord {
	public $tableName = '_widgets_event_subscription';
	public $usePrefix = true;
	private $publicFields = array(
		'name',
		'address',
		'zip',
		'city',
		'phone',
		'orgNum',
		'email',
		'company',
		'billingAddress',
		'billingZip',
		'billingCity',
		'otherInfo'
	);
	
	public function getSubscribeForm($widget)
	{
		$form = new SERIA_EventSubscriptionForm($widget, $this);
		return $form;
	}
	public function getForm($form='subscribe')
	{
		$args = func_get_args();
		if ($args)
			array_shift($args);
		$fc = substr($form, 0, 1);
		$form = 'get'.strtoupper($fc).substr($form, 1).'Form';
		return call_user_func_array(array($this, $form), $args);
	}

	public function set($nam, $val)
	{
		if (!in_array($nam, $this->publicFields))
			throw new Exception('Access denied to private field.');
		$this->$nam = $val;
	}
	public function get($nam)
	{
		switch ($nam) {
			case 'widget_id':
				return $this->$nam;
		}
		if (!in_array($nam, $this->publicFields))
			throw new Exception('Access denied to private field.');
		return $this->$nam;
	}
	public function getPublicFields()
	{
		return $this->publicFields;
	}
}

class SERIA_EventSubscriptionAdminForm extends SERIA_Form
{
	private $tmp = array();

	public function _handle($data)
	{
		foreach (array_keys($this->getFormSpec()) as $nam) {
			switch ($nam) {
				case 'enabled':
					$this->object->set($nam,  (isset($data[$nam]) && $data[$nam]) ? true : false);
					break;
				case 'deadline_date':
				case 'deadline_tod':
					if (isset($data[$nam]))
						$this->tmp[$nam] = $data[$nam];
					else
						$this->tmp[$nam] = false;
					if (isset($this->tmp['deadline_date']) && isset($this->tmp['deadline_tod'])) {
						if ($this->tmp['deadline_date']) {
							$locale = SERIA_Locale::getLocale();
							$ts = $locale->stringToTime($this->tmp['deadline_date'], 'date');
							if ($this->tmp['deadline_tod'])
								$ts = $locale->stringToTime($this->tmp['deadline_tod'], 'time', $ts);
							$this->object->set('deadline', $ts);
						} else
							$this->object->set('deadline', false);
					}
					break;
				default:
					if (isset($data[$nam]))
						$this->object->set($nam, $data[$nam]);
			}
		}
	}
	public static function caption()
	{
		return _t('Event subscription settings');
	}
	public static function getFormSpec()
	{
		$dataSpecFixed = array(
			'enabled' => array(
				'caption' => _t('Enable subscription module'),
				'validator' => array(),
				'trim' => false,
				'fieldtype' => 'checkbox'
			),
			'participants' => array(
				'caption' => _t('Max participants:'),
				'validator' => array(),
				'trim' => false,
				'fieldtype' => 'text'
			),
			'place' => array(
				'caption' => _t('Place:'),
				'validator' => array(),
				'trim' => false,
				'fieldtype' => 'text'
			),
			'price' => array(
				'caption' => _t('Price:'),
				'validator' => array(),
				'trim' => false,
				'fieldtype' => 'text'
			),
			'deadline_date' => array(
				'caption' => _t('Subscription deadline date:'),
				'validator' => array(
					array(SERIA_Validator::LOCAL_DATE)
				),
				'trim' => true,
				'fieldtype' => 'text',
				'selectorClasses' => array('datepicker')
			),
			'deadline_tod' => array(
				'caption' => _t('Subscription deadline time of day:'),
				'validator' => array(
					array(SERIA_Validator::LOCAL_TIME)
				),
				'trim' => true,
				'fieldtype' => 'text'
			),
			'email' => array(
				'caption' => _t('Email:'),
				'validator' => array(
					array(SERIA_Validator::EMAIL)
				),
				'trim' => true,
				'fieldtype' => 'text'
			)
		);
		$spec = array();
		foreach ($dataSpecFixed as $nam => $val) {
			$val['validator'] = new SERIA_Validator($val['validator'], $val['trim']);
			unset($val['trim']);
			$spec[$nam] = $val;
		}
		return $spec;
	}
	public function validate($data)
	{
		$err = parent::validate($data);
		if (!$err)
			$this->object->errors = $this->errors;
		return $err;
	}
}

class SERIA_EventSubscriptionWidget extends SERIA_Widget implements SERIA_IFluentAccess
{
	protected $attachedEvents = array('DELETE' => 'onDelete');
	protected $localVariables = array();
	public $errors = false;

	/*
	 * Counted by view/index.php on each page view.
	 */
	protected static $widgetViewCount = 0;

	protected function onDelete($source) {
		$this->delete();
	}

	public function getGUID()
	{
		return $this->guid;
	}

	public function action_index() {
	}
	public function action_admin() {
	}
	public function action_adminPopup() {
	}
	public function action_client() {
	}

	/* XXX - Hack to plug into SERIA_Form */
	public static function getFieldSpec()
	{
		return SERIA_EventSubscriptionAdminForm::getFormSpec();
	}

	public function getAdminForm()
	{
		$form = new SERIA_EventSubscriptionAdminForm($this);
		return $form;
	}
	public function getSubscribeForm()
	{
		try {
			$record = new SERIA_EventSubscriptionRecord();
			$record->widget_id = $this->getId();
			return $record->getForm('subscribe', $this);
		} catch (PDOException $e) {
			return false;
		}
	}
	public function getForm($form='admin')
	{
		$fc = substr($form, 0, 1);
		$form = 'get'.strtoupper($fc).substr($form, 1).'Form';
		return $this->$form();
	}

	public function getSubscriber($id)
	{
		$record = SERIA_EventSubscriptionRecords::find_first_by_id($id);
		if ($record->widget_id == $this->getId())
			return $record;
		else
			return null;
	}
	public function getAllSubscribers()
	{
		try {
			$records = SERIA_EventSubscriptionRecords::find_all_by_widget_id($this->getId());
			return $records;
		} catch (PDOException $e) {
			/* Could be a missing database table */
			if ($e->getCode() !== '42S02')
				throw $e; /* Not a missing db-table */
			/* Ignoring missing table */
		}
		return false;
	}
	public function countAllSubscribers()
	{
		$subs =  $this->getAllSubscribers();
		if ($subs === false)
			return 0;
		return $subs->count;
	}

	public function set($nam, $val)
	{
		switch ($nam) {
			case 'redirect_url':
				$this->localVariables[$nam] = $val;
				return $this;
		}
		parent::set($nam, $val);
	}
	public function get($nam)
	{
		switch ($nam) {
			case 'deadline_date':
				$ts = parent::get('deadline');
				if (!$ts)
					return '';
				$locale = SERIA_Locale::getLocale();
				return $locale->timeToString($ts, 'date');
			case 'deadline_tod':
				$ts = parent::get('deadline');
				if (!$ts)
					return '';
				$locale = SERIA_Locale::getLocale();
				return $locale->timeToString($ts, 'time');
			case 'redirect_url':
				if (isset($this->localVariables[$nam]))
					return $this->localVariables[$nam];
				else
					return null;
			default:
				return parent::get($nam);
		}
	}
}

?>

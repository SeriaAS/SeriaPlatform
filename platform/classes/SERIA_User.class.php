<?php
	class SERIA_User implements SERIA_NamedObject, SERIA_IFluentObject, ArrayAccess, SERIA_IMetaField
	{
		private $_row,
			$_rights = false,
			$_db;

		const DELETE_HOOK = 'SERIA_User::DELETE_HOOK';
		const AFTER_DELETE_HOOK = 'SERIA_User::AFTER_DELETE_HOOK';
		
		public function offsetExists($name)
		{
			return isset($this->_row[$name]);
		}

		public function offsetGet($name)
		{
			return $this->_row[$name];
		}

		public function offsetSet($name, $value)
		{
			throw new SERIA_Exception('Can\'t assign values to user.');
		}

		public function offsetUnset($name)
		{
			throw new SERIA_Exception('Can\'t unset values on user objects.');
		}
				
		public function getObjectId() {
			if(!isset($this->_row['id']) || !$this->_row['id'])
				throw new SERIA_Exception("I have no ID!");
			return array("SERIA_User","createObject",intval($this->_row['id']));
		}

		function getKey()
		{
			return $this->_row['id'];
		}

		function __construct()
		{
			SERIA_ProxyServer::privateCache(86400);
			$args = func_get_args();
			if(is_array($args[0]))
			{
				$this->_row = $args[0];
			}
			else if(is_numeric($args[0]) && ($id = intval($args[0])))
			{
				$this->_row = SERIA_Base::db()->query('SELECT * FROM {users} WHERE id=:id AND enabled=1', array('id' => $id))->fetch(PDO::FETCH_ASSOC);
				if(!$this->_row) throw new SERIA_NotFoundException('No such user');
			} else
				$this->_row = array();
			SERIA_Hooks::dispatch('SERIA_User::__construct', $this);
		}

		protected static function createDatabaseTable()
		{
			if (SERIA_COMPATIBILITY >= 3) {
				$ct = "
					CREATE TABLE `seria_users` (
					  `id` int(11) NOT NULL DEFAULT '0',
					  `first_name` varchar(50) DEFAULT NULL,
					  `last_name` varchar(50) DEFAULT NULL,
					  `display_name` varchar(100) DEFAULT NULL,
					  `username` varchar(50) DEFAULT NULL,
					  `password` varchar(50) DEFAULT NULL,
					  `email` varchar(100) DEFAULT NULL,
					  `is_administrator` int(1) DEFAULT '0',
					  `enabled` int(1) DEFAULT '1',
					  `password_change_required` tinyint(1) NOT NULL DEFAULT '0',
					  `is_guest` tinyint(1) NOT NULL DEFAULT '0',
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8
				";
				SERIA_Base::db()->exec($ct);
				$ct = "
					CREATE TABLE `seria_user_meta_value` (
					  `name` varchar(100) NOT NULL DEFAULT '',
					  `owner` int(11) NOT NULL DEFAULT '0',
					  `value` text,
					  `timestamp` datetime DEFAULT NULL,
					  PRIMARY KEY (`name`,`owner`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8
				";
				SERIA_Base::db()->exec($ct);
				$user = new SERIA_User();
				$user->set('first_name', 'Super');
				$user->set('last_name', 'User');
				$user->set('display_name', 'Admininstrator');
				$user->set('username', 'admin');
				$user->set('password', 'admin123');
				$user->set('email', 'post@seria.no');
				$user->set('is_administrator', 1);
				$user->set('enabled', 1);
				$user->set('password_change_required', 0);
				$user->set('guestAccount', 0);
				if (!SERIA_Base::elevateUser(array($user, 'save'))) {
					throw new SERIA_Exception('Unable to save user');
				}
			}
		}
		/**
		*	Login a user to the system by either e-mail address or username
		*/
		static function login($username, $password)
		{
			SERIA_ProxyServer::noCache();

			$username = mb_strtolower($username, "UTF-8");

			try {
				$userRow = SERIA_Base::db()->query('SELECT * FROM {users} WHERE enabled=1 AND (username=:username OR email=:username) AND password=:password', array(
					'username' => $username,
					'password' => md5($password),
				))->fetch(PDO::FETCH_ASSOC);
			} catch (PDOException $e) {
				if ($e->getCode() == '42S02' && SERIA_COMPATIBILITY >= 3) /* Tbl not found */ {
					static::createDatabaseTable();
					return static::login($username, $password);
				}
				throw $e;
			}


			if(!$userRow)
			{ // fallback to cleartext passwords, to be removed
				$userRow = SERIA_Base::db()->query('SELECT * FROM {users} WHERE enabled=1 AND (username=:username OR email=:username) AND password=:password', array(
					'username' => $username,
					'password' => $password,
				))->fetch(PDO::FETCH_ASSOC);

				if($userRow)
				{
					// fix md5 of password
					$pw = md5($userRow['password']);
					SERIA_Base::db()->exec('UPDATE {users} SET password=? WHERE id=?', array($pw, $userRow['id']));
				}
			}

			if ($userRow)
			{
				$user = new SERIA_User($userRow);
				return SERIA_Base::user($user);
			}
			else
			{
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('%USER%@%IP%: Incorrect username or password.', array('USER' => $username, 'IP' => $_SERVER['REMOTE_ADDR'])), 'security');
			}
			return false;
		}

		const LOGOUT_ACTION = 'userLogout';
		static function logoutAction($continueUrl=false) {
			SERIA_ProxyServer::noCache();
			$action = new SERIA_ActionUrl(self::LOGOUT_ACTION, NULL, $continueUrl);

			if ($action->invoked()) {
				$continueUrl = $action->getState();
				self::logout();
				if ($continueUrl !== false) {
					SERIA_Base::redirectTo($continueUrl);
					die();
				}
				$action->success = true;
			}
			return $action;
		}

		/**
		 *
		 * Get a login url. You can put redirect to this url or link to it to log the user in.
		 * @param mixed $continueUrl
		 * @return SERIA_Url
		 */
		public static function loginUrl($continueUrl)
		{
			if (is_object($continueUrl))
				$continueUrl = $continueUrl->__toString();
			if (file_exists(SERIA_ROOT.'/login.php'))
				$url = SERIA_HTTP_ROOT.'/login.php';
			else if(SERIA_CUSTOM_PAGES_ROOT && file_exists(SERIA_CUSTOM_PAGES_ROOT."/login.php"))
				$url = SERIA_CUSTOM_PAGES_HTTP_ROOT."/login.php";
			else
				$url = SERIA_HTTP_ROOT."/seria/platform/pages/login.php";
			$url = new SERIA_Url($url);
			return $url->setParam('continue', $continueUrl);
		}

		const LOGIN_ACTION = 'userLogin';
		/**
		 * DEPRECATED
		 *
		 * Returns a login-action object. Due to redirects you will never get an invoked action object (the method does not return invoked).
		 * @param $continueUrl Continue url, the browser is redirected here when the user is logged in.
		 * @param $failureUrl Failure url, the browser is redirected here when login fails. (Not yet supported)
		 * @throws SERIA_Exception
		 */
		public static function loginAction($continueUrl, $failureUrl=false)
		{
			$action = new SERIA_ActionUrl(self::LOGIN_ACTION, NULL, array('continue' => $continueUrl, 'failure' => $failureUrl));
			if ($action->invoked()) {
				$state = $action->getState();
				if ($state === null)
					throw new SERIA_Exception('Lost state information sent through the SERIA_ActionUrl object!');
				$continueUrl = $state['continue'];
				$failureUrl = $state['failure'];

				/* There are two possible outcomes here based on login status, so no caching please! */
				SERIA_ProxyServer::noCache();

				if (SERIA_Base::user() === false) {
					SERIA_Base::redirectTo(self::loginUrl($continueUrl)->__toString());
					die();
				} else {
					SERIA_Base::redirectTo($continueUrl);
					die();
				}
			}
			return $action;
		}

		static function logout()
		{
			SERIA_ProxyServer::noCache();
			return SERIA_Base::user(NULL);
		}

		/**
		*	Login user
		*/
		function setLoggedIn()
		{
			SERIA_ProxyServer::noCache();
			return SERIA_Base::user($this);
		}

		/**
		*	Validates fields based on fieldname and value. $userId means that it should ignore the user with id=$userId
		*/
		static function isInvalid($fieldName, &$value, $userId=0)
		{
			$db = SERIA_Base::db();

			$value = trim($value);
			switch($fieldName)
			{
				case "id" : 
					return "ID can't be changed.";
				case "firstName" : 
				case "lastName" :
				case "displayName" :
					return SERIA_IsInvalid::name($value, true);
				case "username" :
					$value = mb_strtolower($value, "UTF-8");
					if($db->query("SELECT COUNT(*) FROM ".SERIA_PREFIX."_users WHERE id<>".intval($userId)." AND enabled=1 AND username=".$db->quote($value))->fetch(PDO::FETCH_COLUMN, 0))
						return _t("This username already exists. Please choose a different username.");
					if($e = SERIA_IsInvalid::username($value, true))
						return $e;
					return false;
				case "password" :
//					if(strlen(trim($value))==32 && trim($value, '0123456789abcdef')=='')
//						return false; // md5 hash is correct
					return SERIA_IsInvalid::password($value,true);
				case "email" :
					return SERIA_IsInvalid::eMail($value,true);

				case "password_change_required":
					return false;
				case 'is_administrator' :
					if(!SERIA_Base::isAdministrator() && (!SERIA_Base::isElevated() || SERIA_Base::isLoggedIn()) && $value)
						return _t('Only administrators can create administrator accounts.');
					return false;
				case 'is_guest':
				case 'guestAccount':
					if (!SERIA_Base::isAdministrator() && !SERIA_Base::isElevated() && !SERIA_Base::hasSystemAccess() && !$value)
						return _t('Access denied creating a system account.');
					return false;
					break;
				default : throw new SERIA_Exception("Unknown field '$fieldName'.");
			}
		}

		/**
		*	Create a user account
		*/
		public function create(&$firstName=null, &$lastName=null, &$displayName=null, &$username=null, &$password=null, &$email=null, $isAdministrator=false)
		{
			SERIA_ProxyServer::noCache();

			if (!$firstName && !$lastName && ($this && (get_class($this) == 'SERIA_User'))) {
				// This is a create event from Active Record
				$this->_db = SERIA_Base::db();
				$this->_row['is_administrator'] = 0;
				$this->_row['enabled'] = 1;
				$this->_row['password_change_required'] = 0;
				$this->_row['is_guest'] = 0;
				$this->_rights = false;
				
				return;
			}
			
			if(!SERIA_Base::isAdministrator() && !SERIA_Base::isElevated())
				throw new SERIA_Exception("Access Denied. Requires Administrator privileges to create users.");

			$db = SERIA_Base::db();

			$username = trim(mb_strtolower($username, "UTF-8"));
			$email = trim(mb_strtolower($email, "UTF-8"));
			$displayName = trim($displayName);

			// validate all fields

			$errors = array();

			if($e = SERIA_User::isInvalid("firstName", $firstName))
				$errors["firstName"] = $e;
			if($e = SERIA_User::isInvalid("lastName", $lastName))
				$errors["lastName"] = $e;
			if($e = SERIA_User::isInvalid("displayName", $displayName))
				$errors["displayName"] = $e;
			if($e = SERIA_User::isInvalid("username", $username))
				$errors["username"] = $e;
			if($e = SERIA_User::isInvalid("password", $password))
				$errors["password"] = $e;
			if($e = SERIA_User::isInvalid("email", $email))
				$errors["email"] = $e;
			
			if(sizeof($errors)>0)
			{
				throw new SERIA_ValidationException(_t("Please check for errors."), $errors);
				return false;
			}

			$user = new SERIA_User();
			$user->set('first_name', $firstName);
			$user->set('last_name', $lastName);
			$user->set('display_name', $displayName);
			$user->set('username', $username);
			$user->set('password', $password);
			$user->set('email', $email);
			$user->set('is_administrator', (int) (bool) $isAdministrator);
			$user->set('enabled', 1);
			$user->set('password_change_required', 0);
			$user->set('guestAccount', 0);
			if (!$user->save()) {
				throw new SERIA_Exception('Unable to save user');
			}
			
			return $user;
		}

		static function createObject($id=false)
		{
			if($id===false) {
				$user = new SERIA_User();
				$user->set('enabled', 1);
				return $user;
			}

			static $cache = array();

			if(is_array($id))
			{
				if(isset($cache[$id['id']]))
					return $cache[$id['id']];
				$user = new SERIA_User($id);
				return $cache[$id['id']] = $user;
			}
			else if(is_numeric($id))
			{
				if(isset($cache[$id]))
					return $cache[$id];
				else
					return $cache[$id] = new SERIA_User($id);
			}
			throw new SERIA_Exception(_t('User not found'));
		}

		function delete()
		{
			SERIA_ProxyServer::noCache();

			if(!SERIA_Base::isAdministrator() && !SERIA_Base::isElevated())
				throw new SERIA_Exception("Access Denied. Requires Administrator privileges to delete users.");

			// Users (Adminitrators) can't delete themselves.
			if($this->_row['id'] == SERIA_Base::user()->id)
				throw new SERIA_Exception(_t("You can't delete yourself!"));

		        /**
		        *       HOOK: Allows you to create a form that will be added to a tab below the user editing screen. Return an array with this configuration:
		        *       *_hook('delete', false, $user_object);
		        *
		        *       $op = 'delete':         User is being deleted. Please clean up any stored information and flush caches.
		        */
			SERIA_Hooks::dispatch('seria_user_edit', $null='delete', $null=false, $user);

			$this->_row['enabled'] = 0;
			
			// Sets username, email and enabled to 0 if deleted. Allows other users to use the username/email
			$this->_row['username'] = '';
			$this->_row['email'] = '';

			$this->save();
		}

		/**
		 * This deletes the user from the database permanently. Use this method to implement delete-me-features
		 * for website users. It dispatches the SERIA_User::DELETE_HOOK before actually deleting the user, so
		 * all application features connected to the user-object should act upon that hook and detach or delete
		 * any user-related content.
		 *
		 * @param SERIA_User $user
		 */
		public static function deleteUserPermanently(SERIA_User $user)
		{
			SERIA_Hooks::dispatch(self::DELETE_HOOK, $user);
			SERIA_Base::db()->exec('DELETE FROM {user_meta_value} WHERE owner = :owner', array(
				'owner' => $user->get('id')
			));
			SERIA_PropertyList::deleteAll($user);

			/*
			 * Finally delete the user:
			 */
			SERIA_Base::db()->exec('DELETE FROM {users} WHERE id = :id', array('id' => $user->get('id')));
			SERIA_Hooks::dispatch(self::AFTER_DELETE_HOOK, $user);
		}

		/**
		*	Validates $this->_row and throws SERIA_ValidationException
		*/
		function validate()
		{
			$errors = array();

			if($e = SERIA_User::isInvalid('guestAccount', $this->_row['is_guest'], $this->row['id']))
				$errors['firstName'] = $e;
			if($e = SERIA_User::isInvalid("is_administrator", $this->_row['is_administrator'], $this->_row['id']))
				$errors["firstName"] = $e;
			if($e = SERIA_User::isInvalid("firstName", $this->_row['first_name'], $this->_row['id']))
				$errors["firstName"] = $e;
			if($e = SERIA_User::isInvalid("lastName", $this->_row['last_name'], $this->_row['id']))
				$errors["lastName"] = $e;
			if($e = SERIA_User::isInvalid("displayName", $this->_row['display_name'], $this->_row['id']))
				$errors["displayName"] = $e;
			if($e = SERIA_User::isInvalid("username", $this->_row['username'], $this->_row['id']))
				$errors["username"] = $e;
			if($e = SERIA_User::isInvalid("password", $this->_row['password'], $this->_row['id']))
				$errors["password"] = $e;
			if($e = SERIA_User::isInvalid("email", $this->_row['email'], $this->_row['id']))
				$errors["email"] = $e;
			if(sizeof($errors)>0)
				throw new SERIA_ValidationException("Check for errors.", $errors);

			return true;
		}

		function saveEditByUser()
		{
			if (SERIA_Base::user()->id !== $this->_row['id'])
				throw new SERIA_Exception('Access denied.');
			$this->validate();

			$this->save();
		}
		function save()
		{
			SERIA_ProxyServer::noCache();
			if(!(SERIA_Base::isAdministrator() || SERIA_Base::isElevated()))
				throw new SERIA_Exception("Access Denied. Requires Administrator privileges to edit users.");

			$db = SERIA_Base::db();

			$this->validate();

			if(!$this->_row['id'])
			{ // inserting a new user
				$this->_row['id'] = SERIA_Base::guid();
				$res = $db->insert('{users}', array('id', 'first_name', 'last_name', 'display_name', 'username', 'password', 'email', 'is_administrator', 'enabled', 'password_change_required', 'is_guest'), $this->_row);
				if($res)
					SERIA_PropertyList::createObject($this)->save();
				return $res;
			}
			else
			{ // updating an existing user
				$db->exec("UPDATE {users} SET first_name=:first_name, last_name=:last_name, display_name=:display_name, username=:username, password=:password, email=:email, is_administrator=:is_administrator, enabled=:enabled, password_change_required=:password_change_required, is_guest = :is_guest WHERE id=:id", $this->_row);
				SERIA_PropertyList::createObject($this)->save();
				return true;
			}
		}

		function isAdministrator()
		{
			SERIA_ProxyServer::privateCache();
			return isset($this->_row['is_administrator']) && $this->_row['is_administrator'] ? true : false;
		}

		function isGuest()
		{
			SERIA_ProxyServer::privateCache();
			return isset($this->_row['is_guest']) && $this->_row['is_guest'] ? true : false;
		}

		function set($field, $value)
		{
			SERIA_ProxyServer::noCache();
			switch($field)
			{
				case "firstName" :  case "first_name" : $field = "first_name"; break;
				case "lastName" : case "last_name" : $field = "last_name"; break;
				case "displayName" : case "display_name" : $field = "display_name"; break;
				case "password" : $value = md5($value); break;
				case "username" : case "email" : case "id" : break;
				case "isAdministrator" : case "is_administrator" : $field = "is_administrator"; $value = $value ? "1" : "0"; break;
				case "password_change_required": break;
				case "enabled" : $field = "enabled"; break;
				case "is_guest" : case 'guestAccount': $field = 'is_guest'; $value = $value ? "1" : "0"; break;
				default : throw new SERIA_Exception(_t("Unknown field name '$field'."));
			}

			$this->_row[$field] = $value;
		}

		function get($field)
		{
			switch($field)
			{
				case "firstName" : case "first_name" : $field = "first_name"; break;
				case "lastName" : case "last_name" : $field = "last_name"; break;
				case "displayName" : case "display_name" : $field = "display_name"; break;
				case "username" : case "password" : case "email" : case "id" : break;
				case "password_change_required": break;
				case 'isAdministrator' : case 'is_administrator' : break;
				case 'is_guest': case 'guestAccount': $field = 'is_guest'; break;
				default : throw new SERIA_Exception(_t("Unknown field name '$field'."));
			}

			return $this->_row[$field];
		}

		public function getUsers()
		{
			return new SERIA_FluentQuery('SERIA_User', 'enabled=1');
		}

		public function getDisabledUsers()
		{
			return new SERIA_FluentQuery('SERIA_User', 'enabled=0');
		}

		/**
		*	Returns true if the current user has the specified right
		*/
		function hasRight($type)
		{
			SERIA_ProxyServer::privateCache();
			if($this->isAdministrator())
				return true;
			return SERIA_PropertyList::createObject($this)->get('right:'.$type);
		}

		function setRight($type, $value)
		{
			SERIA_ProxyServer::noCache();
			if($this->isAdministrator())
				throw new SERIA_Exception(_t("This is an administrator account."));
			$pl = SERIA_PropertyList::createObject($this);
			if($value)
				$pl->set('right:'.$type, 1);
			else
				$pl->delete('right:'.$type);

		}
		
		/**
		*	Adds the right to the user
		*/
		function addRight($type)
		{
			SERIA_ProxyServer::noCache();
			if($this->isAdministrator())
				throw new SERIA_Exception(_t("This is an administrator account."));
			return SERIA_PropertyList::createObject($this)->set($type, 1);
		}

		/**
		*	Remove a right from the user
		*/
		function removeRight($type)
		{
			SERIA_ProxyServer::privateCache();
			if($this->isAdministrator())
				throw new SERIA_Exception(_t("This is an administrator account."));

			return SERIA_PropertyList::createObject($this)->set($type, NULL);
		}

		function getContextMenu() {
			if(!SERIA_Base::isAdministrator())
				return "";

			$items = array();
			$items[] = _t("Edit user <strong>%DISPLAY_NAME%</strong>", $this->_row).":top.SERIA.editUser(".$this->_row['id'].");";
			
			if(sizeof($items)) {
				return " mnu=\"".implode("|", $items)."\" ";
			}
			return "";
		}
		
		public function __toString() {
			return $this->_row['display_name'];
		}

		public function getCustomRight($rightName) {
			$propertyList = new SERIA_PropertyList($this);
			return $propertyList->get($rightName);
		}
		
		// Help functions used by SERIA_Form to create forms related to SERIA_User objects
		public static function getFieldSpec() {
			return array(
				'first_name' => array(
					'caption' => _t('First name'),
					'fieldtype' => 'text',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 50))),
					'helptext' => _t('Please enter your first name')
				),
				'last_name' => array(
					'caption' => _t('Last name'),
					'fieldtype' => 'text',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 50))),
					'helptext' => _t('Please enter your last name')
				),
				'display_name' => array(
					'caption' => _t('Display name'),
					'fieldtype' => 'text',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 50))),
					'helptext' => _t('Please enter your display name')
				),
				'username' => array(
					'caption' => _t('Username'),
					'fieldtype' => 'text',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 50))),
					'helptext' => _t('Please enter your username')
				),
				'password' => array(
					'caption' => _t('Password'),
					'fieldtype' => 'password',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 50))),
					'helptext' => _t('Please enter your password')
				),
				'email' => array(
					'caption' => _t('Email'),
					'fieldtype' => 'text',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::EMAIL))),
					'helptext' => _t('Please enter your email')
				),
				'is_administrator' => array(
					'caption' => _t('Administrator account'),
					'fieldtype' => 'checkbox',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::CALLBACK, array('SERIA_User','validateUserLevel'))
					))
				),
				'is_guest' => array(
					'caption' => _t('Guest account'),
					'fieldtype' => 'checkbox',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::CALLBACK, array('SERIA_User','validateUserLevel'))
					))
				),
			);
		}


		// interface SERIA_IFluentObject
		public static function fluentSpec() { 
			$res = array('table' => '{users}', 'primaryKey' => 'id'); 
			return $res;
		}

		public static function validateUserLevel($value, $arr)
		{
			// We have post data (trying to save user)
			$object = $arr['object'];
			$field = $arr['field'];

			if($field == 'is_guest')
				if(isset($_POST["is_administrator"]))
					return 'Cannot be administrator and guest at the same time, user was not saved.';
			if($field == 'is_administrator')
				if(isset($_POST["is_guest"]))
					return 'Cannot be administrator and guest at the same time, user was not saved.';
			else
				return false;
		}

		public function toDB() { return $this->_row; }

		public static function fromDB($row) { return SERIA_User::createObject($row); }

		public function isDeletable() {
			SERIA_ProxyServer::privateCache();
			if(empty($this->row['id']))
				return false; // can't delete an object that is not stored
			if(!(SERIA_Base::isElevated() || SERIA_Base::isAdministrator()))
				return false; // access denied
			if(SERIA_Base::user() && SERIA_Base::user()->get('id') == $this->row['id'])
				return false; // can't delete self
			return true;
		}

		/**
		 * This is for name=value pairs that describes this user, and that
		 * should be replicated on single signon and distributed authentication
		 * systems when possible. Security critical information should not be
		 * placed here unless a risk analysis has been performed and consequences
		 * of replication has been understood and handled.
		 *
		 * @param string $name
		 * @param string $value
		 */
		public function setMeta($name, $value)
		{
			SERIA_Base::db()->updateOrInsert('{user_meta_value}', array(
				'name',
				'owner'
			), array(
				'name',
				'owner',
				'value',
				'timestamp'
			), array(
				'name' => $name,
				'owner' => $this->get('id'),
				'value' => $value,
				'timestamp' => date('Y-m-d H:i:s')
			));
			SERIA_Hooks::dispatch('SERIA_User::setMeta', $this, $name, $value);
		}
		/**
		 * This method can be used for synchronizing meta-data across servers. Not for
		 * regular use.
		 *
		 * @param string $name
		 * @param string $value
		 * @param integer $timestamp
		 */
		public function _setMetaExtended($name, $value, $timestamp)
		{
			SERIA_ProxyServer::noCache();
			SERIA_Base::db()->updateOrInsert('{user_meta_value}', array(
				'name',
				'owner'
			), array(
				'name',
				'owner',
				'value',
				'timestamp'
			), array(
				'name' => $name,
				'owner' => $this->get('id'),
				'value' => $value,
				'timestamp' => date('Y-m-d H:i:s', $timestamp)
			));
		}
		/**
		 * This reads meta a name=value pair based on name. This returns both the value and timestamp
		 * in an associative array. array('value' => value, 'timestamp' => unixTimestamp). Returns false
		 * on failure.
		 *
		 * @param string $name
		 * @return array
		 */
		public function getMetaExtended($name)
		{
			$q = SERIA_Base::db()->query('SELECT value, timestamp FROM {user_meta_value} WHERE name = :name AND owner = :owner', array(
				'name' => $name,
				'owner' => $this->get('id')
			))->fetch(PDO::FETCH_NUM);
			if ($q) {
				$locale = SERIA_Locale::getLocale();
				return array('value' => $q[0], 'timestamp' => $locale->sqlToTime($q[1]));
			} else
				return false;
		}
		/**
		 * Reads all meta name=value pairs with timestamps for this user. array('name' => name, 'value' => value, 'timestamp' => timestamp).
		 *
		 * @return array
		 */
		public function getAllMetaExtended()
		{
			$q = SERIA_Base::db()->query('SELECT name, value, timestamp FROM {user_meta_value} WHERE owner = :owner', array(
				'owner' => $this->get('id')
			))->fetchAll(PDO::FETCH_NUM);
			$locale = SERIA_Locale::getLocale();
			$a = array();
			foreach ($q as $r) {
				$a[] = array(
					'name' => $r[0],
					'value' => $r[1],
					'timestamp' => $locale->sqlToTime($r[2])
				);
			}
			return $a;
		}
		/**
		 * Reads one name=value pair for this user. Returns the value or false on error.
		 *
		 * @param string $name
		 * @return string
		 */
		public function getMeta($name)
		{
			$q = SERIA_Base::db()->query('SELECT value FROM {user_meta_value} WHERE name = :name AND owner = :owner', array(
				'name' => $name,
				'owner' => $this->get('id')
			))->fetch(PDO::FETCH_NUM);
			if ($q)
				return $q[0];
			else
				return false;
		}

		/**
		 * Should redirect to a public logon page. Return should guarantee a valid guest account login.
		 *
		 * @return unknown_type
		 */
		public static function requireGuestAccount()
		{
			SERIA_ProxyServer::privateCache();
			if (SERIA_Base::user() !== false)
				return;
			if (file_exists(SERIA_ROOT.'/login.php')) {
				$url = new SERIA_Url(SERIA_HTTP_ROOT.'/login.php');
				$url->setParam('continue', SERIA_Url::current()->__toString());
				$url = $url->__toString();
				header('Location: '.$url);
				die();
			} else
				SERIA_Base::pageRequires('login');
		}

		/**
		*	Interface SERIA_IMetaField
		*/
		public static function createFromUser($value)
		{
			try {
				return self::createObject($value);
			}
			catch (SERIA_Exception $e)
			{
				return NULL;
			}
		}

		public static function createFromDb($value)
		{
			return self::createObject($value);
		}

		public function toDbFieldValue()
		{
			return $this->getKey();
		}

		public static function MetaField()
		{
			return array(
				'type' => 'integer',
				'class' => 'SERIA_User',
			);
		}

		public static function renderFormField($fieldName, $value, array $params=NULL, $hasError=false)
		{
			if($value!==NULL)
				$value = $value->getKey();

/**
                          'id' => $this->_prefix.$name,
                                'name' => $this->_prefix.$name,
                                'class' => 'select'.($this->hasError($name)?' ui-state-error':''),
*/
			$r = '<select id="'.$fieldName.'" class="select'.($hasError?' ui-state-error':'').'" name="'.$fieldName.'"><option></option>';
			$users = SERIA_Fluent::all('SERIA_User');

			foreach($users as $user)
				$r.= '<option value="'.$user->get("id").'"'.($user->getKey()===$value?' selected="selected"':'').'>'.$user->get("display_name").'</option>';

			$r .= '</select>';

			return $r;
		}

	}

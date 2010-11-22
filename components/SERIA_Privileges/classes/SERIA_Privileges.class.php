<?php
	/**
	*	Usage:
	*
	*	All privileges must be registered before you use them. The way you register them is by adding a callback:
	*
	*	SERIA_Hooks::listen(SERIA_PrivilegesHooks::EMBED, 'my_callback');
	*
	*	function my_callback() {
	*		SERIA_Privileges::registerObjectPrivileges('ClassName', array(
	*			'edit' => _t("Edit"),
	*			'delete' => _t("Delete"),
	*			'approve' => _t("Approve"),
	*			'publish' => _t("Publish"),
	*		));
	*		SERIA_Privileges::registerApplicationPrivileges('my_application_id', _t("Name of my application"), array(
	*			'access' => _t("Access"),
	*			'edit_any_article' => _t("Edit all articles"),
	*			'delete_any_article' => _t("Delete any article"),
	*			'publish_any_article' => _t("Publish any article"),
	*		));
	*	}
	*
	*
	*	Checking for privileges in an application:
	*
	*	$p = new SERIA_Privileges('my_application_id'[, $anotherUser]);
	*
	*	Checking for privileges in an object (object must implement SERIA_NamedObject)
	*
	*	$p = new SERIA_Privileges($object[, $anotherUser]);
	*
	*	Check for privilege:
	*	$p->hasPrivilege('access'); // true or false
	*
	*	Give privilege:
	*	$p->grantPrivilege('access'); // true if successful, false if user already had the privilege
	*
	*	Take away privilege:
	*	$p->revokePrivilege('access'); // true if successful, false if user did not have the privilege from before
	*/
	class SERIA_Privileges
	{
		/**
		*	This instance of SERIA_Privileges applies to
		*/
		protected $_namespace, $_object, $_userId, $_user;
		protected static $_applicationPrivileges = array();
		protected static $_delayedApplicationPrivileges = array();
		protected static $_objectPrivileges = array();
		protected static $_privilegeCache = array();
		protected static $_db;

		function __construct($appNameOrObject, $user=NULL)
		{
			if(is_object($appNameOrObject))
			{
				$this->_object = $appNameOrObject;
				$this->_namespace = serialize($appNameOrObject->getObjectId());
			}
			else
			{
				$this->_namespace = ''.$appNameOrObject; // Concat empty string so that it fails if non-scalar values are passed
			}

			if($user) $this->_user = $user;
			else $this->_user = SERIA_Base::user();

			if($this->_user) $this->_userId = $this->_user->get('id');
			else $this->_userId = NULL;

			if(!isset($this->_privilegeCache[$this->_userId]))
				$this->_privilegeCache[$this->_userId] = array($this->_namespace => array());
			else if(!isset($this->_privilegeCache[$this->_userId][$this->_namespace]))
				$this->_privilegeCache[$this->_userId][$this->_namespace] = array();


		}

		public static function getApplicationPrivileges()
		{
			self::_registerPrivileges();
			return self::$_applicationPrivileges;
		}

		protected static function _registerPrivileges()
		{
			static $registered = false;
			if(!$registered)
				SERIA_Hooks::dispatch(SERIA_PrivilegesHooks::EMBED);
			$registered = true;
		}

		public function hasPrivilege($privilegeName)
		{
			self::_registerPrivileges();
			$this->_validatePrivilege($privilegeName); // throws SERIA_Exception('No such privilege');

			if(!$this->_userId) return false;

			if($this->_user->isAdministrator()) return true;

			if(isset(self::$_privilegeCache[$this->_userId]) && isset(self::$_privilegeCache[$this->_userId][$this->_namespace]) && isset(self::$_privilegeCache[$this->_userId][$this->_namespace][$privilegeName]))
			{ // cached
				return self::$_privilegeCache[$this->_userId][$this->_namespace][$privilegeName];
			}
			else
			{ // not cached
				if(self::_query('SELECT 1 FROM {privileges} WHERE namespace=? AND name=? AND user=?', array($this->_namespace, $privilegeName, $this->_userId))->fetch(PDO::FETCH_COLUMN))
					return $this->_privilegeCache[$this->_userId][$this->_namespace][$privilegeName] = true;

				return $this->_privilegeCache[$this->_userId][$this->_namespace][$privilegeName] = false;
			}		
		}

		private function _validatePrivilege($privilegeName)
		{
			if(isset(self::$_object))
			{
				if(!isset(self::$_objectPrivileges[$this->_namespace]))
					throw new SERIA_Exception('No privileges registered for class '.$this->_namespace.' registered.', SERIA_Exception::NOT_FOUND);
				if(!isset(self::$_objectPrivileges[$this->_namespace][$privilegeName]))
					throw new SERIA_Exception('No such privilege ('.$privilegeName.') for class '.$this->_namespace.' registered.', SERIA_Exception::NOT_FOUND);
			}
			else
			{
				if(!isset(self::$_applicationPrivileges[$this->_namespace]))
					throw new SERIA_Exception('No privileges registered for application '.$this->_namespace.' registered.', SERIA_Exception::NOT_FOUND);
				if(!isset(self::$_applicationPrivileges[$this->_namespace]['privileges'][$privilegeName]))
					throw new SERIA_Exception('No such privilege ('.$privilegeName.') for class '.$this->_namespace.' registered.', SERIA_Exception::NOT_FOUND);
			}
		}

		public function grantPrivilege($privilegeName)
		{
			self::_registerPrivileges();
			$this->_validatePrivilege($privilegeName); // throws SERIA_Exception('No such privilege');

			$privileges = new SERIA_Privileges('SERIA_Privileges');
			if(!$privileges->hasPrivilege('modify'))
				return false;

			if(!$this->_userId) return false;
			try {
				if(self::_exec('INSERT INTO {privileges} (namespace, name, user) VALUES (?, ?, ?)', array($this->_namespace, $privilegeName, $this->_userId)))
				{
					$this->_privilegeCache[$this->_userId][$this->_namespace][$privilegeName] = true;
					return true;
				}
			} catch (PDOException $e) {
				if($e->getCode()=='23000') // duplicate key
					return false;
				throw $e;
			}
			return false;
		}

		public function revokePrivilege($privilegeName)
		{
			self::_registerPrivileges();
			$this->_validatePrivilege($privilegeName); // throws SERIA_Exception('No such privilege');

			$privileges = new SERIA_Privileges('SERIA_Privileges');
			if(!$privileges->hasPrivilege('modify'))
				return false;

			if(!$this->_userId) return name;
			if(self::_exec('DELETE FROM {privileges} WHERE namespace=? AND name=? AND user=?', array($this->_namespace, $privilegeName, $this->_userId)))
			{
				$this->_privilegeCache[$this->_userId][$this->_namespace][$privilegeName] = false;
				return true;
			}
			return false;
		}

		public function getUsersHaving($privilegeName)
		{
			$users = SERIA_Fluent::all('SERIA_User');
			$idRS = self::_query('SELECT user FROM {privileges} WHERE namespace=? AND name=?', array($this->_namespace, $privilegeName));
			$ids = array();
			while($id = $idRS->fetch(PDO::FETCH_COLUMN, 0))
				$ids[] = $id;
			if(sizeof($ids)===0)
				$users->where('1=0'); // no users should be returned
			else
				$users->where('id IN ('.implode(',',$ids).')');
			return $users;
		}

		public function getUsersNotHaving($privilegeName)
		{
			$users = SERIA_Fluent::all('SERIA_User');
			$idRS = self::_query('SELECT user FROM {privileges} WHERE namespace=? AND name=?', array($this->_namespace, $privilegeName));
			$ids = array();
			while($id = $idRS->fetch(PDO::FETCH_COLUMN, 0))
				$ids[] = $id;
			if(sizeof($ids)!==0)
				$users->where('id NOT IN ('.implode(',',$ids).')');
			return $users;
		}

		public function grantPrivilegeAction($privilegeName, $user=NULL)
		{
			$action = new SERIA_ActionUrl('grant_privilege', $user->get('id').'-'.$privilegeName);
			if($action->invoked())
			{
				if($user!==NULL)
				{
					if($this->_object)
					{
						$p = new SERIA_Privileges($this->_object, $user);
					}
					else
					{
						$p = new SERIA_Privileges($this->_namespace, $user);
					}
				}
				else $p = $this;

				$p->grantPrivilege($privilegeName);
				$action->success = true;
			}
			return $action;
		}

		public function revokePrivilegeAction($privilegeName, $user=NULL)
		{
			$action = new SERIA_ActionUrl('revoke_privilege', $user->get('id').'-'.$privilegeName);
			if($action->invoked())
			{
				if($user!==NULL)
				{
					if($this->_object)
						$p = new SERIA_Privileges($this->_object, $user);
					else
						$p = new SERIA_Privileges($this->_namespace, $user);
				}
				else $p = $this;

				$p->revokePrivilege($privilegeName);
				$action->success = true;
			}
			return $action;
		}

		protected static function _query($sql, array $values=NULL)
		{
			if(!self::$_db) self::$_db = SERIA_Base::db();
			try {
				return self::$_db->query($sql, $values, true);
			} catch (PDOException $e) {
				if($e->getCode()=='42S02')
				{
					self::_install();
					return self::$_db->query($sql, $values, true);
				}
				throw $e;
			}
		}

		protected static function _exec($sql, array $values=NULL)
		{
			if(!self::$_db) self::$_db = SERIA_Base::db();
			try {
				return self::$_db->exec($sql, $values, true);
			} catch (PDOException $e) {
				if($e->getCode()=='42S02')
				{
					self::_install();
					return self::$_db->exec($sql, $values, true);
				}
				throw $e;
			}
			
		}

		protected static function _install()
		{
			if(!self::$_db) self::$_db = SERIA_Base::db();
			return self::$_db->exec("CREATE TABLE {privileges} (namespace VARCHAR(50), name VARCHAR(50), user INTEGER, PRIMARY KEY(user,namespace,name))");
		}

		public static function registerApplicationPrivileges($applicationId, $displayName, array $privileges)
		{
			if(!isset(self::$_applicationPrivileges[$applicationId]))
			{
				if(isset(self::$_delayedApplicationPrivileges[$applicationId]))
					$privileges = array_merge($privileges, self::$_delayedApplicationPrivileges[$applicationId]);

				self::$_applicationPrivileges[$applicationId] = array(
					'displayName' => $displayName,
					'privileges' => $privileges,
				);
			}
			else
			{
				throw new SERIA_Exception('The application privileges have already been registered. Use SERIA_Privileges::extendApplicationPrivileges($applicationId, array $privileges) to extend the rights.');
			}
		}

		public static function extendApplicationPrivileges($applicationId, array $privileges)
		{
			if(isset(self::$_applicationPrivileges[$applicationId]))
				self::$_applicationPrivileges[$applicationId]['privileges'] = array_merge(self::$_applicationPrivileges[$applicationId]['privileges'], $privileges);
			else if(isset(self::$_delayedApplicationPrivileges[$applicationId]))
				self::$_delayedApplicationPrivileges[$applicationId] = array_merge(self::$_delayedApplicationPrivileges[$applicationId], $privileges);
			else
				self::$_delayedApplicationPrivileges[$applicationId] = $privileges;
		}

		public static function registerObjectPrivileges($className, array $privileges)
		{
			if(isset(self::$_objectPrivileges[$className]))
				self::$_objectPrivileges[$className] = array_merge(self::$_objectPrivileges[$className], $privileges);
			else
				self::$_objectPrivileges[$className] = $privileges;
		}
	}

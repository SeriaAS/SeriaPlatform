<?php
	/**
	*	Usage examples:
	*
	*	An ActionUrl object simply an informational object that is passed between system code and design code, very similar to
	*	an event object.
	*
	*	There are two sides to a ActionUrl object. One side is the designers view and the other is the system developers view.
	*	Usage will be different from each side, since the system developer must perform actions while the designer enables actions.
	*
	*
	*	Example: Deleting an instance of a class "CD".
	*
	*	Developer site:
	*	class CD extends SERIA_MetaObject {
	*
	*		const DELETE_ACTION = 'delete_cd';
	*
	*
	*		public function deleteAction()							// this is not a static method, but it could have been
	*		{
	*			$action = new SERIA_ActionURL(self::DELETE_ACTION, $this);
	*			if($action->invoked())
	*			{
	*				SERIA_Meta::delete($this);
	*				$action->success = true;
	*			}
	*			return $action;
	*		}
	*
	*		public function staticDeleteAction(CD $instance)				// this is the same method in a static version
	*		{
	*			$action = new SERIA_ActionURL(self::DELETE_ACTION, $instance);
	*			if($action->invoked())
	*			{
	*				SERIA_Meta::delete($this);
	*				$action->success = true;
	*			}
	*			return $action
	*		}
	*	}
	*
	*	function functionInGlobalNameSpace_delete_row($table, $primaryKey)
	*	{
	*		$action = new SERIA_ActionURL('functionInGlobalNameSpace_delete_row', array($table, $primaryKey));
	*		if($action->invoked())
	*		{
	*			$action->success = SERIA_Base::db()->exec('DELETE FROM '.$table.' WHERE id='.$primaryKey);
	*		}
	*		return $action;
	*	}
	*/
	class SERIA_ActionUrl
	{
		/**
		*	Informs the view if the action was performed (for example a user was deleted)
		*/
		public $success = false;	
		/**
		*	Informs the view if the action was attempted, but failed (for example if the current user does not have access to delete users)
		*/
		public $error = false;

		/**
		*	The name of the GET field to be used. Should be unique for the action.
		*/
		protected $_name;
		/**
		*	Data that is passed when the user is deleted. This data must match an action object instance on the next page load.
		*/
		protected $_data = array();

		/**
		*	Meta data that is persisted from when the user invoked the action. This could perhaps be a time stamp for when the page was generated, the current URL or similar.
		*/
		protected $_state = NULL;

		/**
		*	Create an ActionUrl object that can be linked to. If invoked, this objects ->invoked()-method will return true.
		*	@param string $name		The name of the GET parameter to use.
		*	@param mixed $data		The data that uniquely identifies this action among other actions with the same name
		*	@param mixed $state		State information, such as the current time or the current request url (which is changed by the clicking of this url)
		*/
		function __construct($name, $data = NULL, $state = NULL)
		{
			$this->_state = $state;
			$this->_name = $name;
			if(is_null($data))
			{
				$this->_data = '1';
			}
			else if(is_scalar($data))
			{
				$this->_data = $data;
			}
			else if(is_array($data)) // TODO: this can be replaced with non destructive storage to allow more intelligent use of the URL data
			{
				$this->_data = md5(serialize($data));
			}
			else if(is_object($data))
			{
				if($data instanceof SERIA_MetaObject)
					$this->_data = SERIA_Meta::getReference($data);
				else if($data instanceof SERIA_FluentObject || in_array('SERIA_IFluentObject', $classList = class_implements($data)))
					$this->_data = get_class($data).":".$data->getKey();
				else if(in_array('SERIA_NamedObject', $classList))
					$this->_data = md5(serialize($data->getObjectId()));
				else
					throw new SERIA_Exception('I do not know how to identify this object. Must extend SERIA_MetaObject or SERIA_FluentObject, or implement SERIA_IFluentObject or SERIA_NamedObject.');
			}
			else
				throw new SERIA_Exception('I do not know how to serialize the provided data for use in an URL.');
		}

		/**
		*	Return true if this URL object have been invoked
		*	@return boolean
		*/
		function invoked()
		{
			if((isset($_GET[$this->_name]) && $_GET[$this->_name] == $this->_data))
			{
				if(isset($_GET[$this->_name.'-s']))
					$this->_state = unserialize($_GET[$this->_name.'-s']);
				else
					$this->_state = NULL;
				return true;
			}
			return false;
		}

		/**
		*	Return an URL that will invoke this SERIA_ActionUrl object.
		*	@return string
		*/
		public function __toString()
		{
			$url = SERIA_Url::current()->setParam($this->_name, $this->_data);

			if($this->_state) $url->setParam($this->_name.'-s', serialize($this->_state));

			return $url->__toString();
		}

		/**
		*	Removes the query param from the provided url, and returns the url.
		*	@param SERIA_Url $url
		*	@return SERIA_Url
		*/
		public function removeFromUrl(SERIA_Url $url)
		{
			return $url->unsetParam($this->_name);
		}

		/**
		 *
		 * Get state information sent with the invokation of this action.
		 * @return mixed State information sent with the original construction of this action.
		 */
		public function getState()
		{
			return $this->_state;
		}
	}

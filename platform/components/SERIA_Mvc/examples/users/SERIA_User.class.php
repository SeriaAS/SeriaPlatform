<?php
	class SERIA_User extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{users}',
				'fields' => array(
					'name' => array('name required', _t('Name')),
					'username' => array('username required', _t('Username')),
					'password' => array('password required', _t('Password')),
				),
			);
		}

		// a non-static action that is tied to the instance of this user
		public function editAction()
		{
			$form = new SERIA_ActionForm($this);
			if($form->hasData())
			{
				if($form->validate())
				{
					$form->save();
					$form->success = true;
				}
			}
			return $form;
		}

		public static function loginAction()
		{
			// login requires user input, thus we create a form based on the spec for username and password of the SERIA_User MetaObject
			$form = new SERIA_ActionForm('SERIA_User', array('username','password'));

			// check if the form has data, and handle it appropriately
			if($form->hasData())
			{
				// search for the user object matching the username and password
				$user = SERIA_Meta::all('SERIA_User')->where('username=:username AND password=:password', $form)->current();

				// if the user was found, handle it appropriately
				if($user)
				{
					// update the session
					$_SESSION['userId'] = $user->get('id');

					// could be useful to return the user
					$form->user = $user;
					$form->success = true;
				}
			}
			return $action;
		}

		public static function logoutAction()
		{
			$action = new SERIA_ActionURL(array('SERIA_User','logoutAction'));
			if($action->hasData())
			{
				// update the session
				unset($_SESSION['userId']);

				// could be useful to tell the view that a user was in fact logged out
				$action->loggedOut = true;
			}
			return $action;
		}
	}

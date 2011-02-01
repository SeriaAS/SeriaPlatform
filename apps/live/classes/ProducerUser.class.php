<?php
	/**
	* ProducerUser
	* SERIA_User user
	* Producer producer
	*
	* Creates a link between a SERIA_User and a Producer
	* (Ie: connection between Ola Nordmann and his employer "Big Media Inc" which is the producer)
	*
	* a ProducerUser has access to all presentations connected to the Producer. If a SERIA_User changes
	* the production company he is assosciated to, his list of available presentations will change thereafter.
	*
	*/

	class ProducerUser extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{producersusers}',
				'displayName' => 'user',
				'fields' => array(
					'producer' => array('Producer required', _t('Production company')),
					'user' => array('SERIA_User required unique', _t('User')),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function getProducer() {
			return $this->get("producer");
		}

		public function getUser()
		{
			return $this->get('user');
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_produceruser', $this, array(
				'producer',
				'user'
			));

			return $form;
		}

		public static function getProducerUserByUser(SERIA_User $user)
		{
			$query = SERIA_Meta::all('ProducerUser')->where('user='.$user->get("id"));

			return $query->current();
		}

		public static function getCurrent()
		{
			$user = SERIA_Base::user();
			if($user)
				return self::getProducerUserByUser($user);
			throw new SERIA_Exception('No current producer user');
		}
	}

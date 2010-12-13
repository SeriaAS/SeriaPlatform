<?php
	class SERIA_DateTimeMetaField implements SERIA_IMetaField
	{
		protected $_dateTime;

		/**
		*	Create a datetime object for SERIA_Meta
		*	@param mixed $timestamp		Unix timestamp or SERIA_DateTime object
		*/
		function __construct($timestamp)
		{
			if(is_object($timestamp))
				$this->_dateTime = $timestamp;
			else {
				$this->_dateTime = new SERIA_DateTime($timestamp);
			}
		}

		/**
		 *
		 * Get the SERIA_DateTime object.
		 * @return SERIA_DateTime
		 */
		public function getDateTimeObject()
		{
			return $this->_dateTime;
		}

		public function renderFormField($name, array $params=NULL)
		{
			$value = $this;
			$formName = /*$prefix.*/$name; /* FIXME - We don't know the prefix. Assuming empty */
			SERIA_ScriptLoader::loadScript('jQuery-ui');
			$id = $name.mt_rand();
			ob_start();
			if (is_object($value) && is_a($value, 'SERIA_DateTimeMetaField')) {
				$timestamp = false;
				?>
					<input type='text' id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($formName); ?>" value="<?php echo htmlspecialchars($value->getDateTimeObject()->render('Y-m-d H:i:s')); ?>" />
					<script type='text/javascript'>
						<!--
							document.getElementById(<?php echo SERIA_Lib::toJSON($id); ?>).style.display = 'none';
						-->
					</script>
				<?php
			} else {
				$timestamp = true;
				?>
					<input type='hidden' id="<?php echo htmlspecialchars($id); ?>" name="<?php echo htmlspecialchars($formName); ?>" value="<?php echo htmlspecialchars($value); ?>" />
				<?php
			}
			?>
				<script type='text/javascript'>
					<!--
						(function () {
							/*
							 * Datepicker:
							 */
							var dataObj = document.getElementById(<?php echo SERIA_Lib::toJSON($id); ?>);
							var visualObj = document.createElement('input');
							visualObj.id = <?php echo SERIA_Lib::toJSON($name); ?>;
							visualObj.type = 'text';
							visualObj.className = 'datetime-datepicker';
							dataObj.parentNode.insertBefore(visualObj, dataObj);
							$(visualObj).datepicker();

							/*
							 * Time selectors:
							 */
							var hours = document.createElement('select');
							var minutes = document.createElement('select');
							var seconds = document.createElement('select');

							hours.title = <?php echo SERIA_Lib::toJSON(_t('Hour')); ?>;
							minutes.title = <?php echo SERIA_Lib::toJSON(_t('Minute')); ?>;
							seconds.title = <?php echo SERIA_Lib::toJSON(_t('Second')); ?>;

							for (var i = 0; i < 24; i++) {
								twoDigit = i;
								if (twoDigit < 10)
									twoDigit = '0' + twoDigit;
								var h = document.createElement('option');
								h.value = i;
								h.innerHTML = twoDigit;
								hours.appendChild(h);
								var m = document.createElement('option');
								m.value = i;
								m.innerHTML = twoDigit;
								minutes.appendChild(m);
								var s = document.createElement('option');
								s.value = i;
								s.innerHTML = twoDigit;
								seconds.appendChild(s);
							}
							for (var i = 24; i < 60; i++) {
								var m = document.createElement('option');
								m.value = i;
								m.innerHTML = i;
								minutes.appendChild(m);
								var s = document.createElement('option');
								s.value = i;
								s.innerHTML = i;
								seconds.appendChild(s);
							}
							dataObj.parentNode.insertBefore(hours, dataObj);
							dataObj.parentNode.insertBefore(minutes, dataObj);
							dataObj.parentNode.insertBefore(seconds, dataObj);

							if (dataObj.value != '') {
								<?php
									if ($timestamp) {
										?>
											var milliseconds = parseInt(dataObj.value) * 1000;
											var date = new Date(milliseconds);

											$(visualObj).datepicker('setDate', date);
											hours.value = date.getHours();
											minutes.value = date.getMinutes();
											seconds.value = date.getSeconds();
										<?php
									} else {
										?>
											var milliseconds = parseInt(<?php echo SERIA_Lib::toJSON($value->getDateTimeObject()->getTimestamp()); ?>) * 1000;
											var date = new Date(milliseconds);

											hours.value = date.getHours();
											minutes.value = date.getMinutes();
											seconds.value = date.getSeconds();
											$(visualObj).datepicker('setDate', date);
										<?php
									}
								?>
							}

							var update = function () {
								var date = $(visualObj).datepicker('getDate');
								date.setHours(
									parseInt(hours.value),
									parseInt(minutes.value),
									parseInt(seconds.value),
									0
								);
								<?php
									if ($timestamp) {
										?>
											var unixTimestamp = date.getTime() / 1000;
											dataObj.setAttribute('value', unixTimestamp);
										<?php
									} else {
										/* Iso date format */
										?>
											var twoDigits = function (input) {
												if (input >= 10)
													return input;
												else
													return '0' + input;
											}
											var dy = date.getFullYear();
											var dm = twoDigits(date.getMonth() + 1);
											var dd = twoDigits(date.getDate());
											var th = twoDigits(date.getHours());
											var tm = twoDigits(date.getMinutes());
											var ts = twoDigits(date.getSeconds());

											var isoDate = dy + '-' + dm + '-' + dd + ' ' + th + ':' + tm + ':' + ts;

											dataObj.setAttribute('value', isoDate);
										<?php
									}
								?>
							}
							visualObj.onchange = function () {
								update();
							}
							hours.onchange = function () {
								update();
							}
							minutes.onchange = function () {
								update();
							}
							seconds.onchange = function () {
								update();
							}
						})();
					-->
				</script>
			<?php
			return ob_get_clean();
		}

		public static function createFromUser($value)
		{
			return new SERIA_DateTimeMetaField(SERIA_DateTime::parseUserDateTime($value));
		}

		public static function createFromDb($value)
		{
			return new SERIA_DateTimeMetaField(strtotime($value));
		}

		public function __toString()
		{
			return $this->_dateTime->renderUserDateTime();
		}

		public function toDb()
		{
			return $this->_dateTime->render('Y-m-d H:i:s');
		}

		public static function MetaField()
		{
			return array(
				'type' => 'datetime',
				'validator' => new SERIA_Validator(array()),
				"class" => 'SERIA_DateTimeMetaField',
			);
		}
	}

<?php
	/**
	* This class adds hook functionality to Seria Platform. Hooks are used to extend the functionality of 
	* applications and the platform iself. Hooks can change the data it receives in its arguments, and
	* also may return data to the hook dispatcher.
	* 
	* DEBUGING:
	*	If you are unable to pass values by reference, insert $null= before your value:
	*		SERIA_Hooks::dispatch('my_hook', $null='my_value');
	*/
	class SERIA_Hooks
	{
		private static $listeners = array();
		private static $depth = 0; // record the depth of recursive hooks, to avoid infinite loops

		static function dispatch($name, &$p1=false, &$p2=false, &$p3=false, &$p4=false, &$p5=false, &$p6=false, &$p7=false, &$p8=false, &$p9=false, &$p10=false, &$p11=false, &$p12=false)
		{
			global $seria_options;
			if(SERIA_DEBUG) seria_debugger_notice("Hook: ".$name);
			if(self::$depth++ > 50) throw new Exception('SERIA_Hooks depth of 50 reached.');
			if(SERIA_DEBUG) SERIA_Base::debug('SERIA_Hooks:dispatch('.$name.')');

			if(!isset(self::$listeners[$name]) && !isset($seria_options['hooks'][$name]))
			{
				self::$depth--;
				return array();
			}

			// For support of early added hooks, required when listening to hooks issued before components and applications are loaded.
			if(isset($seria_options['hooks'][$name])) {// merge the arrays and unset $seria_options['hooks'][$name]
				foreach ($seria_options['hooks'] as $name => $hook) {
					self::listen($name, (isset($hook['callback']) ? $hook['callback'] : $hook), (isset($hook['callback']) && isset($hook['weight']) ? $hook['weight'] : 0));
				}
				unset($seria_options['hooks']);
			}

			usort(self::$listeners[$name], array('SERIA_Hooks','sortByWeight'));

			$results = array();
			foreach(self::$listeners[$name] as $listener)
			{
				/**
				*	We are not using call_user_func because we want to support by reference arguments
				*/
				if(SERIA_DEBUG && !is_callable($listener['callback']))
				{
					SERIA_Base::debug('<strong>Illegal callback ('.serialize($listener['callback']).') for hook ('.$name.')</strong>');
				}
				else
				{
					$result = call_user_func($listener['callback'], $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);
					$results[] = $result;
				}
/*
				if(is_array($listener['callback']))
				{
					if(is_object($listener['callback'][0]))
					{
						$o = $listener['callback'][0];
						$m = $listener['callback'][1];
						$res = $o->$m($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12); // funker dette?
						if($res!==NULL) $results[] = $res;
					}
					else
					{
						$c = $listener['callback'][0];
						$m = $listener['callback'][1];

						//TODO: Use closures when PHP 5.3 is the standard
						$code = 'return '.$c.'::'.$m.'($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);';

						$func = create_function('&$p1,&$p2,&$p3,&$p4, &$p5, &$p6, &$p7, &$p8, &$p9, &$p10, &$p11, &$p12', $code);
						$res = $func($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12); // funker dette?
						if($res!==NULL) $results[] = $res;
					}
				}
				else
				{ // OK, by reference (?)
					$func = $listener['callback'];
					$res = $func($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);
					if($res!==NULL) $results[] = $res;
				}
*/
			}
			self::$depth--;
			return $results;
		}

		static function sortByWeight($a, $b)
		{
			if($a['weight'] == $b['weight']) return 0;
			if($a['weight'] < $b['weight']) return -1;
			return 1;
		}

		/**
		*	Will invoke all event listeners in order, sending the result of the first as input to
		*	the next. If you have listeners A and B, and the value $data then the sequence will be called
		*	$data = A($data);
		*	$data = B($data);
		*
		*	@param string $name		The hook name
		*	@param mixed $data		The input to the event listeners
		*	@return mixed
		*/
		static function dispatchOrdered($name, $data)
		{
			if(SERIA_DEBUG) SERIA_Base::debug('SERIA_Hooks:dispatchSequence('.$name.')');

			if(!isset(self::$listeners))
				return NULL;

			usort(self::$listeners[$name], array('SERIA_Hooks','sortByWeight'));

			foreach(self::$listeners[$name] as $listener)
			{
				if(SERIA_DEBUG && !is_callable($listener['callback']))
				{
					SERIA_Base::debug('<strong>Illegal callback ('.serialize($listener['callback']).') for hook ('.$name.')</strong>');
				}
				else
				{
					$data = call_user_func($listener['callback'], $data);
				}
			}

			return $data;
		}

		/**
		*	Will stop after the first response and return the result
		*/
		static function dispatchToFirst($name, &$p1=false, &$p2=false, &$p3=false, &$p4=false, &$p5=false, &$p6=false, &$p7=false, &$p8=false, &$p9=false, &$p10=false, &$p11=false, &$p12=false)
		{
			if(SERIA_DEBUG) SERIA_Base::debug('SERIA_Hooks:dispatch('.$name.')');

			if(self::$depth++ > 50) throw new Exception('SERIA_Hooks depth of 50 reached.');
			if(!isset(self::$listeners[$name]))
			{
				self::$depth--;
				return NULL;
			}

			usort(self::$listeners[$name], array('SERIA_Hooks','sortByWeight'));

			foreach(self::$listeners[$name] as $listener)
			{
				if(SERIA_DEBUG && !is_callable($listener['callback']))
				{
					SERIA_Base::debug('<strong>Illegal callback ('.serialize($listener['callback']).') for hook ('.$name.')</strong>');
				}
				else
				{
					$result = call_user_func($listener['callback'], $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);
				}
/*				
				$result = false;
				if(is_array($listener['callback']))
				{
					if(is_object($listener['callback'][0]))
					{
						$o = $listener['callback'][0];
						$m = $listener['callback'][1];
						$result = $o->$m($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12); // funker dette?
					}
					else
					{
						$c = $listener['callback'][0];
						$m = $listener['callback'][1];

						//TODO: Use closures when PHP 5.3 is the standard
						$func = create_function('&$p1,&$p2,&$p3,&$p4, &$p5, &$p6, &$p7, &$p8, &$p9, &$p10, &$p11, &$p12', '
							return '.$c.'::'.$m.'($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);
						');

						$result = $func($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12); // funker dette?
					}
				}
				else
				{ // OK, by reference (?)
					$func = $listener['callback'];
					$result = $func($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12);
				}
*/
				if($result)
				{
					self::$depth--;
					return $result;
				}
			}
			self::$depth--;
			return NULL;
		}

		static function listen($name, $callback, $weight=0)
		{
			if(!isset(self::$listeners[$name]))
				self::$listeners[$name] = array();

			if(SERIA_DEBUG) SERIA_Base::debug('SERIA_Hooks:listen('.$name.','.serialize($callback).')');
			$package = array('callback' => $callback, 'weight' => $weight);
			self::$listeners[$name][] = $package;
		}
	}

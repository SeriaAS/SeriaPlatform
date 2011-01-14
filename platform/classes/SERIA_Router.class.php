<?php
	/**
	*	Class for handling url requests that are sent trought /index.php?q=some/path
	*
	*	Applications are responsible for adding routes for their requirements to SERIA_Router
	*	during SERIA_RouterHooks::
	*/
	class SERIA_Router
	{
		protected static $_instance = NULL;
		protected $_routes = array();
		protected $_routeMap = array();

		public static function instance()
		{
			if(self::$_instance===NULL)
				self::$_instance = new SERIA_Router('singleton');
			return self::$_instance;
		}

		function __construct($singleton)
		{
			if($singleton!=='singleton') throw new Exception('Use SERIA_Router::instance() to instantiate SERIA_Router.');

			SERIA_Hooks::dispatch(SERIA_PlatformHooks::ROUTER_EMBED, $this);
		}

		/**
		*	Generate a link to a handler: linkTo('SeriaTV') will return http://www.example.com/?q=tv,
		*	and linkTo(array('SERIA_TvPage', 'view'), array('id' => 3231') will return http://www.example.com/?q=tv/play/3231
		*/
		public static function linkTo($application, $page, $variables=array())
		{
			$instance = self::instance();

			if(!isset($instance->_routeMap[$application]) || !isset($instance->_routeMap[$application][$page]))
				throw new SERIA_Exception('Route to "'.$application.':'.$page.'" not found.', SERIA_Exception::NOT_FOUND);

			$route = $instance->_routeMap[$application][$page];

			$find = array();
			$replace = array();
			foreach($variables as $name => $value)
			{
				$find[] = ':'.$name;
				$replace[] = rawurlencode($value);
			}
			$route = str_replace($find, $replace, $route);
			if(strpos($route, ':')!==false)
				throw new SERIA_Exception('All variables not specified in link to "'.$controller.':'.$action.'".');

			$url = SERIA_HTTP_ROOT.'/?route='.$route;
			return new SERIA_Url($url);
		}

		/**
		*	Generate a hidden form field for use within GET-method forms to link to certain pages
		*/
		public static function submitTo($application, $page, $variables=array())
		{
			$instance = self::instance();

			if(!isset($instance->_routeMap[$application]) || !isset($instance->_routeMap[$application][$page]))
				throw new SERIA_Exception('Route to "'.$application.':'.$page.'" not found.', SERIA_Exception::NOT_FOUND);

			$route = $instance->_routeMap[$application][$page];

			$find = array();
			$replace = array();
			foreach($variables as $name => $value)
			{
				$find[] = ':'.$name;
				$replace[] = rawurlencode($value);
			}
			$route = str_replace($find, $replace, $route);
			if(strpos($route, ':')!==false)
				throw new SERIA_Exception('All variables not specified in link to "'.$controller.':'.$action.'".');

			return '<input type="hidden" name="route" value="'.htmlspecialchars($route).'">';;
		}

		/**
		*	Expects a route in a path-like manner; "news" will accept ALL requests to /index.php?q=news and /index.php?q=news/archive
		*	while "news/archive" will accept /index.php?q=news/archive and /index.php?q=news/latest but NOT /index.php?q=news.
		*
		*	If you specify a more advanced route, like "news/:id" it will catch anything matching news/something, and will add
		*	"something" to the params array sent to the callback.
		*
		*	Example							 Request			Returns
		*
		*	$router->addRoute(array('Class', 'method'),'news');	 /index.php?q=news/archive 	does not resolve
		*								 /index.php?q=news 		array();
		*
		*	$router->addRoute(array('Class', 'method'),'news/:id');	 /index.php?q=news 		does not resolve
		*								 /index.php?q=news/archive	array('id' => 'archive');
		*								 /index.php?q=news/archive/	array('id' => 'archive');
		*								 /index.php?q=news/archive/123	does not resolve
		*/
		public function addRoute($application, $page, $callback, $route)
		{
			$depth = sizeof(explode('/', $route=trim($route, '/')));
			if(!isset($this->_routes[$depth]))
			{
				$this->_routes[$depth] = array();
			}

			$this->_routes[$depth][$route] = $callback;

			if(!isset($this->_routeMap[$application]))
				$this->_routeMap[$application] = array();
			$this->_routeMap[$application][$page] = $route;
		}

		/**
		*	Return the handler specified for the route and variables parsed out
		*/
		public function resolve($reqRoute)
		{
			$depth = sizeof($reqParts = explode('/', $route=trim($reqRoute, '/')));
			if(!isset($this->_routes[$depth]))
				throw new SERIA_Exception('Not found', SERIA_Exception::NOT_FOUND);

			$candidates = array();
			$routes = $this->_routes[$depth];
			foreach($routes as $route => $handler)
			{
				$match = true;
				$variables = array();
				$parts = explode('/', $route);
				foreach($parts as $i => $part)
				{
					if($reqParts[$i]===$part)
					{ // this route is still a candidate, exact match
					}
					else if($part[0]===':')
					{ // this route is still a candidate, variable
						$variables[substr($part, 1)] = $reqParts[$i];
					}
					else
					{ // move on, since this route will never match
						$match = false;
						break;
					}
				}
				if($match)
				{
					$candidates[] = array($route, $handler, $variables);
				}
			}
			$num = sizeof($candidates);
			if($num===0)
			{
				throw new SERIA_Exception('"'.$reqRoute.'" not found', SERIA_Exception::NOT_FOUND);
			}
			else if($num===1)
			{
				// returns array($callback, $variables)
				return array($candidates[0][1], $candidates[0][2]);
			}
			else
			{ // we have multiple candidates. Select the candidate with the fewest number of variables
				usort($candidates, array('SERIA_Router','_resolveSorter'));
				$match = array_pop($candidates);
				return array($match[1], $match[2]);
			}
			throw new SERIA_Exception('Not found', SERIA_Exception::NOT_FOUND);
		}

		protected static function _resolveSorter($a, $b)
		{
			$aa = sizeof($a[1]);
			$bb = sizeof($b[1]);
			if($aa == $bb)
			{ // the route with the earliest variable is last
				$aa = strpos($a[0], ':');
				$bb = strpos($b[0], ':');
				if($aa == $bb)
					return 0;
				return
					$aa > $bb ? -1 : 1;
			}
			else return $aa < $bb ? -1 : 1;
		}
	}

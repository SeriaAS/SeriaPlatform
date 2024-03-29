<?php
	// the default response
	$response = NULL;
                /**
                *       Methods related to collections of data
                */
		// /api/[class]?start=0&length=1000
		// public static function apiQuery($start, $length, $filer)

                // public static function getCollectionApi($start=0, $length=1000, $options=NULL); // returns an array with $length items representing table rows, starting at offset $start. $options is an associative arr$
                // public static function putCollectionApi($values, $options=NULL); // overwrite entire collection, return true or false
                // public static functino postCollectionApi($values, $options); // insert a new element to the collection, return the new primary key or throw an exception
                // public static function deleteCollectionApi($options=NULL); // delete the entire collection, return true or throw an exception

                /**
                *       Methods related to a specified element belonging to a collection
                */
                // public static function getElementApi($key, $options=NULL) // returns an array of key=>value pairs
                // public static function putElementApi($key, $values, $options=NULL) // overwrite or create element, return true or false
                // public static function deleteElementApi($key, $options=NULL) // delete an element, return true or throw an exception


	try {
		// check if trying to access an actual meta object

		$size = sizeof($parts);

		if($size==1)
		{
			if(strpos($parts[0], '.')!==false)
			{
				list($class, $format) = explode(".", $parts[0]);
			}
			else
			{
				$class = $parts[0];
				$format = 'html';
			}
		}
		else
			throw new SERIA_Exception("Request URL not understood.", SERIA_Exception::NOT_IMPLEMENTED);

		$responder = 'respond_'.$format;
		if(!function_exists($responder))
		{
			$responder = 'respond_html';
			throw new SERIA_Exception('Format ('.$format.') is not supported', SERIA_Exception::NOT_IMPLEMENTED);
		}
		if(!class_exists($class) || !in_array('SERIA_IApiAccess', class_implements($class)))
			throw new SERIA_Exception($class.' does not exist, or is not accessible trough this API.', SERIA_Exception::NOT_FOUND);

		switch($_SERVER['REQUEST_METHOD'])
		{
			case 'GET' : // retrieving operations, can retrieve tabular data or a single row
				$params = $_GET;
				unset($params['route']);
				if(!isset($params['start'])) $params['start'] = 0;
				if(!isset($params['length'])) $params['length'] = 1000;
				$result = call_user_func(array($class, 'apiQuery'), $params);
				$responder($result);
				break;
			case 'POST' : // retrieving operations, can retrieve tabular data or a single row
				$params = $_POST;
				unset($params['route']);
				if(!isset($params['start'])) $params['start'] = 0;
				if(!isset($params['length'])) $params['length'] = 1000;
				$result = call_user_func(array($class, 'apiQuery'), $params);
				$responder($result);
				break;
			default :
				throw new SERIA_Exception($_SERVER['REQUEST_METHOD'].' request method not implemented.');
				break;
		}
	} catch (SERIA_Exception $e) {
		if($e->getCode()==SERIA_Exception::NOT_IMPLEMENTED)
			throw $e;
		else if($e->getCode()==SERIA_Exception::NOT_FOUND)
			throw new SERIA_Exception('Not handled ('.$e->getMessage().')', SERIA_Exception::NOT_IMPLEMENTED);
		else if($e->getCode()==SERIA_Exception::NOT_IMPLEMENTED)
			header("HTTP/1.0 501 Not Implemented");
		else
			header("HTTP/1.0 500 Unknown error");
		$responder(array('error' => $e->getMessage(), 'type' => get_class($e), 'code' => $e->getCode()));
	} catch (Exception $e) {
		header("HTTP/1.0 500 Unknown error");
		$responder(array('error' => $e->getMessage(), 'type' => get_class($e), 'code' => $e->getCode()));
	}

	function respond_php($data)
	{
		echo serialize($data);
		die();
	}

	function respond_js($data) { respond_json($data); }

	function respond_json($data)
	{
		echo SERIA_Lib::toJSON($data);
	}

	function respond_html($data)
	{
		echo "<html><head><title>HTML formatted</title></head><body>";
		_respond_html_tree($data);
		echo "</body></html>";
	}
	function _respond_html_tree($data)
	{
		if(is_array($data))
		{
			if(sizeof($data))
			{
				echo "<table class='array' style='border: 2px solid #aaa;'>";
				foreach($data as $key => $value)
				{
					echo "<tr><td class='key'>".htmlspecialchars($key)."</td><td><em>".gettype($value)."</td><td class='value' style='border-left:1px solid black;'>";
					_respond_html_tree($value);
					echo "</td></tr>\n";
				}
				echo "</table>";
			}
			else
				echo "<em>Empty array</em>";
		}
		else if(is_scalar($data))
		{
			echo $data;
		}
		else if(is_object($data))
		{
			echo "<span class='object'>".get_class($data)."</span>";
		}
	}

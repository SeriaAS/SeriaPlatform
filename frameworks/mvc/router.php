<?php
	$controller = $_GET['controller'];
	$action = $_GET['action'];
	
	if (!$controller) {
		$controller = 'index';
	}
	if (!$action) {
		$action = 'index';
	}
	
	if (!preg_match('/^[a-z0-9A-Z]+\z/', $controller)) {
		throw new SERIA_Exception('Invalid controller name');
	}
	if (!preg_match('/^[a-z0-9A-Z]+\z/', $action)) {
		throw new SERIA_Exception('Invalid action name');
	}
	
	$controllerClassName = strtolower($controller);
	$controllerClassName[0] = strtoupper($controllerClassName);
	$controllerClassName .= 'Controller';
	
	$controllerPaths = array(SERIA_ROOT . '/controllers/', SERIA_ROOT . '/seria/controllers/');
	
	$controllerPath = '';
	foreach ($controllerPaths as $basePath) {
		$controllerPath = $basePath . $controller . '.php';
		if ($controllerPath) {
			break;
		}
	}
	
	if (!$controllerPath || !file_exists($controllerPath)) {
		throw new SERIA_Exception('Controller file not found');
	}
	
	require($controllerPath);
	if (!class_exists($controllerClassName)) {
		throw new SERIA_Exception('Controller not found inside controller file');
	}
	
	$controller = new $controllerClassName($controller);
	if (!is_subclass_of($controller, 'SERIA_ActiveRecordController')) {
		throw new SERIA_Exception('Controller is not a child of SERIA_ActiveRecordController');
	}
	
	$controller->request($action, $_GET);

<?php
	require_once(dirname(__FILE__).'/../../../main.php');

	try {
	if(SERIA_Base::isLoggedIn()){
			if(!isset($_GET["file_id"]))
				throw new SERIA_Exception('Must provide file_id for information regarding conversion status');
	
			$file_id = $_GET["file_id"];
			$info = PowerpointConverterJobInformation::createFromFileId($file_id);

			$contents = '<Job>
					<Progress>
						'.$info->get('progress').'
					</Progress>
					<Description>
						'.$info->get('description').'
					</Description>
					
				</Job>';
	
		} else {
	
			$contents = '<Job>
					<Progress>
						"Ukjent feil,"
					</Progress>
					<Description>
						" ikke logget inn "
					</Description>
					
				</Job>';
		}
	} catch(SERIA_Exception $e) {
		$contents = '<Job>
				<Progress>
					"Ukjent feil,"
				</Progress>
				<Description>
					'.$e->getMessage().'
				</Description>
				
			</Job>';	
	}
	SERIA_Template::override('text/xml', $contents);

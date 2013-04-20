<?php
class routerController {

	function __construct( ) {

	}

	public function getControllerName( ) {

		if($_GET['R1']=='') {
			header("Status: 301 Moved Permanently");
			header("Location: /hello_world");
			exit;
		}

		$controllerInfo = array(
			'sid'	=>$_GET['R1'],
			'params'=>array()
		);
		
		return $controllerInfo;
	}


}
<?php

class ASAdvancedControllerRouter {

	private $response;

	public function __construct( $controllerInstance, $methodUrlKeyNumber=2 ) {

		if(!isset($controllerInstance->writesToSession) || !$controllerInstance->writesToSession) {
			session_write_close();
		}


		//route the method
		$method = $_GET[ 'R'.$methodUrlKeyNumber ];

		if($method=='') {
			$method = 'view';
		}

		if(method_exists($controllerInstance, $method)) {

			$ref = new ReflectionMethod( $controllerInstance, $method );
			$methodParams = $ref->getParameters();

			$args = [];

			for($i=0, $mpCount=count($methodParams); $i<$mpCount; $i++) {
				if($_GET['R'.($i+($methodUrlKeyNumber+1))]!='') {
					$args[] = $_GET['R'.($i+($methodUrlKeyNumber+1))];
				}
			}

			$this->response = call_user_func_array( [ $controllerInstance, $method ], $args );

		}
		else {
			http_response_code(404);
			exit;
		}
	}

	public function get() {
		return $this->response;
	}

}
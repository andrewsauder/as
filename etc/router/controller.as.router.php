<?php
class ASrouterController {

	private $sid;

	function __construct( ) {

	}

	public function getPage() {

		$controller = $this->route();

		if($controller!==false) {
			$rendered = $this->getContent( $controller );
		}

		return $rendered;

	}

	private function route( ) {

		if($_SESSION['AS']['var']['router']['controller']) {
			include_once(AS_VAR_PATH.'router/controller.router.php');
			$rt = new routerController();
			$controllerInfo = $rt->getControllerName();
		}
		else {
			$controllerInfo = array( 'sid'=>'error', 'params'=>array('error'=>'404 Not Found'));
		}

		$this->sid = $controllerInfo['sid'];

		$controller = $this->loadController( $controllerInfo['sid'], $controllerInfo['params'] );
		return $controller;
	}

	private function loadController( $controller_sid, $controller_params ) {
		if($controller_sid!='') {

			if(file_exists(AS_APP_PATH.$controller_sid.'/model.'.$controller_sid.'.php')) {
				include_once($controller_sid.'/model.'.$controller_sid.'.php');
			}

			if(file_exists(AS_APP_PATH.$controller_sid.'/controller.'.$controller_sid.'.php')) {
				include_once($controller_sid.'/controller.'.$controller_sid.'.php');
			}

			$class = $controller_sid.'Controller';

			if(class_exists($class)) {
				$controller = new $class( $controller_params, $_GET, $_POST, $_GET['mobile'] );
			}
		}

		if(!isset($controller)) {
			include_once(AS__PATH.'etc/error/controller.error.php');
			$controller = new errorController( $controller_params );
		}

		return $controller;
	}

	private function getContent( $controller ) {

		//controller is defined in the function getContentController
		$templateFilePrepend = '';
		if(isset($_GET['iframe']) && $_GET['iframe']=='true') {
			$templateFilePrepend = 'iframe.';
		}

		$showHeader		= $controller->includeHeader();
		$showFooter		= $controller->includeFooter();

		$viewAndVars	= $controller->view();
		$title 			= $controller->getPageTitle();
		$keywords		= $controller->getPageKeywords();

		$description	= '';
		$scripts 		= array();
		$css			= array();
		$showHeader		= true;
		$showFooter		= true;

		if(method_exists($controller, 'getPageScripts')){
			$scripts	= $controller->getPageScripts();
		}

		if(method_exists($controller, 'getPageCSS')){
			$css		= $controller->getPageCSS();
		}

		if(method_exists($controller, 'getPageDescription')) {
			$description = 	$controller->getPageDescription();
		}

		if(method_exists($controller, 'includeHeader')) {
			$showHeader = 	$controller->includeHeader();
		}

		if(method_exists($controller, 'includeFooter')) {
			$showFooter = 	$controller->includeFooter();
		}



		ob_start();
			include(getThemePath('/templates/'.$templateFilePrepend.'template.wrapper.php'));
			$html = ob_get_contents();
		ob_end_clean();

		return $html;

	}

	private function getBody( $viewAndVars ) {

		if(isset($viewAndVars['vars'])) {
			//make view variables local scope and accessible by the key
			foreach($viewAndVars['vars'] as $varName=>$varValue) {
				$$varName = $varValue;
			}
		}

		ob_start();
			include(getThemePath('/'.$this->sid.'/'.$viewAndVars['view']));
			$body = ob_get_contents();
		ob_end_clean();

		return $body;
	}

}
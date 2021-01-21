<?php
include_once('advanced_controller_router.php');
class ASrouterController {

	private $sid;

	function __construct( ) {

	}

	public function getPage() {

		$route = $this->route();

		$controller	= $route['controller'];
		$router		= $route['router'];

		$rendered = '';
		if($controller!==false) {
			$rendered = $this->getContent( $controller, $router );
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

		return array('controller'=>$controller, 'router'=>$rt);
	}

	private function loadController( $controller_sid, $controller_params ) {
		if($controller_sid!='') {

			$noterror = true;
			if($controller_sid=='error' && !file_exists(AS_APP_PATH.$controller_sid.'/controller.'.$controller_sid.'.php')) {
				$noterror = false;
				error_log('Controller sid is equal to error and no error controller exists in app. Processing error using standard AS methods.');
			}

			if($noterror) {
				if(file_exists(AS_APP_PATH.$controller_sid.'/model.'.$controller_sid.'.php')) {
					include_once($controller_sid.'/model.'.$controller_sid.'.php');
				}

				if(file_exists(AS_APP_PATH.$controller_sid.'/controller.'.$controller_sid.'.php')) {
					include_once($controller_sid.'/controller.'.$controller_sid.'.php');
				}

				$class = $controller_sid.'Controller';

				if(class_exists($class)) {
					$controller = new $class( $controller_params, $_GET, $_POST );
				}
				else {
					error_log('Controller not found. Check controller name. ');
				}
			}
		}
		else {
			error_log('Controller name not set.');
		}

		if(!isset($controller)) {
			if(!headers_sent()) {
				if(http_response_code()==200 || http_response_code()==null) {
					http_response_code(404);
				}
			}
			$this->sid = 'error';
			include_once(AS__PATH.'etc/error/controller.error.php');
			$controller = new errorController( $controller_params );
		}

		return $controller;
	}

	private function getContent( $controller, $router ) {

		//BODY CONTENT (with caching)
			$allowCaching = true;
			$body = false;

			if(method_exists($controller, 'allowCaching')){
				$allowCaching = $controller->allowCaching();
			}

			//if caching is allowed, we try to get the output from the cache and only if it doesn't exist do we run the resource expensive controller view()
			if($allowCaching) {
				$cacheKey = str_replace('?', '_',\framework\helpers\cache::sanitizeKey($_SERVER['REQUEST_URI']));
				$body	  = \framework\helpers\cache::get( 'output', $cacheKey );
			}

			//call local variables whether cache is real or not.
			if(method_exists($router, 'getAdditionalVars')) {
				$localVariables = $router->getAdditionalVars();
			}

			if($body===false) {

				if(method_exists($router, 'customView')){
					$viewAndVars	= $router->customView( $controller );

					//VIEW AND VAR local vars for template
					$localVariables = array();
					if(isset($viewAndVars[0])) {
						foreach($viewAndVars as $vv) {
							if(isset($vv['vars'])) {
								$localVariables = array_merge( $localVariables, $vv['vars'] );
							}
						}
					}
					elseif(isset($viewAndVars['vars'])) {
						$localVariables = $viewAndVars['vars'];
					}

				}
				else {
					$router = new ASAdvancedControllerRouter( $controller );
					$viewAndVars = $router->get();
				}


				$body = $this->getBody($viewAndVars);


				if($allowCaching) {
					\framework\helpers\cache::put( 'output', $cacheKey, $body );
				}
			}

		//JavaSCRIPT
			$scripts 		= array();
			if(method_exists($controller, 'getPageScripts')){
				$scripts	= $controller->getPageScripts();
			}

			$requireJSModules	= array();
			if(method_exists($controller, 'getRequireJSModules')){
				$requireJSModules	= $controller->getRequireJSModules();
			}

		//CSS
			$rcss			= array();
			$ccss			= array();
			if(method_exists($router, 'customGetPageCSS')){
				$rcss		= $router->customGetPageCSS( $controller );
			}
			if(method_exists($controller, 'getPageCSS')){
				$ccss		= $controller->getPageCSS();
			}
			$css = array_merge($rcss, $ccss);

		//TITLE
			$title	= '';
			if(method_exists($controller, 'getPageTitle')) {
				$title = $controller->getPageTitle();
			}

		//META: Keywords
			$keywords	= '';
			if(method_exists($controller, 'getPageKeywords')) {
				$keywords = 	$controller->getPageKeywords();
			}

		//META: DESCRIPTION
			$description	= '';
			if(method_exists($controller, 'getPageDescription')) {
				$description = 	$controller->getPageDescription();
			}

		//INVLUDE HEADER/FOOTER
			$showHeader		= true;
			if(isset($viewAndVars['data']) || isset($viewAndVars[0]['data'])) {
				$showHeader = false;
			}
			elseif(method_exists($controller, 'includeHeader')) {
				$showHeader = 	$controller->includeHeader();
			}

			$showFooter		= true;
			if(isset($viewAndVars['data']) || isset($viewAndVars[0]['data'])) {
				$showFooter = false;
			}
			elseif(method_exists($controller, 'includeFooter')) {
				$showFooter = 	$controller->includeFooter();
			}



		//TEMPLATE
			$defaultTemplate = true;
			if(method_exists($controller, 'differentTheme')) {

				$defaultTemplate = false;
				$themeToUse = 	$controller->differentTheme();

			}

			//controller is defined in the function getContentController
				$templateFilePrepend = '';
				if(isset($_GET['mobile']) && $_GET['mobile']=='true') {
					$templateFilePrepend = 'm.';
				}
				if(isset($_GET['iframe']) && $_GET['iframe']=='true') {
					$templateFilePrepend = 'iframe.';
				}

			//grab the appropriate template wrapper
				if($defaultTemplate) {
					$viewToInclude = getThemePath('/templates/'.$templateFilePrepend.'template.wrapper.php');
				}
				else {
					$viewToInclude = $themeToUse.'/templates/'.$templateFilePrepend.'template.wrapper.php';
				}



			//PRINT OUT!
				ob_start();
					if(file_exists($viewToInclude)) {
						include($viewToInclude);
					}
					$html = ob_get_contents();
				ob_end_clean();

		return $html;

	}

	private function getBody( $viewAndVars ) {

		$body = '';

		//do we have to deal with multiple file/var sets?
		if(isset($viewAndVars[0])) {
			foreach($viewAndVars as $viewAndVar) {
				$body .= $this->getBody($viewAndVar);
			}
			return $body;
		}

		if(isset($viewAndVars['data'])) {

			header('Content-Type:application/json');

			if(is_array($viewAndVars['data']) || is_object($viewAndVars['data'])) {
				$d = json_encode($viewAndVars['data']);
			}
			else {
				$d = $viewAndVars['data'];
			}
			return $d;
		}

		if(isset($viewAndVars['vars'])) {
			//make view variables local scope and accessible by the key
			foreach($viewAndVars['vars'] as $varName=>$varValue) {
				$$varName = $varValue;
			}
		}

		if(substr($viewAndVars['view'],0,1)=='/') {
			$path = $viewAndVars['view'];
		}
		elseif(substr($viewAndVars['view'],1,2)==':\\') {
			$path = $viewAndVars['view'];
		}
		elseif (substr($viewAndVars['view'], 0, 2) == '\\\\') { // support for UNC paths (Eric)
			$path = $viewAndVars['view'];
		}
		else {
			if(substr($viewAndVars['view'], 0, strlen($this->sid)) == $this->sid) {
				$path = '/'.$viewAndVars['view'];
			}
			else {
				$path = '/'.$this->sid.'/'.$viewAndVars['view'];
			}
		}

		$viewToInclude = $this->view($path);

		ob_start();
			if(stream_resolve_include_path($viewToInclude)!==false) {
				include($viewToInclude);
			}
			if(isset($viewAndVars['js'])) {
				echo '<script>window.'.$this->sid.' = '. json_encode($viewAndVars['js']).';</script>';
			}
			$body = ob_get_contents();
		ob_end_clean();

		return $body;
	}



	private static function view( $path ) {

		$path = trim($path, '/');

		$parts = explode('/', $path);

		$c = count($parts);

		if($c>0) {

			$ses = $_SESSION[AS_APP]['theme']['views'];

			if(!isset($ses[$parts[0]])) {
				return $path;
			}

			$dir = array();

			foreach($parts as $i=>$v) {
				if($v!='views' && $i<($c-1)) {
					if(isset($ses[$v])) {
						$ses = $ses[$v];
						$dir[] = $v;
					}
				}
			}

			$fil = $parts[$c-1];

			if( in_array( $fil, $ses ) ) {
				return getThemePath('templates/'.implode('/',$dir) .'/'.$fil);
			}

		}

		return $path;
	}

}
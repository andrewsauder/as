<?php
class ASloader {

	function __construct() {
		if(!defined('AS__PATH')) {

			if(isset($_SERVER['APPL_PHYSICAL_PATH'])) {
				$wwwPath = rtrim($_SERVER['APPL_PHYSICAL_PATH'], '/\\');
			}
			elseif(isset($_SERVER['DOCUMENT_ROOT'])) {
				$wwwPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
			}
			else {
				$wwwPath = __DIR__;
			}

			//PATHS
				define('AS_WWW_PATH', $wwwPath);
				define('AS_ROOT_PATH', $wwwPath.'/../');
				define('AS_APP_PATH', $wwwPath.'/../app/');
				define('AS_VAR_PATH', $wwwPath.'/../var/');
				define('AS_LOG_PATH', $wwwPath.'/../logs/');
				define('AS_CACHE_PATH', $wwwPath.'/../cache/');
				define('AS_TMP_PATH', $wwwPath.'/../var/tmp/');
				define('AS__PATH', $wwwPath.'/../AS/');
			//APP DEFAULT INCLUDE PATH
				set_include_path( AS_APP_PATH );
		}

		//start session
			$this->startSession();
	}

	private function startSession() {

		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

	}

	private function getRequiredFiles() {
		$files = [];


		$files[] = AS__PATH.'/opts/functions.php';
		$files[] = AS__PATH.'/opts/cacheSys.php';
		$files[] = AS__PATH.'/opts/tools.php';
		$files[] = AS__PATH.'/opts/email.php';
		$files[] = AS__PATH.'/opts/db.php';

		$files[] = AS__PATH.'/etc/session/controller.as.session.php';
		$files[] = AS__PATH.'/etc/router/controller.as.router.php';

		return $files;
	}

	private function parseURL() {

		if(!isset($_GET['R0'])) {
			$_GET['R0'] = '';
			$url = [];
		}
		else {
			$url = explode('/', $_GET['R0']);
			foreach($url as $i=>$urlKey) {
				if(!isset($_GET['R'.($i+1)])) {
					$_GET['R'.($i+1)] = $urlKey;
				}
			}
		}

		//force GET R to be set for up to 6 places
		for($i=0; $i<6; $i++) {
			if(!isset($_GET['R'.($i+1)])) {
				$_GET['R'.($i+1)] = '';
			}
		}

	}

	private function initiateAS() {

		//parse URL
			$this->parseURL();

		//INCLUDE REQUIRED FILES
			$files = $this->getRequiredFiles();
			foreach($files as $f) {
				include_once( $f );
			}

		//CLEAN START
			if(!isset($_SESSION['AS']) || isset($_GET['cleansession'])) {
				$_SESSION['AS'] = [];
				$_SESSION['AS']['config'] = tools::getXMLAsArray(AS_VAR_PATH."config.xml");
				foreach($_SESSION['AS']['config']['environment'] as $environment=>$opts) {
					if(isset($opts['srv']['server_name'])) {
						$_SESSION['AS']['config']['environment'][$environment]['srv'] = array($opts['srv']);
					}
				}
			}

			if(isset($_GET['cleancache'])) {
				cacheSys::deleteAllCache();
			}

	}


	private function initiateApp( $pageLoad=true ) {
		//CONFIG
			if(!defined('AS_APP')) {
				define('AS_APP', $_SESSION['AS']['config']['app']['machinename']);
			}

		//CLEAN START
			if(!isset($_SESSION[ AS_APP ]) || isset($_GET['cleansession']) || $_SERVER['SERVER_NAME']=='cli') {
				$_SESSION[ AS_APP ] = [];
				ASsessionController::startApp();
			}

		//SESSION
			if($pageLoad) {
				ASsessionController::pageLoad();
			}
	}


	public function startCLIApp() {
		$this->initiateAS();
		$this->initiateApp();
	}

	public function startApp() {
		$this->initiateAS();
		$this->initiateApp();
	}

	public function renderApp() {
		$router = new ASrouterController( $_GET, $_POST );
		$c = $router->getPage();
		return $c;
	}


}
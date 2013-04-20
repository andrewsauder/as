<?php
session_name('AS'.preg_replace("/[^A-Za-z0-9 ]/", '', $_SERVER['SERVER_NAME'].$_SERVER["SERVER_PORT"]));
session_start();

class ASloader {

	function __construct() {
		if(!defined('AS__PATH')) {
			//PATHS
				define('AS_WWW_PATH', $_SERVER['DOCUMENT_ROOT']);
				define('AS_ROOT_PATH', $_SERVER['DOCUMENT_ROOT']."/../");
				define('AS_APP_PATH', $_SERVER['DOCUMENT_ROOT']."/../app/");
				define('AS_VAR_PATH', $_SERVER['DOCUMENT_ROOT']."/../var/");
				define('AS__PATH', $_SERVER['DOCUMENT_ROOT']."/../AS/");
			//APP DEFAULT INCLUDE PATH
				set_include_path( AS_APP_PATH );
		}
	}

	private function getRequiredFiles() {
		$files = array();

		$files[] = AS__PATH.'/opts/functions.php';
		$files[] = AS__PATH.'/opts/tools.php';
		$files[] = AS__PATH.'/opts/email.php';
		$files[] = AS__PATH.'/opts/db.php';

		$files[] = AS__PATH.'/etc/session/controller.as.session.php';
		$files[] = AS__PATH.'/etc/router/controller.as.router.php';

		return $files;
	}

	private function initiateAS() {
		//INCLUDE REQUIRED FILES
			$files = $this->getRequiredFiles();
			foreach($files as $f) {
				include_once( $f );
			}

		//CLEAN START
			if(!isset($_SESSION['AS']) || isset($_GET['cleansession'])) {
				$_SESSION['AS'] = array();
				$_SESSION['AS']['config'] = getXMLAsArray(AS_VAR_PATH."config.xml");
			}

	}


	private function initiateApp() {
		//CONFIG
			if(!defined('AS_APP')) {
				define('AS_APP', $_SESSION['AS']['config']['app']['machinename']);
			}

		//CLEAN START
			if(!isset($_SESSION[ AS_APP ]) || isset($_GET['cleansession'])) {
				$_SESSION[ AS_APP ] = array();
				ASsessionController::startApp();
			}

		//SESSION
			ASsessionController::pageLoad();
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
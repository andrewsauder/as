<?php
class ASsessionController {

	public static function startApp() {
		self::setTesting();
		self::setAppVersion();
		self::setUserAgent();
		self::setASVar();

		//ASvar session controller runs on startApp if available
		if($_SESSION['AS']['var']['session']['controller']) {
			include_once(AS_VAR_PATH . 'session/controller.session.php');
			sessionController::startApp();
		}
	}

	public static function pageLoad() {
		//cast mobile to bool
		if(isset($_GET['mobile'])) {
			if($_GET['mobile']=='true') {
				$_GET['mobile'] = true;
			}
			else {
				$_GET['mobile'] = false;
			}
		}
		else {
			$_GET['mobile'] = false;
		}

		//load any app specific session
		if($_SESSION['AS']['var']['session']['controller']) {
			include_once(AS_VAR_PATH . 'session/controller.session.php');
			sessionController::pageLoad();
		}


	}

	private static function setTesting() {
		if(!isset($_SESSION[ AS_APP ]['testing'])) {

			$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'cli';

			$activeEnvironment = array();
			foreach($_SESSION['AS']['config']['environment'] as $environment=>$opts) {
				if(isset($opts['srv']['server_name'])) {
					$opts['srv'] = array($opts['srv']);
				}
				foreach($opts['srv'] as $server) {
					if($host==$server['server_name']) {
						$activeEnvironment = $environment;

						if(isset($server['db_connector']['server'])) {
							$db_connectors = array( $server['db_connector']['db'] => $server['db_connector'] );
						}
						else {
							$db_connectors = array();
							foreach($server['db_connector'] as $db_connector) {
								$db_connectors[ $db_connector['db'] ] = $db_connector;
							}
						}

						$_SESSION[ AS_APP ]['db'] = array(
							'db_conntectors'=>$db_connectors,
							'default_db'=>$server['default_db']
						);

					}
				}
			}

			if($activeEnvironment=='local') {
				$_SESSION[ AS_APP ]['testing'] = true;
				$_SESSION[ AS_APP ]['localtesting'] = true;
			}
			elseif($activeEnvironment=='dev') {
				$_SESSION[ AS_APP ]['testing'] = true;
				$_SESSION[ AS_APP ]['localtesting'] = false;
			}
			elseif($activeEnvironment=='prod') {
				$_SESSION[ AS_APP ]['testing'] = false;
				$_SESSION[ AS_APP ]['localtesting'] = false;
			}
			else {
				error('403 Forbidden');
			}

		}
	}

	private static function setUserAgent() {
		if(!isset($_SESSION[ AS_APP ]['mobile'])) {
			$agent   = strtolower($_SERVER['HTTP_USER_AGENT']);

			$iphone  = strpos($agent, "iphone");
			$android = strpos($agent, "android");
			$palmpre = strpos($agent, "webos");
			$berry   = strpos($agent, "blackberry");
			$ipod    = strpos($agent, "ipod");

			if ($iphone>=0 || $android>=0 || $palmpre>=0 || $ipod>=0 || $berry>=0)  {
				$_SESSION[ AS_APP ]['mobile'] = true;
			}
			else {
				$_SESSION[ AS_APP ]['mobile'] = false;
			}
		}
	}

	private static function setAppVersion() {
		if(!isset($_SESSION[ AS_APP ]['version'])) {
			if(file_exists(AS_APP_PATH .".version")) {
				$svnrevision = file_get_contents(AS_APP_PATH .".version");
				$version = preg_replace("/[^0-9]/", "", $svnrevision);
				$_SESSION[ AS_APP ]['version'] = number_format($version/100,2);
			}
		}
	}

	private static function setASVar() {
		if(!isset($_SESSION['AS']['var'])) {

			$_SESSION['AS']['var'] = array();

			$ASvar = ls(AS_VAR_PATH);

			foreach( $ASvar as $item ) {
				if($item['type']=='dir') {

					$_SESSION['AS']['var'][ $item['name'] ] = array(
						'controller' =>false,
						'model'      =>false,
						'files'		 =>$item['files']
					);

					foreach($item['files'] as $f) {
						if($f['name']=='controller.'.$item['name'].'.php') {
							$_SESSION['AS']['var'][ $item['name'] ]['controller'] = true;
						}
						elseif($f['name']=='model.'.$item['name'].'.php') {
							$_SESSION['AS']['var'][ $item['name'] ]['model'] = true;
						}
					}
				}
			}

		}
	}

}

<?php
if( file_exists( AS_ROOT_PATH . '/vendor/autoload.php' )) {
	include_once(AS_ROOT_PATH . '/vendor/autoload.php');
}
include_once(AS__PATH . '/as.autoloader.php');

class ASsessionController {

	public static function startApp() {
		self::setTesting();
		self::setASVar();

		//ASvar session controller runs on startApp if available
		if($_SESSION['AS']['var']['session']['controller']) {
			include_once(AS_VAR_PATH . 'session/controller.session.php');
			sessionController::startApp();
		}
	}

	public static function pageLoad() {
		//load any app specific session
		if($_SESSION['AS']['var']['session']['controller']) {
			include_once(AS_VAR_PATH . 'session/controller.session.php');
			sessionController::pageLoad();
		}
	}

	public static function setTesting() {
		if(!isset($_SESSION[ AS_APP ]['testing'])) {

			$host = isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : 'cli';

			$activeEnvironment = '';

			//AS_ENVIRONMENT names the one environment this server may run. Without it, any
			//environment whose <srv> matches the host is used, and a host that matches none
			//runs in local testing mode, which some templates answer with the whole session.
			$requiredEnvironment = getASEnvironmentValue('AS_ENVIRONMENT');
			$environments = $_SESSION['AS']['config']['environment'];
			if($requiredEnvironment!==null) {
				$environments = isset($environments[$requiredEnvironment]) && is_array($environments[$requiredEnvironment]) ? [ $requiredEnvironment=>$environments[$requiredEnvironment] ] : [];
			}

			foreach($environments as $environment=>$opts) {

				if(!isset($opts['srv'])) {
					continue;
				}

				if(isset($opts['srv']['server_name'])) {
					$opts['srv'] = array($opts['srv']);
				}

				foreach($opts['srv'] as $server) {

					//exact matches in config
					$serverNameCompare = $server['server_name'];
					$hostCompare = $host;
					$wildcard = false;

					//capture wildcard when present (match on domain only)
					if(substr($server['server_name'],0,1)=='*') {
						$serverNameCompare = substr($server['server_name'], 2);

						//no subdomain
						if(substr_count($host, '.')==1) {
							$wildSubDomain = '';
							$wildSubDomainReplacement = $wildSubDomain;
						}
						//any subdomain
						else {
							$hostSubStrPos = strpos($host, '.');
							$hostCompare = substr( $host, $hostSubStrPos+1 );
							$wildSubDomain = substr( $host, 0, $hostSubStrPos);
							$wildSubDomainReplacement = $wildSubDomain.'.';
						}

						$wildcard = true;

					}

					//if hostname matches this config srv
					if($hostCompare==$serverNameCompare) {

						//replace wildcards for session
						if($wildcard) {
							//set new subdomain field
							$server['subdomain'] = $wildSubDomain;
							//replace wildcarded fields
							$server['base_url'] = str_replace('*.', $wildSubDomainReplacement, $server['base_url']);
							$server['cookie_url'] = str_replace('*.', $wildSubDomainReplacement, $server['cookie_url']);
							$server['server_name'] = str_replace('*.', $wildSubDomainReplacement, $server['server_name']);
						}

						//define active environment (local, cli, dev, prod) for testing variables
						$activeEnvironment = $environment;


						//build data connectors
						if(isset($server['db_connector'])) {
							$db_connectors = [];
							if(isset($server['db_connector']['server'])) {
								$server['db_connector'] = array( $server['db_connector'] );
							}

							if(count($server['db_connector'])>0) {
								foreach($server['db_connector'] as $i=>$db_connector) {
									if(!isset( $db_connector[ 'alias' ])) {
										$db_connector[ 'alias' ] = [];
									}
									if(!is_array($db_connector[ 'alias' ])) {
										$db_connector[ 'alias' ] = [ $db_connector[ 'alias' ] ];
									}
									$db_connectors[ $db_connector['db'] ] = $db_connector;
									$server['db_connector'][$i] = $db_connector;
								}
							}

							$_SESSION[ AS_APP ]['db'] = array(
								'db_conntectors'=>$db_connectors,
								'default_db'=>$server['default_db']
							);
						}

						//set environment in session
						$_SESSION[ AS_APP ]['environment'] = $server;

						break;
					}
				}

			}

			if($requiredEnvironment!==null && $activeEnvironment==='') {
				self::refuseUnknownHost($host, $requiredEnvironment);
			}

			$_SESSION[ AS_APP ]['cli'] = php_sapi_name()=='cli';

			if($activeEnvironment=='local') {
				$_SESSION[ AS_APP ]['testing'] = true;
				$_SESSION[ AS_APP ]['localtesting'] = true;
			}
			elseif($activeEnvironment=='cli') {
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
				$_SESSION[ AS_APP ]['testing'] = true;
				$_SESSION[ AS_APP ]['localtesting'] = true;
			}

		}
	}

	/**
	 * Stops a request for a host that the environment in AS_ENVIRONMENT does not list.
	 * The application session is removed, so the next request checks the host again.
	 */
	private static function refuseUnknownHost( $host, $environment ) {
		$safeHost = preg_replace('/[^a-z0-9.*_-]/', '?', $host);
		$message = 'AS_ENVIRONMENT is "'.$environment.'", and no <srv> under <environment><'.$environment.'> in var/config.xml has <server_name>'.$safeHost.'</server_name>. Request refused.';
		error_log($message);

		unset($_SESSION[ AS_APP ]);

		if(php_sapi_name()=='cli') {
			fwrite(STDERR, $message.PHP_EOL);
			exit(1);
		}

		if(!headers_sent()) {
			http_response_code(421);
			header('Content-Type: text/plain; charset=utf-8');
		}
		echo 'Misdirected Request';
		exit;
	}

	public static function setASVar() {
		if(!isset($_SESSION['AS']['var'])) {
			$_SESSION['AS']['var'] = [];

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

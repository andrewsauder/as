<?php
namespace andrewsauder\asframework;

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

			foreach($_SESSION['AS']['config']['environment'] as $environment=>$opts) {

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

						//set environment in session
						$_SESSION[ AS_APP ]['environment'] = $server;

						//build data connectors
						if(isset($server['db_connector'])) {
							if(isset($server['db_connector']['server'])) {
								$db_connectors = array( $server['db_connector']['db'] => $server['db_connector'] );
							}
							else {
								$db_connectors = array();
								if(count($server['db_connector'])>0) {
									foreach($server['db_connector'] as $db_connector) {
										$db_connectors[ $db_connector['db'] ] = $db_connector;
									}
								}
							}

							$_SESSION[ AS_APP ]['db'] = array(
								'db_conntectors'=>$db_connectors,
								'default_db'=>$server['default_db']
							);
						}

						break;
					}
				}

			}

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

	public static function setASVar() {
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

<?php
/**
 * The health check of an AS application in a container.
 *
 * nginx sends GET /healthz here. The nginx image's HEALTHCHECK calls it, and so does
 * Traefik's readiness check, so a deploy from gcgov/deploy succeeds only when it passes.
 * It never starts a session and never reaches the application's router, so it does not
 * depend on the Host header matching a <srv> in var/config.xml.
 *
 * It does no network I/O. A database outage must not restart the container.
 *
 * The response says "ok" or "unavailable" and nothing else. Each reason goes to the log.
 */

require_once __DIR__ . '/../opts/functions.php';

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'Cache-Control: no-store' );

$wwwPath = isset( $_SERVER['DOCUMENT_ROOT'] ) ? rtrim( $_SERVER['DOCUMENT_ROOT'], '/\\' ) : '';
if( $wwwPath==='' ) {
	$wwwPath = dirname( __DIR__, 2 ) . '/www';
}
$rootPath = $wwwPath . '/../';
$varPath  = $rootPath . 'var/';

$problems = [];

//var/config.xml and the local config file
$base = getXMLAsArrayQuietly( $varPath . 'config.xml' );
if( $base===null ) {
	$problems[] = 'var/config.xml is missing or is not valid XML.';
}
elseif( !isset( $base['app']['machinename'] ) || !is_string( $base['app']['machinename'] ) || $base['app']['machinename']==='' ) {
	$problems[] = 'var/config.xml has no <app><machinename>.';
}
else {
	try {
		$config = getConfigAsArray( $varPath );
	}
	catch( \RuntimeException $e ) {
		$problems[] = $e->getMessage();
		$config     = $base;
	}

	if( getASEnvironmentValue( 'AS_CONFIG_LOCAL_FILE' )===null && is_file( $varPath . 'config.local.example.xml' ) && !is_file( $varPath . 'config.local.xml' ) ) {
		$problems[] = 'The application ships var/config.local.example.xml, so it has secrets, and AS_CONFIG_LOCAL_FILE is not set.';
	}

	$requiredEnvironment = getASEnvironmentValue( 'AS_ENVIRONMENT' );
	if( $requiredEnvironment!==null && empty( $config['environment'][ $requiredEnvironment ]['srv'] ) ) {
		$problems[] = 'AS_ENVIRONMENT is "' . $requiredEnvironment . '", and var/config.xml has no <srv> under <environment><' . $requiredEnvironment . '>.';
	}
}

//a session name that is the same on every request
if( getASEnvironmentValue( 'AS_SESSION_NAME' )!==null ) {
	if( getSessionNameFromEnvironment()===null ) {
		$problems[] = 'AS_SESSION_NAME is not a valid session name.';
	}
}
elseif( !is_writable( __DIR__ ) ) {
	$problems[] = 'AS_SESSION_NAME is not set, and AS cannot write its session name file to ' . __DIR__ . '.';
}

//the directories AS writes on every request
$cachePath = $rootPath . 'cache';
if( !is_dir( $cachePath ) || !is_writable( $cachePath ) ) {
	$problems[] = 'The output cache directory ' . $cachePath . ' is missing or not writable.';
}

$sessionPath = session_save_path();
if( strpos( $sessionPath, ';' )!==false ) {
	$sessionPath = substr( $sessionPath, strrpos( $sessionPath, ';' ) + 1 );
}
if( $sessionPath==='' ) {
	$sessionPath = sys_get_temp_dir();
}
if( !is_dir( $sessionPath ) || !is_writable( $sessionPath ) ) {
	$problems[] = 'The session directory ' . $sessionPath . ' is missing or not writable.';
}

if( count( $problems )>0 ) {
	foreach( $problems as $problem ) {
		error_log( 'AS health: ' . $problem );
	}
	http_response_code( 503 );
	echo "unavailable\n";
	exit;
}

echo "ok\n";

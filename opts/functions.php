<?php

function getFullURL() {

	$s = '';
	if(!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
		$s = 's';
	}
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
	$url = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];

	return $url;
}


function getBaseURL() {
	return rtrim($_SESSION[AS_APP]['environment']['base_url'], '/');
}


function getThemePath($addpath = '') {

	$extended = $addpath;
	if(substr($extended, 0, 1) != '/') {
		$extended = '/' . $addpath;
	}

	return AS_WWW_PATH.'/theme/' . $_SESSION[AS_APP]['theme']['dirname'] . $extended;
}


function getThemeURL($addpath = '') {

	$extended = $addpath;
	if(substr($extended, 0, 1) != '/') {
		$extended = '/' . $addpath;
	}

	$base = getBaseURL();

	if(substr($base, -1)=='/') {
		$base = substr($base, 0, strlen($base)-1);
	}

	return $base . '/theme/' . $_SESSION[AS_APP]['theme']['dirname'] . $extended;
}




/**
 * Sets the http response code and optionally overwrites output buffer with error template
 * @param int $statusCode HTTP Status Code to return. Ex: 404
 * @param bool $replaceOBwError If true, overtakes the request (nothing further in your code will run) and places error page in output buffer. If false, output buffer remains in existing state. When true, $die is ignored and the error page handles the rest of the request.
 * @param bool $die If true, function dies after setting status code and optionally including error page. If false, caller must handle the error output.
 * @return int new HTTP status code
 */
function httpError($statusCode, $replaceOBwError=true, $die=true) {
	if(!headers_sent()) {
		http_response_code($statusCode);
	}
	if($replaceOBwError) {
		include(AS_WWW_PATH."/error.php");
	}
	if($replaceOBwError || $die) {
		die();
	}

	return $statusCode;
}


function pie($v) {
	error_log('******PIE Function is being ran******');
	elog($v);
	echo '<pre>';
	var_dump($v);
	die();
}

function elog( $v ) {

	if(is_array($v) || is_object($v)) {
		$v = json_encode($v);
	}
	elseif(is_numeric($v)) {
		$v = (string) $v;
	}
	elseif(is_bool($v)) {
		$v = $v ? 'true' : 'false';
	}

	error_log( $v );

}



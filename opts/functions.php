<?php


function hasValue($var, $checkZero = false) {
	if(!isset($var)) {
		return false;
	}

	if($var === NULL || $var == '') {
		return false;
	}
	if($checkZero) {
		if($var === 0 || $var == '0') {
			return false;
		}
	}
	return true;
}


function getFullURL($m = false) {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
	$mobile = $m ? '/m/' : '';
	$url = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $mobile . $_SERVER['REQUEST_URI'];

	return $url;
}


function getBaseURL() {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
	$url = $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;

	return $url;
}


function getThemePath($addpath = '') {

	$extended = $addpath;
	if(substr($extended, 0, 1) != '/') {
		$extended = '/' . $addpath;
	}

	return '../www/theme/' . $_SESSION[AS_APP]['theme']['dirname'] . $extended;
}


function getThemeURL($addpath = '') {

	$extended = $addpath;
	if(substr($extended, 0, 1) != '/') {
		$extended = '/' . $addpath;
	}

	return 'http://' . $_SERVER['HTTP_HOST'] . '/theme/' . $_SESSION[AS_APP]['theme']['dirname'] . $extended;
}


function convertArrayToCSV($array, $header_row = true, $col_sep = ",", $row_sep = "\n", $qut = '"') {
	if(!is_array($array) or !is_array($array[0]))
		return false;

	$headers = array();

	//Header row.
	if($header_row) {
		foreach($array[0] as $key=> $val) {
			//Escaping quotes.
			$key = str_replace($qut, "$qut$qut", $key);
			$output .= "$col_sep$qut$key$qut";
			$headers[] = $key;
		}
		$output = substr($output, 1) . "\n";
	}
	//Data rows.
	foreach($array as $key=> $val) {
		$tmp = '';
		//foreach ($val as $cell_key => $cell_val)
		foreach($headers as $ik) {
			$cell_val = str_replace($qut, "$qut$qut", $val[$ik]);
			$tmp .= "$col_sep$qut$cell_val$qut";
		}
		$output .= substr($tmp, 1) . $row_sep;
	}

	return $output;
}


function getRandomString($length = 8, $upper = true, $lower = true, $numbers = true, $symbols = true) {
	$random = "";
	srand((double) microtime() * 1000000);
	$char_list = '';
	if($upper) {
		$char_list .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
	}
	if($lower) {
		$char_list .= "abcdefghijklmnpqrstuvwxyz";
	}
	if($numbers) {
		$char_list .= "123456789";
	}

	for($i = 0; $i < $length; $i++) {
		$random .= substr($char_list, (rand() % (strlen($char_list))), 1);
	}

	return $random;
}


function error($status) {
	header("Status: " . $status);
	include("../www/error.php");
	die();
}


function pie($v) {
	echo '<pre>';
	var_dump($v);
	die();
}


function urlExists($url) {
	$hdrs = @get_headers($url);

	echo @$hdrs[1] . "\n";

	return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/', $hdrs[0]) : false;
}


function getXMLAsArray($src, $format = 'array') {
	$xml = simplexml_load_file($src);
	$json = json_encode($xml);
	if($format == 'json') {
		return $json;
	}
	elseif($format == 'array') {
		return json_decode($json, true);
	}
	elseif($format == 'object') {
		return json_decode($json, false);
	}
	else {
		return false;
	}
}


function ls($path, $returnSimple = false, $fileFilter=null, $dirFilter=null) {

	$dir = array();

	if(substr($path, -1) != '/') {
		$path = $path . '/';
	}

	if($dirFilter!==null) {
		foreach($dirFilter as $dirFilterIndex=>$df) {
			$dirFilter[ $dirFilterIndex ] = strtolower($df);
		}
	}
	if($fileFilter!==null) {
		foreach($fileFilter as $fileFilterIndex=>$ff) {
			$fileFilter[ $fileFilterIndex ] = strtolower($ff);
		}
	}

	if(!is_dir($path)) {
		return array();
	}

	if($handle = opendir($path)) {
		while(false !== ($entry = readdir($handle))) {
			if($entry != "." && $entry != "..") {
				if($returnSimple) {
					$dir[] = $entry;
				}
				else {
					$filetype = filetype($path . $entry);
					if($filetype == 'dir') {
						if($dirFilter===null || in_array(strtolower($entry), $dirFilter)) {
							$add = true;
							$files = ls($path . $entry, $returnSimple, $fileFilter);
						}
						else {
							$add = false;
						}
					}
					else {
						$files = null;
						if($fileFilter===null || in_array(strtolower(substr($entry, -3)), $fileFilter)) {
							$add = true;
						}
						else {
							$add = false;
						}
					}

					if($add) {
						$dir[] = array(
							'name'=>$entry,
							'type'=>$filetype,
							'files'=>$files
						);
					}
				}
			}
		}
	}

	return $dir;
}

function view( $path ) {

	$parts = explode('/', $path);

	$c = count($parts);

	if($c>0) {

		$ses = $_SESSION[AS_APP]['theme']['views'];

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

<?php
class tools {


	//This function is used to reduce SQL injection risk - For Microsoft SQL Server
	public static function sql($value) {

		if(is_array($value)) {
			foreach($value as $k=> $v) {
				$v2 = iconv('UTF-8', 'ASCII//TRANSLIT', $v);
				if($v2===false || $v2=='') {
					$v2 = $v;
				}
				$value[$k] = tools::sql($v2);
			}
			return $value;
		}
		else {
			if($value === NULL) {
				return NULL;
			}

			$v2 = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
			if($v2===false || $v2=='') {
				$v2 = $value;
			}

			//escapse single quotes - needed for MS-SQL
			$singQuotePattern = "'";
			$singQuoteReplace = "''";
			return stripslashes(str_replace($singQuotePattern, $singQuoteReplace, $v2));
		}
	}


	//this function converts an associative array into an SQL UPDATE [column]='value', [column2]='value2' string where column is the array key
	public static function qUpdate($post) {
		$set = array();
		foreach($post as $k=> $v) {
			if($v === NULL) {
				$set[] = "[" . tools::sql($k) . "]=NULL";
			}
			else {
				$set[] = "[" . tools::sql($k) . "]='" . tools::sql($v) . "'";
			}
		}
		return implode(', ', $set);
	}


	//this function converts an associative array into an SQL INSERT string. returning an array of columns (array keys) and corresponding sql quoted values
	public static function qInsert($post) {
		$cols = array();
		$vals = array();
		foreach($post as $k=> $v) {
			$cols[] = "[" . tools::sql($k) . "]";
			if($v === NULL) {
				$vals[] = "NULL";
			}
			else {
				$vals[] = "'" . tools::sql($v) . "'";
			}
		}
		return array('cols'=>implode(', ', $cols), 'vals'=>implode(', ', $vals));
	}


	//this function converts an associative array into an SQL INSERT string. returning an array of columns (array keys) and corresponding sql quoted values
	/**
	 * qInsertMultipleRows
	 *
	 * Function can be used to set up the columns and values porition of a tSQL insert statement for multiple rows.
	 * To use, pass a numerical array holding associative arrays in which the array keys correspond to the table's columns
	 * EX:
	 * [
	 *    [
	 *      'site_id'=>4,
	 *      'module_name'=>'sample_module_A'
	 *    ],
	 *    [
	 *      'site_id'=>4,
	 *      'module_name'=>'sample_module_B'
	 *    ]
	 * ]
	 *
	 * @param $query array -numerical array holding associative arrays in which the array keys correspond to the table's columns
	 *
	 * @return array with keys 'cols' and 'vals' which can be directly inserted into the tSQL query
	 */
	public static function qInsertMultipleRows($post) {
		$rows = array();
		$cols = array();
		$fin = array('cols'=>array(), 'vals'=>array());
		foreach($post as $i=> $ov) {
			foreach($ov as $k=> $v) {
				if($i == 0) {
					$fin['cols'][] = "[" . tools::sql($k) . "]";
					$cols[] = tools::sql($k);
				}
				foreach($cols as $colName) {
					$vals[$colName] = "'" . tools::sql($ov[$colName]) . "'";
				}
			}
			$fin['vals'][] = " SELECT " . implode(',', $vals) . " ";//$vals;
			$vals = array();
		}

		$fin['cols'] = implode(', ', $fin['cols']);
		$fin['vals'] = implode(' UNION ALL ', $fin['vals']);

		return $fin;
	}


	//this function converts an associative array into an SQL WHERE string. returns a string
	public static function qWhere($post, $operator = 'AND') {
		$set = array();
		foreach($post as $k=> $v) {
			if($v === NULL) {
				$match = "NULL";
				$set[] = " ( [" . tools::sql($k) . "]=NULL OR [" . tools::sql($k) . "]='' ) ";
			}
			else {
				$match = "'" . tools::sql($v) . "'";
				$set[] = " [" . tools::sql($k) . "]=" . $match . " ";
			}


		}
		return implode(' ' . $operator . ' ', $set);
	}


	public static function updateSort( $post, $tableName, $postKey='sorter' ) {
		$ids = array();

		$q = "UPDATE
					".tools::sql($tableName)."
				SET [sort] = CASE id ";

		foreach($post[$postKey] as $k=>$v) {
			$q .= "
					WHEN ".tools::sql($v)." THEN ".tools::sql($k)."
				  ";
			$ids[] = $v;
		}

		$q .= 	" END
				WHERE id IN (".implode($ids,',').")";
		//die($q);

		return $q;
	}

	//select boxes that have both employee ID and employee Group IDs pass: 'e#' or 'g#'. this function determines which one.
	public static function EGdecider($post, $key, $finEmpName = '', $finGrpName = '') {

		switch(substr($post[$key], 0, 1)) {
			case 'e':
				$employeeID = substr($post[$key], 1);
				$employeeGroupsID = '';
				break;
			case 'g':
				$employeeID = '';
				$employeeGroupsID = substr($post[$key], 1);
				break;
		}

		$fin = array(
			$finEmpName=>$employeeID,
			$finGrpName=>$employeeGroupsID
		);

		return $fin;
	}


	public static function checkToken($hours = 8, $etag = NULL) {

		if($etag === NULL) {
			$etag = $_SERVER['HTTP_ETAG'];
		}
		$t = time();

		for($i = 0; $i < $hours; $i++) {

			$ts = $t - (60 * 60 * $i);

			$comp = md5(date('Y-m-d-H', $ts) . self::getTokenKey(date('H', $ts)));

			if($comp == $etag) {
				return true;
			}
		}
		return false;
	}


	public static function createToken() {
		return md5(date('Y-m-d-H') . self::getTokenKey(date('H')));
	}


	public static function getTokenKey($hour) {
		$key = (int) $hour;
		$set = array(
			'thiekoeth#4x!awiak#ab5eyIef+asto+f$ougoe=r5amlun0u',
			'ti5poa0=UChlesiuDrOUw50Woa=LuN-edOeTl-6o8Rl!PRoUxI',
			'TR-E@o-Jo2C6EPriu6Ia!HLuPieS?ed&o73oE0Hiu#ouvI$Dr?',
			'!labrou*?!6rl04r@E_leyi&9r3ucluq!usi9cr5evlaphia&l',
			'_wO71IA$=*7OaqOuBo2XOugL$=7et-le_24A?!iud?iebl6v0a',
			'j8adr4uF!uspoaqL_sp#8s2oed6iaswia=4esi5d6lu69ief*?',
			'chl=stlUdoepH$acRluk9evlupri1@hlAZLez2uThouphiaP&u',
			'p0a5oesoeboebr3ethleri1d=lufr6es7i01luxoewrluPhles',
			'frlegoum*ut1*ASl1m*E@_e*r64m9uj$Af8*0diasIe9#a9rOa',
			'QlUD385koafOuNoUBRouy78pro5Wroa=o#Cr?A@Iu6oAt$e@oe',
			'Bi-WROEzIu-iuX=aNl_bLAs?-?_oefRiATi5GIukl0chl!hLuS',
			'phOaroutr+e*tiepouD7luQlaplaKLatiusIaS4iasplecRoaw',
			'riu2l4flUthies7U+o9Fius$+ur2aw-+*Pi3s=o_k9aml$soa9',
			'hIa79l!s@i$?T!+j@Uj?UToUKL=ylecRLeC354bRoU!IAbRieX',
			'3iu11ewrlep1lupoEti2vOEd_iuyiAblUc14es4Oaci4zIakou',
			'p&iasw!6nlucludrLethi4fiEdiufoe=8oustou3Riufoeqlap',
			'c4l7f+exlAxiechluz3usoatrleslumla5oeDroewRiawlusto',
			'&o=?+Ak?az9eswlamlaStl4noeS1oEb3lAcHou--IEpi?ri&@r',
			'vlAPh8AsIeBOetoUP@OablUfriuS#iA=8+Ji#-Ia$H#7wo!#4e',
			'-IE=6Oa!!5wrI19ria$rl5jlAj@Ast=agO&3wOAcIAXou6O@0o',
			'slE9ouphl7y7aY7e$o7Ph6Ari4bIEwroAthLuzIuKOUt3ieyoe',
			'h4UCIePh6uth=6JoUTOUM3AvoeFroUph7anl*@roAXLecHOUsw',
			'gOa$8adiepOupOuX5UtiusLuciudi?D**us!lezi?-i@CHo?!@',
			'goE@iA-IA_!o6#ri+5-iuTro&stiu$7e=o3w@a47uv0agie_Lu',
			'Spi=CrL!f$iachiaql$viaroevl8wouk7ajL7S=lep9iaBrius'
		);

		return $set[$key];
	}


	public static function convertStringToExternalURL($str) {

		if(substr($str, 0, 4) == 'http') {
			return $str;
		}
		else {
			return 'http://' . $str;
		}
	}


	public static function translateStatusCode($code) {
		switch($code) {
			case 'P':
				$status = 'Pending';
				break;
			case 'I':
				$status = 'In Progress';
				break;
			case 'F':
				$status = 'Finished & Closed';
				break;
			case 'C':
				$status = 'Uncompleted & Closed';
				break;
			default:
				$status = 'Pending';
				break;
		}

		return $status;
	}


	public static function translatePriorityCode($code) {
		switch($code) {
			case 'A':
				$status = 'Low';
				break;
			case 'M':
				$status = 'Medium';
				break;
			case 'Z':
				$status = 'High';
				break;
			default:
				$status = 'Normal';
				break;
		}

		return $status;
	}



	public static function sid( $unsafeString ) {
		$unsafeString = str_replace( '/', '-', strtolower($unsafeString));
		preg_match_all("/[a-z0-9-]+/", str_replace( ' ', '-', strtolower($unsafeString)),$matches);
		$sid = str_replace('--','-',implode('',$matches[0]));
		return $sid;
	}


	public static function getPrimaryNav($params = array()) {
		$links = array();
		$links[] = array(
			'href'=>'/' . $_SESSION['org']['sid'],
			'text'=>$_SESSION['org']['title'],
			'cssClass'=>!hasValue($_GET['site_sid']) ? 'active' : '',
			'preventActive'=>hasValue($_GET['site_sid']) ? true : false
		);

		if(isset($_SESSION['user']['type']) && $_SESSION['user']['type'] == 'X' && (!isset($params['showExec']) || $params['showExec'] != false)) {
			$links[] = array(
				'href'=>'/' . $_SESSION['org']['sid'] . '/executive',
				'text'=>'Executive Suite',
				'cssClass'=>$_GET['site_sid'] == 'executive' ? 'active' : ''
			);
		}

		$public_sites = $_SESSION['org']['public_sites'];
		$sites_in_nav = array();

		foreach($public_sites as $i=> $k) {

			$href = '/' . $_SESSION['org']['sid'] . '/' . $k['sid'] . '/public';
			if(isset($_SESSION['user']['site_id']) && hasValue($_SESSION['user']['site_id']) && $_SESSION['user']['site_id'] == $k['id'] && $_SESSION['user']['authorized']) {
				$href = '/' . $_SESSION['org']['sid'] . '/' . $k['sid'];
			}

			$links[] = array(
				'href'=>$href,
				'text'=>$k['title'],
				'cssClass'=>$k['sid'] == $_GET['site_sid'] ? 'active' : ''
			);
			$sites_in_nav[] = $k['sid'];
		}

		if(isset($_SESSION['user']['site_id']) && hasValue($_SESSION['user']['site_id'])) {
			if(!in_array($_SESSION['user']['site_sid'], $sites_in_nav)) {
				$links[] = array(
					'href'=>'/' . $_SESSION['org']['sid'] . '/' . $_SESSION['user']['site_sid'] . '/',
					'text'=>$_SESSION['user']['site_title']
				);
			}
		}

		return $links;
	}


	public static function LDAPinterface($url, $fields, $authenticate = false) {
		//set POST variables
		//url-ify the data for the POST

		$fields_string = '';
		foreach($fields as $key=> $value) {
			$fields_string .= $key . '=' . urlencode($value) . '&';
		}
		rtrim($fields_string, '&');

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		//execute post
		$result = curl_exec($ch);
		$status = curl_getinfo($ch);

		//capture any errors from curl
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);

		//close connection
		curl_close($ch);
		//die($result);


		if($curl_errno) {
			$jsonResult = array(
				'code'=>'error',
				'message'=>$curl_error
			);
		}
		else {

			$jsonResult = json_decode($result, true);
		}

		return $jsonResult;
	}

	public static function createCron( $script, $dataString='' ) {

		$bat = '"'.$_SESSION[AS_APP]['environment']['phppath'].'" -f "'.$_SESSION[AS_APP]['environment']['cronpath'].$script.'.php" '.$_SESSION[AS_APP]['environment']['cli_server_name'].' '.$dataString;
		$bat .= "\n exit";

		file_put_contents($_SESSION[AS_APP]['environment']['tmppath'].$script.'.bat', $bat);

		pclose(popen('start /B '. $_SESSION[AS_APP]['environment']['tmppath'].$script.'.bat 2>&1 >nul', "r"));

		return true;

	}

	public static function encodeEmails( $html ) {

		$matches = array();

		$emailsFound = preg_match_all("/[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})/i", $html, $matches);

		if ($emailsFound) {

			$emailReplacements = array();

			foreach ($matches[0] as $match) {

				$emailReplacements[$match] = tools::forceHtmlEncode($match);

			}

			foreach ($emailReplacements as $email => $emailReplacement) {

				$html = str_replace($email, $emailReplacement, $html);

			}

		}

		return $html;

	}

	public static function forceHtmlEncode($html) {

		$output = '';

		$len = strlen($html);

		for ($index = 0; $index < $len; $index++) {

			$char = substr($html, $index, 1);

			$output .= '&#' . strval(ord($char)) . ';';

		}

		return $output;

	}

	public static function validEmail( $string ) {

		if (filter_var($string, FILTER_VALIDATE_EMAIL)) {
			return true;
		} else {
			return false;
		}
	}

	public static function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	public static function arrayKeyStringHasValue( $var, $key, $checkZero=false ) {

		if(!isset($var[$key])) {
			return false;
		}

		if($var[$key] === NULL || trim($var[$key]) == '') {
			return false;
		}

		if($checkZero) {
			if($var[$key] === 0 || $var[$key] == '0') {
				return false;
			}
		}

		return true;

	}

	public static function easyCURL( $params ) {

		//$params = [
		//	'url' => 'string',
		//	'fields' => 'array',
		//	'header' => 'array',
		//	'authentication' => [ 'user'=>'string', 'password'=>'string'],
		//];

		//$fieldCount = 0;
		//$fields_string = '';
		//foreach($params['fields'] as $key=> $value) {
		//	$fields_string .= $key . '=' . urlencode($value) . '&';
		//	$fieldCount++;
		//}
		//rtrim($fields_string, '&');
		if(!isset($params['fields'])) {
			$params['fields'] = [];
		}

		if(is_array($params['fields'])) {
			$fields_string = http_build_query($params['fields']);
		}
		elseif(is_string($params['fields'])) {
			$fields_string = $params['fields'];
		}

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $params['url']);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		if(isset($params['method'])) {
			if(strtolower($params['method'])=='post') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			}
			elseif(strtolower($params['method'])=='get') {
				curl_setopt($ch, CURLOPT_URL, $params['url'].'?'.$fields_string);
			}
			elseif(strtolower($params['method'])=='put') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch, CURLOPT_NOBODY, false);
			}
			elseif(strtolower($params['method'])=='delete') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			}
		}
		//assume post for legacy
		else {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		}


		if(isset($params['header'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}
		else {
			curl_setopt($ch, CURLOPT_HEADER, false);
		}

		if(isset($params['authentication'])) {
			if(!isset($params['authentication']['basic']) || $params['authentication']['basic']!=true) {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			}
			curl_setopt($ch, CURLOPT_USERPWD, $params['authentication']['user'].':'.$params['authentication']['password']);
		}

		if(isset($params['useragent'])) {
			curl_setopt($ch,CURLOPT_USERAGENT, $params['useragent']);
		}

		//execute post
		$result = curl_exec($ch);

		//capture any errors from curl
		$curl_errno = curl_errno($ch);

		if($_SESSION[AS_APP]['testing']) {
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($curl_errno) {
				error_log('easyCURL error '.$curl_errno.' (http status '.$httpCode.') on url '.$params['url']);
			}
			else {
				error_log('easyCURL success (http status '.$httpCode.') to url '.$params['url']);
			}
		}

		//close connection
		curl_close($ch);

		return $result;

	}

	public static function everbridgeCall( $params ) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $params['url']);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if(isset($params['type'])) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		}
		else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $params['fields']);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);

		curl_setopt($ch, CURLOPT_USERPWD, $params['authentication']['user'].':'.$params['authentication']['password']);


		$result = curl_exec($ch);

		$curl_errno = curl_errno($ch);

		if($curl_errno) {
			error_log('everbridge error '.$curl_errno.' on url '.$params['url']);
		}
		else {
			error_log('everbridge success to url '.$params['url']);
		}

		curl_close($ch);

		return $result;
	}

	public static function httpStatusCodeText($code=null) {
		if($code===null) {
			$code = http_response_code();
		}

		switch ($code) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version not supported'; break;
			default:
				$text = 'Unknown http status code "' . htmlentities($code) . '"';
			break;
		}

		return $text;
	}


}
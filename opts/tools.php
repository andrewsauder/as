<?php

class tools {


	//This function is used to reduce SQL injection risk - For Microsoft SQL Server
	public static function sql($value) {
		if(is_array($value)) {
			foreach($value as $k=> $v) {
				$value[$k] = tools::sql($v);
			}
			return $value;
		}
		else {
			if($value === NULL) {
				return NULL;
			}
			//escapse single quotes - needed for MS-SQL
			$singQuotePattern = "'";
			$singQuoteReplace = "''";
			return stripslashes(str_replace($singQuotePattern, $singQuoteReplace, $value));
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
			$fields_string .= $key . '=' . $value . '&';
		}
		rtrim($fields_string, '&');

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

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

}
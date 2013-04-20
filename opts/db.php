<?php

class db {

	private $database, $connect;


	function __construct($params = array()) {
		if(!isset($_SESSION[AS_APP]['testing'])) {
			ASsessionController::setTesting();
		}

		if(isset($params['database'])) {
			$this->database = $params['database'];
		}
		else {
			$this->database = 'insight';
		}

		$this->defineConnectionSettings();
	}


	/**
	 * read
	 *
	 * Fetch the results of a query and returns a numerical array of the results
	 *
	 * @param $query (string) the SQL statment to run
	 *
	 * @return array numerical array of the rows returned from the query
	 */
	public function read($query) {

		$odbc = $this->odbcConnect();

		$sql = odbc_exec($odbc, $query) or die(odbc_errormsg() . var_dump(debug_backtrace()));

		$result = array();
		while($row = odbc_fetch_array($sql)) {
			$result[] = $row;
		}

		odbc_close($odbc);

		return $result;
	}


	/**
	 * readOneRow
	 *
	 * Fetch the results of a query and return the last or only row of data
	 *
	 * @param $query (string) the SQL statment to run
	 *
	 * @return array associative array of the columns selected for the last or only row of data
	 */
	public function readOneRow($query) {

		$odbc = $this->odbcConnect();

		$sql = odbc_exec($odbc, $query) or die(odbc_errormsg() . var_dump(debug_backtrace()));

		$result = array();
		while($row = odbc_fetch_array($sql)) {
			foreach($row as $k=> $v) {
				if(strlen($k) == 31) {
					echo $k . '<br>';
				}
			}
			$result = $row;
		}

		odbc_close($odbc);

		return $result;
	}


	/**
	 * write
	 *
	 * Update, insert, or delete a query
	 *
	 * @param $query (string) the SQL statment to run
	 *
	 * @return boolean true
	 */
	public function write($query) {

		$odbc = $this->odbcConnect(false);

		$sql = odbc_exec($odbc, $query) or die(odbc_errormsg() . var_dump(debug_backtrace()));

		odbc_close($odbc);

		return true;
	}


	public function cleanValue($value, $return = "NULL") {
		if($value == "" && $return != "NULL") {
			return 0;
		}
		elseif($value == "" && $return == "NULL") {
			return "NULL";
		}
		else {
			return tools::sql($value);
		}
	}


	public function updateSort($post, $tableName, $postKey = 'sorter') {
		$ids = array();

		$q = "UPDATE
					" . tools::sql($tableName) . "
				SET [sort] = CASE id ";

		foreach($post[$postKey] as $k=> $v) {
			$q .= "
					WHEN " . tools::sql($v) . " THEN " . tools::sql($k) . "
				  ";
			$ids[] = $v;
		}

		$q .= " END
				WHERE id IN (" . implode($ids, ',') . ")";
		//die($q);

		$this->write($q);
	}


	private function defineConnectionSettings() {
		if($_SESSION[AS_APP]['testing'] && $_SESSION[AS_APP]['localtesting']) {
			$environment = $_SESSION['AS']['config']['environment']['local']['srv'];
		}
		elseif($_SESSION[AS_APP]['testing'] && !$_SESSION[AS_APP]['localtesting']) {
			$environment = $_SESSION['AS']['config']['environment']['dev']['srv'];
		}
		else {
			$environment = $_SESSION['AS']['config']['environment']['prod']['srv'];
		}
		
		foreach($environment as $env) {
			if($_SERVER['SERVER_NAME']==$env['server_name']) {
				$this->connect = array(
					'read'=>array(
						'server'=>$env['db_connector']['server'],
						'driver'=>$env['db_connector']['driver'],
						'db'=>$env['db_connector']['db'],
						'user'=>$env['db_connector']['read']['user'],
						'pass'=>$env['db_connector']['read']['pass']
					),
					'write'=>array(
						'server'=>$env['db_connector']['server'],
						'driver'=>$env['db_connector']['driver'],
						'db'=>$env['db_connector']['db'],
						'user'=>$env['db_connector']['write']['user'],
						'pass'=>$env['db_connector']['write']['pass']
					)
				);
			}
		}


	}


	private function odbcConnect($read = true) {

		$key = 'write';
		if($read) {
			$key = 'read';
		}

		if(isset($this->connect['read']['server'])) {
			if($this->connect[$key]['driver'] == '{SQL Server}') {
				$odbc = odbc_connect("Driver=" . $this->connect[$key]['driver'] . ";
									   Server=" . $this->connect[$key]['server'] . ";
									   Database=" . $this->connect[$key]['db'] . ";
									   UID=" . $this->connect[$key]['user'] . ";
									   PWD=" . $this->connect[$key]['pass'], $this->connect[$key]['user'], $this->connect[$key]['pass']
				);
			}
			elseif($this->connect[$key]['driver'] == '{Pervasive ODBC Client Interface}') {
				$odbc = odbc_connect("Driver=" . $this->connect[$key]['driver'] . ";
									   ServerName=" . $this->connect[$key]['server'] . ";
									   ServerDSN=" . $this->connect[$key]['db'] . ";
									   UID=" . $this->connect[$key]['user'] . ";
									   PWD=" . $this->connect[$key]['pass'], $this->connect[$key]['user'], $this->connect[$key]['pass']
				);
			}
		}
		else {
			$odbc = odbc_connect($this->connect[$key]['dsn'], $this->connect[$key]['user'], $this->connect[$key]['pass']) or die(odbc_errormsg() . var_dump(debug_backtrace()));
		}

		if($odbc === null) {
			//die('here');
		}


		return $odbc;
	}

}
<?php


class db2 {

	private string $database           = '';

	private array  $connectionSettings = [];

	private string $readUsername       = '';

	private string $readPassword       = '';

	private string $writeUsername      = '';

	private string $writePassword      = '';

	private ?\PDO  $readPdo            = null;

	private ?\PDO  $writePdo           = null;


	/**
	 * @throws \LogicException
	 */
	function __construct( $params = [] ) {
		//make sure testing mode has been set (can be unset when running as cli)
		if( !isset( $_SESSION[ AS_APP ][ 'testing' ] ) ) {
			ASsessionController::setTesting();
		}

		//set database we're reading from
		if( isset( $params[ 'database' ] ) ) {
			$this->database = $params[ 'database' ] ?? '';
		}
		else {
			$this->database = $_SESSION[ AS_APP ][ 'environment' ][ 'default_db' ] ?? '';
		}

		if( $this->database === '' ) {
			throw new \LogicException( 'No database could be found. Verify environment is correctly defined in var/config.xml.' );
		}

		//DEFINE CONNECTION SETTINGS
		$env = $_SESSION[ AS_APP ][ 'environment' ];

		//find db connector from config.xml for the selected database
		$dbConnector = null;
		//if there is only one connector, it is not set as an array
		if( !isset( $env[ 'db_connector' ][ 0 ] ) ) {
			$env[ 'db_connector' ] = [ $env[ 'db_connector' ] ];
		}
		foreach( $env[ 'db_connector' ] as $envDbConnector ) {
			$dbNames = [
				$envDbConnector[ 'db' ]
			];
			if( isset( $envDbConnector[ 'alias' ] ) ) {
				if( is_array( $envDbConnector[ 'alias' ] ) ) {
					$dbNames = array_merge( $dbNames, $envDbConnector[ 'alias' ] );
				}
				else {
					$dbNames[] = $envDbConnector[ 'alias' ];
				}
			}

			//this connector matches the selected database
			if( in_array( $this->database, $dbNames ) ) {
				$dbConnector = $envDbConnector;
			}
		}

		if( $dbConnector === null ) {
			throw new \LogicException( $this->database . ' could not be found in the environment db connectors. Verify it is defined correctly in var/config.xml' );
		}

		$this->connectionSettings = [
			'dsn'       => $dbConnector[ 'dsn' ] ?? '',
			'server'    => $dbConnector[ 'server' ] ?? '',
			'driver'    => $dbConnector[ 'driver' ] ?? '',
			'pdodriver' => $dbConnector[ 'pdodriver' ] ?? '',
			'db'        => $dbConnector[ 'db' ] ?? ''
		];

		$this->readUsername = $dbConnector[ 'read' ][ 'user' ] ?? '';
		$this->readPassword = $dbConnector[ 'read' ][ 'pass' ] ?? '';

		$this->writeUsername = $dbConnector[ 'write' ][ 'user' ] ?? '';
		$this->writePassword = $dbConnector[ 'write' ][ 'pass' ] ?? '';
	}


	/**
	 * read
	 * Fetch the results of a query and returns a numerical array of the results
	 *
	 * @param  string   $query      The SQL statement to run
	 * @param  array    $params     (optional)  Associative array of columnName=>value to pass into query
	 * @param  ?string  $className  (optional) to retrieve each row as a specific class, pass the fully qualified class name. Excluding a class name will return an associative array for each row of data.
	 *
	 * @return array numerical array of the rows returned from the query
	 * @throws \PDOException
	 */
	public function read( string $query, array $params = [], ?string $className = null ) : array {
		$pdo = $this->pdo();

		//run the query
		if( count( $params ) > 0 ) {
			//prepare query
			$sth = $pdo->prepare( $query );

			//execute query
			$sth->execute( $params );
		}
		else {
			$sth = $pdo->query( $query );
		}

		//get results of query
		if( !isset( $className ) ) {
			$result = $sth->fetchAll( PDO::FETCH_ASSOC );
		}
		else {
			$result = $sth->fetchAll( PDO::FETCH_CLASS, $className );
		}

		return $result;
	}


	/**
	 * readOneColumn
	 * Fetch the results of a query and returns a numerical array of the first column
	 *
	 * @param  string  $query   The SQL statement to run
	 * @param  array   $params  (optional) Associative array of columnName=>value to pass into query
	 *
	 * @return array numerical array of the first column of each row returned from the query
	 * @throws \PDOException
	 */
	public function readOneColumn( string $query, array $params = [] ) : array {
		$pdo = $this->pdo();

		//run the query
		if( count( $params ) > 0 ) {
			//prepare query
			$sth = $pdo->prepare( $query );

			//execute query
			$sth->execute( $params );
		}
		else {
			$sth = $pdo->query( $query );
		}

		return $sth->fetchAll( PDO::FETCH_COLUMN, 0 );
	}


	/**
	 * readOneRow
	 * Fetch the results of a query and return the last or only row of data
	 *
	 * @param  string  $query      The SQL statement to run
	 * @param  array   $params     (optional) Associative array of columnName=>value to pass into query
	 * @param  ?string  $className  (optional) to retrieve each row as a specific class, pass the fully qualified class name. Excluding a class name will return an associative array for each row of data.
	 *
	 * @return mixed associative array of the columns selected for the last or only row of data OR specific class type if class name is provided
	 *
	 * @throws \PDOException
	 */
	public function readOneRow( string $query, array $params = [], ?string $className = null ) : mixed {
		$pdo = $this->pdo();

		//run the query
		if( count( $params ) > 0 ) {
			//prepare query
			$sth = $pdo->prepare( $query );

			//execute query
			$sth->execute( $params );
		}
		else {
			$sth = $pdo->query( $query );
		}

		//get results of query
		//get results of query
		if( !isset( $className ) ) {
			$result = $sth->fetch( PDO::FETCH_ASSOC );
		}
		else {
			$sth->setFetchMode( PDO::FETCH_CLASS, $className );
			$result = $sth->fetch();
		}

		return $result;
	}


	/**
	 * write
	 * Update, insert, or delete a query
	 *
	 * @param $query  (string) the SQL statement to run
	 *
	 * @throws \PDOException
	 */
	public function write( string $query, array $params = [] ) : string|int|bool|\PDOStatement {
		//will be an object reference to $this->writePdo if  the write is inside a transaction
		$pdo = $this->pdo( false );

		//run the query
		if( count( $params ) > 0 ) {
			//prepare query
			$sth = $pdo->prepare( $query );

			//execute query
			$sth->execute( $params );
		}
		else {
			$sth = $pdo->query( $query );
		}

		//if the query failed, there is not going to be an id to look at
		if(!$sth) {
			return $sth;
		}

		//insert statements return the primary key
		try {
			return $pdo->lastInsertId();
		}
		catch(\PDOException $e) {
			return $sth;
		}

	}


	/**
	 * @return bool
	 */
	public function beginTransaction(): bool {
		try {
			$this->writePdo = $this->pdo( false );
			return $this->writePdo->beginTransaction();
		}
		catch(\PDOException $e) {
			return false;
		}
	}


	/**
	 * @return bool
	 * @throws \PDOException
	 */
	public function commitTransaction(): bool {
		if( $this->writePdo===null || !$this->writePdo->inTransaction() ) {
			throw new \PDOException('No transaction is open to commit');
		}

		$committed = false;
		try {
			$committed = $this->writePdo->commit();
		}
		catch( \PDOException $e ) {
			if( $this->writePdo->inTransaction()) {
				$this->writePdo->rollBack();
			}
		}

		$this->writePdo = null;

		return $committed;
	}


	/**
	 * @return bool
	 * @throws \PDOException
	 */
	public function rollbackTransaction(): bool {
		$rolledBack = true;
		if( $this->writePdo instanceof \PDO && $this->writePdo->inTransaction()) {
			$rolledBack = $this->writePdo->rollBack();
		}

		$this->writePdo = null;

		return $rolledBack;
	}


	/**
	 * @param  bool  $readOnly
	 *
	 * @return \PDO
	 *
	 * @throws \LogicException
	 * @throws \PDOException
	 */
	private function pdo( bool $readOnly = true ) : \PDO {
		//return existing connection
		if( $readOnly && $this->readPdo instanceof \PDO ) {
			return $this->readPdo;
		}
		elseif( !$readOnly && $this->writePdo instanceof \PDO ) {
			return $this->writePdo;
		}

		//create connection
		$username = $this->readUsername;
		$password = $this->readPassword;
		if( !$readOnly ) {
			$username = $this->writeUsername;
			$password = $this->writePassword;
		}

		$pdoOptions = [
			PDO::ATTR_TIMEOUT => 8,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];

		if( $this->connectionSettings[ 'pdodriver' ] == 'sqlsrv' ) {
			$pdo = new PDO( "sqlsrv:Server=" . $this->connectionSettings[ 'server' ] . ";Database=" . $this->connectionSettings[ 'db' ].";TrustServerCertificate=1", $username, $password );
			$pdo->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 10 );
		}

		elseif( $this->connectionSettings[ 'pdodriver' ] == 'mysql' ) {
			$pdo = new PDO( 'mysql:host=' . $this->connectionSettings[ 'server' ] . ';
							dbname=' . $this->connectionSettings[ 'db' ], $username, $password, $pdoOptions );
		}

		elseif( $this->connectionSettings[ 'pdodriver' ] == 'odbc' ) {
			if( isset( $this->connectionSettings[ 'dsn' ] ) ) {
				$pdo = new PDO( "odbc:DSN=" . $this->connectionSettings[ 'dsn' ], $username, $password, $pdoOptions );
			}

			//dBASE, Access (file based)
			elseif( strpos( strtolower( $this->connectionSettings[ 'driver' ] ), 'dbase' ) !== false || strpos( strtolower( $this->connectionSettings[ 'driver' ] ), 'access' ) !== false ) {
				$pdo = new PDO( "odbc:Driver=" . $this->connectionSettings[ 'driver' ] . ";
									Dbq=" . $this->connectionSettings[ 'server' ] . ";
									UID=" . $username . ";", $username, $password, $pdoOptions );
			}

			//pervasive 64 bit
			elseif( $this->connectionSettings[ 'driver' ] == '{Pervasive ODBC Interface}' ) {
				$connStr = "odbc:Driver=" . $this->connectionSettings[ 'driver' ] . ";
									ServerName=" . $this->connectionSettings[ 'server' ] . ";
									Dbq=" . $this->connectionSettings[ 'db' ] . ";
									UID=" . $username . ";
									PWD=" . $password . ";";
				$pdo     = new PDO( $connStr, $username, $password, $pdoOptions );
			}

			//pervasive 32 bit
			else {
				$pdo = new PDO( "odbc:Driver=" . $this->connectionSettings[ 'driver' ] . ";
									ServerName=" . $this->connectionSettings[ 'server' ] . ";
									ServerDSN=" . $this->connectionSettings[ 'db' ] . ";
									UID=" . $username . ";
									PWD=" . $password . ";", $username, $password, $pdoOptions );
			}
		}

		if( !isset( $pdo ) ) {
			throw new LogicException( 'DB class cannot create a PDO connection using the environment dbconnector for ' . $this->database . '. Verify the pdodriver and driver (if needed) match connection properties DB class can handle.' );
		}

		return $pdo;
	}

}
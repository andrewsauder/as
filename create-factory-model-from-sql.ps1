Write-host "SQL Server" -ForegroundColor Yellow 
$SqlServer = Read-Host "I.E. it-as\sqlexpress" 

Write-host "SQL Database" -ForegroundColor Yellow 
$SqlDatabase = Read-Host "I.E. W_Timeclock" 

Write-host "SQL Table Name" -ForegroundColor Yellow 
$SqlTable = Read-Host "I.E. customer" 

$modelFile = '../models/'+$SqlTable+'.php';
$factoryFolder = '../models/'+$SqlTable+'';
$factoryFile = '../models/'+$SqlTable+'/factory.php';

$SqlConnectionString = 'Data Source={0};Initial Catalog={1};Integrated Security=SSPI' -f $SqlServer, $SqlDatabase;
$SqlQuery = "SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = '$SqlTable';";

$SqlConnection = New-Object -TypeName System.Data.SqlClient.SqlConnection -ArgumentList $SqlConnectionString;
$SqlCommand = $SqlConnection.CreateCommand();
$SqlCommand.CommandText = $SqlQuery;

$SqlConnection.Open();
$SqlDataReader = $SqlCommand.ExecuteReader();

$columns = New-Object System.Collections.Specialized.OrderedDictionary



#MODEL
    #Open Class
        "<?php`nnamespace model;`n`nuse \db;`nuse \JsonSerializable;`n`nclass $SqlTable implements JsonSerializable {" | Set-Content $modelFile
    
        "`n`t/** @var \db */" | Add-Content $modelFile
        Start-Sleep -Milliseconds 300
        "`tprivate `$db;" | Add-Content $modelFile
        Start-Sleep -Milliseconds 300


    #Add public variables
        while ($SqlDataReader.Read()) {
    
            #set type in PHPDoc

            $dataType = $SqlDataReader['DATA_TYPE'];
            $defaultValue = "";
            if( $dataType -eq 'bit' ){ 
                $dataType = 'bool';
                $defaultValue = " = false";
            }
            elseif( $dataType -eq 'varchar' ) {
                $dataType = 'string';
                $defaultValue = " = ''";
            }
            elseif( $dataType -eq 'char' ) {
                $dataType = 'string';
                $defaultValue = " = ''";
            }
            elseif( $dataType -eq 'decimal' ) {
                $dataType = 'float';
                $defaultValue = "";
            }
            elseif( $dataType -eq 'money' ) {
                $dataType = 'float';
                $defaultValue = "";
            }
            elseif( $dataType -eq 'real' ) {
                $dataType = 'float';
                $defaultValue = "";
            }
            elseif( $dataType -eq 'smallint' ) {
                $dataType = 'int';
                $defaultValue = "";
            }
            elseif( ($dataType -eq 'date') -Or ($dataType -eq "datetime")) {
                $dataType = '\DateTimeImmutable';
                $defaultValue = "";
            }
    
            "`n`t/** @var "+$dataType+" */" | Add-Content $modelFile
            Start-Sleep -Milliseconds 300
            "`tpublic $"+$SqlDataReader['COLUMN_NAME']+$defaultValue+";" | Add-Content $modelFile
            Start-Sleep -Milliseconds 300
            $columns.Add($SqlDataReader['COLUMN_NAME'], $dataType );

        }



    #Constructor
    "


	    public function __construct(  ) {

		    `$this->db = new db();

    " | Add-Content $modelFile
    Start-Sleep -Milliseconds 300

    foreach($column in $columns.GetEnumerator()) {
        $value = "("+$column.Value+") `$this->"+$column.Name+"";

        if( $column.Value -eq "string") {
            $value = "trim(`$this->"+$column.Name+")";
        }
        elseif( $column.Value -eq "\DateTimeImmutable") {
            #$value = "`$row['"+$column.Name+"'] == null ? null : new \DateTimeImmutable( `$row['"+$column.Name+"'] )";
            "`t`t`tif( isset(`$this->"+$column.Name+") && !`$this->"+$column.Name+" instanceof \DateTimeImmutable ) {"  | Add-Content $modelFile
                "`t`t`t`t`$this->"+$column.Name+" = new \DateTimeImmutable(`$this->"+$column.Name+");"  | Add-Content $modelFile
            "`t`t`t}"  | Add-Content $modelFile
        }

        if( $column.Value -ne "\DateTimeImmutable") {
            "`t`t`t`$this->"+$column.Name+" = isset(`$this->"+$column.Name+") ? "+$value+" : null;"  | Add-Content $modelFile
        }

        Start-Sleep -Milliseconds 300
    }


    "

	    }" | Add-Content $modelFile
    Start-Sleep -Milliseconds 300





    #Update
    "


	    /**
	     * Update
	     *
	     * Save changes made to the object
	     *
	     * @return boolean
	     */
	    public function update() {

		    `$params = [" | Add-Content $modelFile

    $params = "";
    foreach($column in $columns.GetEnumerator()) {

        if( $column.Value -eq "\DateTimeImmutable") {
            "`t`t`t'"+$column.Name+"' => (`$this->"+$column.Name+" instanceof \DateTimeImmutable) ? `$this->"+$column.Name+"->format('Y-m-d') : `$this->"+$column.Name+","  | Add-Content $modelFile
        }
        else {
            "`t`t`t'"+$column.Name+"' => `$this->"+$column.Name+","  | Add-Content $modelFile
        }
        Start-Sleep -Milliseconds 300

        if( $column.Name -ne 'id' ) {
            if($params -ne "") {
                $params+=", ";
            }

            $params += "["+$column.Name+"]=:"+$column.Name;
        }
    }

    "
		    ];

		    `$q = `"UPDATE [$SqlTable] SET $params WHERE [id]=:id;`";

		    `$this->db->write( `$q, `$params );

		    return true;

	    }" | Add-Content $modelFile
    Start-Sleep -Milliseconds 300



    #Insert
    "


	    /**
	     * Create New
	     *
	     * Insert new object into the database
	     *
	     * @return boolean
	     */
	    public function insert() {

		    `$params = [" | Add-Content $modelFile

    $insertColumnParams = "";
    $insertValueParams = "";
    foreach($column in $columns.GetEnumerator()) {

        if( $column.Name -ne 'id' ) {
    
            if( $column.Value -eq "\DateTimeImmutable") {
                "`t`t`t'"+$column.Name+"' => (`$this->"+$column.Name+" instanceof \DateTimeImmutable) ? `$this->"+$column.Name+"->format('Y-m-d') : `$this->"+$column.Name+","  | Add-Content $modelFile
            }
            else {
                "`t`t`t'"+$column.Name+"' => `$this->"+$column.Name+","  | Add-Content $modelFile
            }
            #"`t`t`t'"+$column.Name+"' => `$this->"+$column.Name+","  | Add-Content $modelFile
            Start-Sleep -Milliseconds 300

            if($insertColumnParams -ne "") {
                $insertColumnParams+=", ";
            }
            if($insertValueParams -ne "") {
                $insertValueParams+=", ";
            }

            $insertColumnParams += "["+$column.Name+"]";
            $insertValueParams += ":"+$column.Name;
        }
    }

    "
		    ];

		    `$q = `"INSERT INTO [$SqlTable] ($insertColumnParams) VALUES ($insertValueParams);`";

		    `$this->id = `$this->db->write( `$q, `$params );

		    return true;

	    }" | Add-Content $modelFile
    Start-Sleep -Milliseconds 300


    #delete
    "
	    /**
	     * Delete
	     *
	     * Delete object from the database
	     *
	     * @return boolean
	     */
	    public function delete() {

		    `$params = [
			    'id'	 => `$this->id,
		    ];

		    `$q = `"DELETE FROM [$SqlTable] WHERE [id]=:id;`";

		    `$this->db->write( `$q, `$params );

		    return true;

    }
    " | Add-Content $modelFile
    Start-Sleep -Milliseconds 300


    #Init
    "


	    /**
	     * Initialize from outside object
	     *
	     * @return void
	     */
	    public function init( $"+$SqlTable+"Object ) {


		    `$keys = get_object_vars( $"+$SqlTable+"Object );

		    foreach ( `$keys as `$key => `$value ) {
			    `$this->`$key = `$value;
		    }

    " | Add-Content $modelFile
    Start-Sleep -Milliseconds 300

    foreach($column in $columns.GetEnumerator()) {
    
        if( $column.Value -eq "\DateTimeImmutable") {
            "`t`t`t`$this->"+$column.Name+" = !empty( `$this->"+$column.Name+" ) ? new \DateTimeImmutable( `$this->"+$column.Name+" ) : null;"  | Add-Content $modelFile
            Start-Sleep -Milliseconds 300
        }    
        #"`t`t`t`$this->"+$column.Name+" = $"+$SqlTable+"Object->"+$column.Name+";" | Add-Content $modelFile

    }

    "`t}" | Add-Content $modelFile



    #JsonSerialize
    "


	    public function jsonSerialize() {
    " | Add-Content $modelFile
    Start-Sleep -Milliseconds 300

    foreach($column in $columns.GetEnumerator()) {

        if( $column.Value -eq "\DateTimeImmutable") {
            "`t`t`t`$this->"+$column.Name+" = is_object( `$this->"+$column.Name+" ) ? `$this->"+$column.Name+"->format( 'Y-m-d H:i:s' ) : null;"  | Add-Content $modelFile
        }

    }
    "`t`t`treturn `$this;" | Add-Content $modelFile
    Start-Sleep -Milliseconds 300

    "	}"  | Add-Content $modelFile
    Start-Sleep -Milliseconds 300



    #Close class
    "`r`n"  | Add-Content $modelFile
    "}"  | Add-Content $modelFile



#FACTORY
    New-Item -ItemType Directory -Force -Path $factoryFolder


    
    #Open Class
        "<?php`nnamespace model\$SqlTable;`n`n`nclass factory {" | Set-Content $factoryFile
    
    #One
        "	/**
	 * @param \int `$id
	 *
	 * @return \model\$SqlTable
	 */
	public static function one( int `$id ) {

		`$db = new db();

		`$q = `"SELECT * FROM [admin] WHERE id=:id;`";

		`$$SqlTable = `$db->readOneRow(`$q, [ 'id' => `$id ], '\model\$SqlTable');

		return `$$SqlTable;

	}" | Add-Content $factoryFile
        
    #many
        "	/**
	 * Get multiple $SqlTable
	 *
	 * Pass ids to select or exclude ids to select all
	 *
	 * @param int[] `$ids
	 *
	 * @return \model\$SqlTable[]
	 */
	public static function many( `$ids = [] ) {

		`$db = new db();

		`$params = [];

		`$where = `"`";
		if( count(`$ids)>0 ) {

			`$inKeys = [];

			foreach( `$ids as `$i => `$id ) {
				`$key                 = 'id' . `$i;
				`$params[ 'id' . `$i ] = `$id;
				`$inKeys[]            = ':' . `$key;
			}
			`$where = `"WHERE [id] IN (`" . implode(',', `$inKeys) . `")`";
		}

		`$q = `"SELECT * FROM [$SqlTable] `" . `$where . `";`";

		`$results = `$db->read(`$q, `$params, '\model\$SqlTable');

		return `$results;
	}"  | Add-Content $factoryFile

    #Close class
        "`r`n"  | Add-Content $factoryFile
        "}"  | Add-Content $factoryFile


$SqlConnection.Close();
$SqlConnection.Dispose();
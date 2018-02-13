Write-host "SQL Server" -ForegroundColor Yellow 
$SqlServer = Read-Host "I.E. it-as\sqlexpress" 

Write-host "SQL Database" -ForegroundColor Yellow 
$SqlDatabase = Read-Host "I.E. W_Timeclock" 

Write-host "SQL Table Name" -ForegroundColor Yellow 
$SqlTable = Read-Host "I.E. customer" 

$modelFile = '../models/'+$SqlTable+'.php';

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

#Open Class
    "<?php`nnamespace model;`n`nuse \db;`nuse \JsonSerializable;`n`nclass $SqlTable implements JsonSerializable {" | Set-Content $modelFile
    
    "`n`t/** @var \db */" | Add-Content $modelFile
    "`tprivate `$db;" | Add-Content $modelFile


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
        elseif( ($dataType -eq 'date') -Or ($dataType -eq "datetime")) {
            $dataType = '\DateTimeImmutable';
            $defaultValue = "";
        }
    
        "`n`t/** @var "+$dataType+" */" | Add-Content $modelFile
        "`tpublic $"+$SqlDataReader['COLUMN_NAME']+$defaultValue+";" | Add-Content $modelFile

        $columns.Add($SqlDataReader['COLUMN_NAME'], $dataType );

    }



#Constructor
"


	/**
	 * Get
	 *
	 * Initialize and set data by passing id. Exclude id to initialize empty.
	 *
	 * @param int `$id Id of court to get
	 */
	public function __construct( `$id=null ) {

		`$this->db = new db();

		if( `$id!=null ) {

			`$q = `"SELECT * FROM [$SqlTable] WHERE id=:id;`";

			`$row = `$this->db->readOneRow( `$q, [ 'id'=>`$id ]);
" | Add-Content $modelFile

foreach($column in $columns.GetEnumerator()) {
    $value = "("+$column.Value+") `$row['"+$column.Name+"']";

    if( $column.Value -eq "string") {
        $value = "trim(`$row['"+$column.Name+"'])";
    }
    elseif( $column.Value -eq "\DateTimeImmutable") {
        $value = "`$row['"+$column.Name+"'] == null ? null : new \DateTimeImmutable( `$row['"+$column.Name+"'] )";
    }

    "`t`t`t`$this->"+$column.Name+" = "+$value+";"  | Add-Content $modelFile
}


"
		}

	}" | Add-Content $modelFile





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

    "`t`t`t'"+$column.Name+"' => `$this->"+$column.Name+","  | Add-Content $modelFile

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
        "`t`t`t'"+$column.Name+"' => `$this->"+$column.Name+","  | Add-Content $modelFile

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

foreach($column in $columns.GetEnumerator()) {
    
    if( $column.Value -eq "\DateTimeImmutable") {
        "`t`t`t`$this->"+$column.Name+" = !empty( `$this->"+$column.Name+" ) ? new \DateTimeImmutable( `$this->"+$column.Name+" ) : null;"  | Add-Content $modelFile
    }    
    #"`t`t`t`$this->"+$column.Name+" = $"+$SqlTable+"Object->"+$column.Name+";" | Add-Content $modelFile

}

"`t}" | Add-Content $modelFile



#JsonSerialize
"


	public function jsonSerialize() {
" | Add-Content $modelFile

foreach($column in $columns.GetEnumerator()) {

    if( $column.Value -eq "\DateTimeImmutable") {
        "`t`t`t`$this->"+$column.Name+" = is_object( `$this->"+$column.Name+" ) ? `$this->"+$column.Name+"->format( 'Y-m-d H:i:s' ) : null;"  | Add-Content $modelFile
    }

}
"`t`t`treturn `$this;" | Add-Content $modelFile

"	}"  | Add-Content $modelFile



#Close class
"`r`n"  | Add-Content $modelFile
"}"  | Add-Content $modelFile



#build getter method
"	/**
	 * Get multiple $SqlTable
	 *
	 * Pass ids to select or exclude ids to select all
	 *
	 * @param int[] `$ids Ids of the items to select
	 *
	 * @return \model\$SqlTable[]
	 */
	public static function "+$SqlTable+"s( `$ids = [] ) {

		`$db = new db();

		`$rows = [];

		if ( count( `$ids ) == 0 ) {

			`$q = `"SELECT id FROM [$SqlTable];`";

			`$rowsRaw = `$db->read( `$q );

			foreach( `$rowsRaw as `$rowRaw ) {
				`$ids[] = `$rowRaw['id'];
			}

		}
		
		foreach( `$ids as `$id ) {
			`$rows[] = new \model\$SqlTable( `$id );
		}

		return `$rows; 
    }"  | Add-Content $modelFile



$SqlConnection.Close();
$SqlConnection.Dispose();
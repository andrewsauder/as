Param(
    [Parameter()]
    [string]
    $EnvServerName,

    [Parameter()]
    [string]
    $EnvDB
)

# Returns the text of the child element at $path under $node, or '' when it is missing.
function Get-XmlText( $node, [string]$path ) {
    if( $null -eq $node ) { return '' }
    $child = $node.SelectSingleNode( $path )
    if( $null -eq $child ) { return '' }
    return $child.InnerText.Trim()
}

# Returns the first child of $parent named $name whose $idName child has the text $id.
function Find-XmlEntry( $parent, [string]$name, [string]$idName, [string]$id ) {
    if( $null -eq $parent ) { return $null }
    foreach( $entry in $parent.SelectNodes( $name ) ) {
        if( (Get-XmlText $entry $idName) -eq $id ) { return $entry }
    }
    return $null
}

# A value in config.local.xml replaces the value in config.xml.
function Get-MergedText( $baseNode, $localNode, [string]$path ) {
    $localValue = Get-XmlText $localNode $path
    if( $localValue -ne '' ) { return $localValue }
    return Get-XmlText $baseNode $path
}

if( -not $EnvServerName ) {
    Write-host "AS Environment Server Name" -ForegroundColor Yellow
    $EnvServerName = Read-Host "I.E. apps.garrettcounty.local"
}

if( -not $EnvDB ) {
    Write-host "DB to create users for" -ForegroundColor Yellow
    $EnvDB = Read-Host "I.E. W_Timeclock"
}

[xml]$config = Get-Content ../var/config.xml

$localEnvironment = $null
if( Test-Path -Path ../var/config.local.xml -PathType Leaf ) {
    try {
        [xml]$localConfig = Get-Content ../var/config.local.xml
    }
    catch {
        # The parser message can quote lines of the file, and this file holds secrets.
        Write-Host "../var/config.local.xml is not valid XML." -ForegroundColor Red
        exit 1
    }
    Write-Host "Reading passwords from ../var/config.local.xml over ../var/config.xml" -ForegroundColor Yellow
    $localEnvironment = $localConfig.SelectSingleNode( '/config/environment/local' )
}

$baseEnvironment = $config.SelectSingleNode( '/config/environment/local' )

foreach( $srv in $baseEnvironment.SelectNodes( 'srv' ) ) {
    if( $EnvServerName -ne (Get-XmlText $srv 'server_name') ) {
        continue
    }

    $localSrv = Find-XmlEntry $localEnvironment 'srv' 'server_name' $EnvServerName

    foreach( $db_connector in $srv.SelectNodes( 'db_connector' ) ) {
        if( $EnvDB -ne (Get-XmlText $db_connector 'db') ) {
            continue
        }

        $localConnector = Find-XmlEntry $localSrv 'db_connector' 'db' $EnvDB

        $db         = Get-XmlText $db_connector 'db'
        $SqlServer  = Get-MergedText $db_connector $localConnector 'server'
        $reader     = Get-MergedText $db_connector $localConnector 'read/user'
        $writer     = Get-MergedText $db_connector $localConnector 'write/user'
        $readerpass = Get-MergedText $db_connector $localConnector 'read/pass'
        $writerpass = Get-MergedText $db_connector $localConnector 'write/pass'

        $missing = @()
        if( $reader -eq '' )     { $missing += 'read/user' }
        if( $writer -eq '' )     { $missing += 'write/user' }
        if( $readerpass -eq '' ) { $missing += 'read/pass' }
        if( $writerpass -eq '' ) { $missing += 'write/pass' }
        if( $missing.Count -gt 0 ) {
            Write-Host "Stopped: <db_connector> for $db on $EnvServerName has no value for: $($missing -join ', '). Set it in ../var/config.local.xml. No SQL ran." -ForegroundColor Red
            exit 1
        }

        Write-Host "Adding users $writer and $reader for $db on $SqlServer"

        $buildQuery = {
            param( $writerpassText, $readerpassText )
            "
                USE [$db]
                DROP USER IF EXISTS [$writer]
                GO
                DROP USER IF EXISTS [$reader]
                GO

                USE [master]

                BEGIN TRY
                     DROP LOGIN [$writer]
                END TRY
                BEGIN CATCH

                END CATCH

                GO

                BEGIN TRY
                     DROP LOGIN [$reader]
                END TRY
                BEGIN CATCH

                END CATCH

                GO
                CREATE LOGIN [$writer] WITH PASSWORD=N'$writerpassText', DEFAULT_DATABASE=[$db], DEFAULT_LANGUAGE=[us_english], CHECK_EXPIRATION=OFF, CHECK_POLICY=OFF
                GO
                CREATE LOGIN [$reader] WITH PASSWORD=N'$readerpassText', DEFAULT_DATABASE=[$db], DEFAULT_LANGUAGE=[us_english], CHECK_EXPIRATION=OFF, CHECK_POLICY=OFF
                GO


                USE [$db]
                GO
                CREATE USER [$writer] FOR LOGIN [$writer]
                GO
                ALTER ROLE [db_datareader] ADD MEMBER [$writer]
                GO
                ALTER ROLE [db_datawriter] ADD MEMBER [$writer]
                GO
                CREATE USER [$reader] FOR LOGIN [$reader]
                GO
                ALTER ROLE [db_datareader] ADD MEMBER [$reader]
                GO
            "
        }

        $SqlQuery = & $buildQuery $writerpass $readerpass

        Write-Host $SqlServer
        Write-Host (& $buildQuery '********' '********')

        #Invoke-Sqlcmd -ServerInstance $SqlServer -Query $SqlQuery
        Invoke-Sqlcmd -Query $SqlQuery
    }
}

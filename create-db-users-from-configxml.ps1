function createUser( $EnvServerName, $srv ) {

    return false
}


Write-host "AS Environment Server Name" -ForegroundColor Yellow
$EnvServerName = Read-Host "I.E. apps.garrettcounty.local"


Write-host "DB to create users for" -ForegroundColor Yellow
$EnvDB = Read-Host "I.E. W_Timeclock"

[xml]$config = Get-Content ../var/config.xml

foreach( $srv in $config.config.environment.local.srv) {
    echo 'create'
    if($EnvServerName -eq $srv.server_name) {

        foreach( $db_connector in $srv.db_connector ) {
             if($db_connector.db -eq $EnvDB) {
                Write-Host "Adding users for "$db_connector.db" on "$db_connector.server

                $db = $db_connector.db
                $reader = $db_connector.read.user
                $writer = $db_connector.write.user
                $readerpass = $db_connector.read.pass
                $writerpass = $db_connector.write.pass
                $SqlServer = $db_connector.server

                $SqlQuery = "
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
                    CREATE LOGIN [$writer] WITH PASSWORD=N'$writerpass', DEFAULT_DATABASE=[W_Timeclock], DEFAULT_LANGUAGE=[us_english], CHECK_EXPIRATION=OFF, CHECK_POLICY=OFF
                    GO
                    CREATE LOGIN [$reader] WITH PASSWORD=N'$readerpass', DEFAULT_DATABASE=[W_Timeclock], DEFAULT_LANGUAGE=[us_english], CHECK_EXPIRATION=OFF, CHECK_POLICY=OFF
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
                ";

                Invoke-Sqlcmd -ServerInstance $SqlServer -Query $SqlQuery

             }
        }


    }
    echo 'created'
}



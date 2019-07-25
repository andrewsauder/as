function makedir( $name ) {
    if(-Not (Test-Path $name)) { 
        mkdir $name
    }
}

Write-host "Is this a Muunnella CMS site?" -ForegroundColor Yellow 
$MuunnellaCMS = Read-Host "( y / n )" 


Switch ($MuunnellaCMS) 
{ 
    Y {
    } 
    N {
        Write-host "Include deprecated dcfront module?" -ForegroundColor Yellow 
        $dcfront = Read-Host "( y / n )" 
    }
} 





#ignore file
    Copy-Item 'C:\Assets\Code\PowerShell Scripts\new-as-git-site\.gitignore' .\
    
    git commit -m "Added submodules" 2>&1 | write-host

    git push 2>&1 | write-host


#create structure
    makedir('www')
    makedir('www/script')
    makedir('www/theme')
    makedir('cache')
    makedir('models')
    makedir('var')
    makedir('var/tmp')
    makedir('var/srv')
    makedir('var/session')
    makedir('var/router')



#add submodules 
    git submodule add https://github.com/andrewsauder/as AS 2>&1 | write-host

    t
    
    Switch ($dcfront) 
     { 
        Y {
           git submodule add https://github.com/andrewsauder/dcfront www/dcfront 2>&1 | write-hos
        }
     } 

    Switch ($MuunnellaCMS) 
     { 
        Y {
            git submodule add https://github.com/andrewsauder/as-app-muunnella-front www/muunnellafront 2>&1 | write-host
            git submodule add https://github.com/andrewsauder/as-app-muunnella app 2>&1 | write-host
            git submodule add https://github.com/andrewsauder/dcfront www/dcfront 2>&1 | write-host
        } 
        N {
            makedir('app')
        } 
        Default {
            makedir('app')
        } 
     } 

    git submodule update --init --recursive 2>&1 | write-host

    git commit -m "Added submodules" 2>&1 | write-host

    git push 2>&1 | write-host


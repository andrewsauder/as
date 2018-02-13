function makedir( $name ) {
    if(-Not (Test-Path $name)) { 
        mkdir $name
    }
}

Write-host "Is this a Muunnella CMS site?" -ForegroundColor Yellow 
$MuunnellaCMS = Read-Host "( y / n )" 




#ignore file
    Copy-Item 'C:\Assets\Code\PowerShell Scripts\new-as-git-site\.gitignore' .\
    
    git commit -m "Added submodules" 2>&1 | write-host

    git push 2>&1 | write-host


#create structure
    makedir('www')
    makedir('cache')
    makedir('var')



#add submodules 
    git submodule add https://github.com/andrewsauder/as AS 2>&1 | write-host

    git submodule add https://github.com/andrewsauder/dcfront www/dcfront 2>&1 | write-host
    

    Switch ($MuunnellaCMS) 
     { 
        Y {
            git submodule add https://github.com/andrewsauder/as-app-muunnella-front www/muunnellafront 2>&1 | write-host
            git submodule add https://github.com/andrewsauder/as-app-muunnella app 2>&1 | write-host
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


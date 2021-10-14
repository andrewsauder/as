function Menu ($object, $prompt) {
    if (!$object) { Throw 'Must provide an object.' }
    $ok = $false
    Write-Host ''
    do {
        if ($prompt) {
            Write-Host $prompt -ForegroundColor Yellow
        }
        for ($i = 0; $i -lt $object.count; $i++) {
            Write-Host $i`. $object[$i]
        }
        Write-Host ''
        $answer = Read-Host
        if ($answer -in 0..($object.count-1)) {
            $object[$answer]
            $ok = $true
        } else {
            Write-Host 'Not an option!' -ForegroundColor Red
            Write-Host ''
        }
    } while (!$ok)
}

Push-Location $PSScriptRoot\..\
Write-Host "Running in directory "$PSScriptRoot\..\ -ForegroundColor Yellow

##GIT
Write-Host "git pull" -ForegroundColor Yellow
git pull

Write-Host "`nMost Recent Tags:" -ForegroundColor Yellow
$recentTags = git tag --sort=version:refname | select -Last 5

$tag = menu -object $recentTags -prompt 'Which tag do you want to check out?'

Write-Host "git checkout tags/$tag" -ForegroundColor Yellow
git checkout tags/$tag

Write-Host "git submodule sync" -ForegroundColor Yellow
git submodule sync

Write-Host "git submodule update" -ForegroundColor Yellow
git submodule update


##ENV
if (Test-Path -Path 'app/config/environment-prod.json' -PathType Leaf) {
	Write-Host "update environment files" -ForegroundColor Yellow
	Copy-Item -Path app/config/environment-prod.json -Destination app/config/environment.json
}

##IIS
if (Test-Path -Path 'www/web-prod.config' -PathType Leaf) {
	Write-Host "update web.config environment file" -ForegroundColor Yellow
	Copy-Item -Path www/web-prod.config -Destination www/web.config
}

##COMPOSER
if (Test-Path -Path 'composer-prod.json' -PathType Leaf) {
	Write-Host "update composer environment file" -ForegroundColor Yellow
	Copy-Item -Path app/config/composer-prod.json -Destination app/config/composer.json
}
if (Test-Path -Path 'composer.json' -PathType Leaf) {
	Write-Host "composer install" -ForegroundColor Yellow
	composer install

	Write-Host "composer update" -ForegroundColor Yellow
	composer update
}


##VERSION
Write-Host "Set version" -ForegroundColor Yellow
$status = git status | Select -first 1
$statusWords = -split $status
$appVersion = $statusWords[-1]
Write-Host "App version: $appVersion" -ForegroundColor Yellow

$jsonBase = @{}
$jsonBase.Add("app",$appVersion)
$jsonBase.Add("inherit",$true)
$jsonBase | ConvertTo-Json | Out-File "version.json"

Pop-Location
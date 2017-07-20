$name = Read-Host 'Module name to create (ex: employee)'

cp $PSScriptRoot\std\ $PSScriptRoot\..\..\app\$name  -recurse

$stringToReplace = "{module}"
$replaceWith = $name



$pathToFileC = $PSScriptRoot+"\..\..\app\$name\controller.php"
$newControllerPath = $PSScriptRoot+"\..\..\app\$name\controller.$name.php"
$newControllerPathTmp = $newControllerPath+'.tmp'


$pathToFileM = $PSScriptRoot+"\..\..\app\$name\model.php"
$newModelPath =  $PSScriptRoot+"\..\..\app\$name\model.$name.php"
$newModelPathTmp = $newModelPath+'.tmp'


$pathToFileV = $PSScriptRoot+"\..\..\app\$name\views\view.php"
$newViewPath =  $PSScriptRoot+"\..\..\app\$name\views\view.$name.php"

Move-Item $pathToFileC $newControllerPathTmp
Move-Item $pathToFileM $newModelPathTmp

Move-Item $pathToFileV $newViewPath

get-content $newControllerPathTmp | % { $_ -replace $stringToReplace, $replaceWith } | set-content $newControllerPath


get-content $newModelPathTmp | % { $_ -replace $stringToReplace, $replaceWith } | set-content $newModelPath

Remove-Item $newControllerPathTmp
Remove-Item $newModelPathTmp

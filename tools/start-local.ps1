$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$php = 'C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe'
$mysqlAdmin = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqladmin.exe'
$mysqlServer = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe'
$mysqlIni = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini'
$apache = 'C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe'
$apacheConf = 'C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\conf\httpd.conf'

try {
    & $mysqlAdmin --host=127.0.0.1 --port=3306 -u root ping | Out-Null
} catch {
    Start-Process -FilePath $mysqlServer -ArgumentList "--defaults-file=$mysqlIni" -WorkingDirectory (Split-Path $mysqlServer)
    Start-Sleep -Seconds 3
}

if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $apache -ArgumentList "-f `"$apacheConf`"" -WorkingDirectory (Split-Path $apache)
    Start-Sleep -Seconds 2
}

& $php "$root\tools\ready_check.php"
Write-Host ''
Write-Host 'Local URLs:'
Write-Host '  http://market.test'
Write-Host '  http://market.test/admin/dashboard'

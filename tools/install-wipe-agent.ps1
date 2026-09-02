# Install AdminOffboard Wipe Agent as Windows Scheduled Task
# Run as Administrator in PowerShell

param(
    [string]$Username = "",
    [string]$ServerUrl = "https://cloud9.pkflink.ru"
)

$ScriptPath = "$PSScriptRoot\wipe-agent.ps1"
$TaskName = "AdminOffboard Wipe Agent"

if (-not (Test-Path $ScriptPath)) {
    Write-Host "Error: wipe-agent.ps1 not found in $PSScriptRoot"
    exit 1
}

if (-not $Username) {
    $Username = Read-Host "Enter Nextcloud username"
}

$securePassword = Read-Host "Enter password for $Username" -AsSecureString
$Password = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
)

# Создать действие - запускать скрипт
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -ExecutionPolicy Bypass -File `"$ScriptPath`" -Username `"$Username`" -Password `"$Password`" -ServerUrl `"$ServerUrl`""

# Триггер - каждые 5 минут
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5)

$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RestartCount 999 -ExecutionTimeLimit (New-TimeSpan -Days 3650)

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Description "Checks AdminOffboard API for wipe signal and deletes Nextcloud files"

Write-Host "✅ Wipe Agent installed!"
Write-Host "Runs every 5 minutes to check for wipe signal."
Write-Host "To uninstall: Unregister-ScheduledTask -TaskName '$TaskName'"

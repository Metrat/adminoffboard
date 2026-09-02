# AdminOffboard Wipe Agent Installer
# Run ONCE to install Task Scheduler job
# Job checks for wipe signal every 5 minutes

param(
    [string]$Username = "",
    [string]$ServerUrl = "https://cloud9.pkflink.ru"
)

if (-not $Username) {
    $Username = $env:USERNAME
}

$TaskName = "AdminOffboard Wipe Agent"
$InstallDir = "$env:ProgramData\AdminOffboard"
$ScriptPath = "$InstallDir\wipe-agent.ps1"

# Создать директорию
if (-not (Test-Path $InstallDir)) {
    New-Item -Path $InstallDir -ItemType Directory -Force | Out-Null
}

# Скачать скрипт
Write-Host "Downloading wipe agent script..."
$response = Invoke-RestMethod -Uri "$ServerUrl/index.php/apps/adminoffboard/api/v1/wipe-agent/download/$Username"
$scriptContent = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($response.data.content))
[System.IO.File]::WriteAllText($ScriptPath, $scriptContent)
Write-Host "Script saved to: $ScriptPath"

# Создать Task Scheduler задачу
Write-Host "Creating Task Scheduler job..."

$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -ExecutionPolicy Bypass -File `"$ScriptPath`" -Username `"$Username`" -ServerUrl `"$ServerUrl`""
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RestartCount 999 -ExecutionTimeLimit (New-TimeSpan -Days 3650) -Hidden

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Description "Checks AdminOffboard API for wipe signal every 5 minutes" | Out-Null

Write-Host ""
Write-Host "✅ Wipe Agent installed successfully!"
Write-Host "Task: $TaskName"
Write-Host "Runs every 5 minutes (hidden)"
Write-Host ""
Write-Host "To uninstall:"
Write-Host "  Unregister-ScheduledTask -TaskName '$TaskName'"
Write-Host "  Remove-Item -Path '$InstallDir' -Recurse -Force"
Write-Host ""
Write-Host "To run manually:"
Write-Host "  Start-ScheduledTask -TaskName '$TaskName'"

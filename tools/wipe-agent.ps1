# AdminOffboard Wipe Agent - One-Time Check
# Task Scheduler runs this every 5 minutes
# Checks for wipe signal, deletes files if received

param(
    [string]$ServerUrl = "https://cloud9.pkflink.ru",
    [string]$Username = "",
    [string]$NextcloudFolder = ""
)

if (-not $NextcloudFolder) {
    $NextcloudFolder = "$env:USERPROFILE\Nextcloud"
}

if (-not $Username) {
    $Username = $env:USERNAME
}

Add-Type -AssemblyName System.Windows.Forms

# Проверить API
try {
    $ApiEndpoint = "$ServerUrl/index.php/apps/adminoffboard/api/v1/wipe-check/$Username"
    $response = Invoke-RestMethod -Uri $ApiEndpoint -Method GET -ErrorAction Stop
    
    if ($response.success -and $response.data.wipe_requested) {
        Write-Host "WIPE SIGNAL RECEIVED!"
        
        # Закрыть Nextcloud
        Get-Process | Where-Object { $_.Name -match "nextcloud|owncloud" } | ForEach-Object {
            $_.Kill()
        }
        Start-Sleep -Seconds 2
        
        # Удалить файлы
        if (Test-Path $NextcloudFolder) {
            Remove-Item -Path $NextcloudFolder -Recurse -Force -Confirm:$false -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 1
            
            if (Test-Path $NextcloudFolder) {
                Get-ChildItem -Path $NextcloudFolder -Force | ForEach-Object {
                    Remove-Item -Path $_.FullName -Recurse -Force -Confirm:$false -ErrorAction SilentlyContinue
                }
            }
            
            Write-Host "Files deleted!"
            
            [System.Windows.Forms.MessageBox]::Show(
                "Nextcloud files have been remotely wiped by administrator.",
                "AdminOffboard - Remote Wipe",
                [System.Windows.Forms.MessageBoxButtons]::OK,
                [System.Windows.Forms.MessageBoxIcon]::Warning
            )
        }
    }
    else {
        # Нет сигнала — просто выйти
        Write-Host "No wipe signal"
    }
}
catch {
    Write-Host "Error: $($_.Exception.Message)"
}

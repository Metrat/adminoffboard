# AdminOffboard Wipe Agent - One-time check
# Run via Task Scheduler every 5 minutes
# If wipe signal received - deletes Nextcloud files

param(
    [string]$ServerUrl = "https://cloud9.pkflink.ru",
    [string]$Username = "",
    [string]$Password = "",
    [string]$NextcloudFolder = ""
)

if (-not $NextcloudFolder) {
    $NextcloudFolder = "$env:USERPROFILE\Nextcloud"
}

if (-not $Username) {
    $Username = Read-Host "Enter Nextcloud username"
}

if (-not $Password) {
    $securePassword = Read-Host "Enter password for $Username" -AsSecureString
    $Password = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    )
}

Add-Type -AssemblyName System.Windows.Forms

# Проверить API
$ApiEndpoint = "$ServerUrl/index.php/apps/adminoffboard/api/v1/wipe-check"
$headers = @{
    "OCS-APIREQUEST" = "true"
    "Accept" = "application/json"
    "Authorization" = "Basic $([Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${Username}:${Password}")))"
}

try {
    $response = Invoke-RestMethod -Uri $ApiEndpoint -Headers $headers -Method GET -ErrorAction Stop
    
    if ($response.success -and $response.data.wipe_requested) {
        Write-Host "WIPE SIGNAL RECEIVED!"
        
        # Закрыть Nextcloud
        Get-Process | Where-Object { $_.Name -match "nextcloud|owncloud" } | ForEach-Object {
            $_.Kill()
        }
        Start-Sleep -Seconds 2
        
        # Удалить файлы
        if (Test-Path $NextcloudFolder) {
            Write-Host "Deleting: $NextcloudFolder"
            Remove-Item -Path $NextcloudFolder -Recurse -Force -Confirm:$false -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 1
            
            # Если не удалось - удалить содержимое
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
        Write-Host "$(Get-Date -Format 'HH:mm:ss') - No wipe signal"
    }
}
catch {
    Write-Host "Error: $($_.Exception.Message)"
}

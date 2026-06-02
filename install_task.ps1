$TaskName = "FuturamaStaffTracker"
$PHPPath = "php.exe"
$WorkingDir = $PSScriptRoot
$ScriptPath = Join-Path $WorkingDir "cron_sync.php"

# Cleanup
try { Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue } catch {}

# Action
$Action = New-ScheduledTaskAction -Execute $PHPPath -Argument "-f `"$ScriptPath`"" -WorkingDirectory $WorkingDir

# Trigger: Start now and repeat every hour indefinitely
$Trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1)

# Settings
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Register
Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Description "Futurama Staff Tracker"

Write-Host "✅ Task '$TaskName' created successfully!"

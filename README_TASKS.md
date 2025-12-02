Task Scheduler: monitor:run (Windows)
===================================

This file documents how to run the Laravel monitoring command on Windows (Task Scheduler).

Purpose
- Run `php artisan monitor:run` periodically to check monitored sites for defacement.

Log file
- The scheduled task writes stdout/stderr to `storage/logs/monitor.log`.

Exact `schtasks` command used (creates/overwrites task named "LaravelMonitorRun")

Open PowerShell as Administrator, then run:

```powershell
schtasks /Create /SC MINUTE /MO 1 /TN "LaravelMonitorRun" /TR '"C:\xampp\php\php.exe" "C:\laragon\www\DigitalForensik\artisan" monitor:run >> "C:\laragon\www\DigitalForensik\storage\logs\monitor.log" 2>>&1' /F
```

Notes & recommended settings
- The example above uses `C:\xampp\php\php.exe` as the PHP CLI binary. Replace this path if you use a different PHP (e.g., Laragon's PHP).
- If you prefer to run Laravel's scheduler instead, change the command to run `schedule:run`:

```powershell
schtasks /Create /SC MINUTE /MO 1 /TN "LaravelSchedule" /TR '"C:\xampp\php\php.exe" "C:\laragon\www\DigitalForensik\artisan" schedule:run >> "C:\laragon\www\DigitalForensik\storage\logs\schedule.log" 2>>&1' /F
```

- Open Task Scheduler (Task Scheduler Library) and check the task's General tab. You may want to set:
  - "Run whether user is logged on or not" if you want it to run in background.
  - "Run with highest privileges" if your environment requires admin-level permissions for PHP or filesystem writes.

- Verify `storage/logs/monitor.log` is created and being appended to. The task's "Last Result" value should be `0` for successful runs.

Troubleshooting
- If the task does not run:
  - Confirm the PHP binary path is correct by running `C:\xampp\php\php.exe -v`.
  - Check `C:\laragon\www\DigitalForensik\storage\logs\monitor.log` for errors.
  - Ensure the user account in Task Scheduler has permission to run `php.exe` and write into the project folders.

Disable-ScheduledTask -TaskName "LaravelMonitorRun"
Enable-ScheduledTask -TaskName "LaravelMonitorRun"
Stop-ScheduledTask -TaskName "LaravelMonitorRun"
Start-ScheduledTask -TaskName "LaravelMonitorRun"
Get-ScheduledTask -TaskName "LaravelMonitorRun" | Get-ScheduledTaskInfo

' Runs MailDesk's Gmail sync silently (no console window).
' Scheduled every minute via Windows Task Scheduler ("MailDesk Sync").
CreateObject("Wscript.Shell").Run """C:\xampp\php\php.exe"" ""C:\xampp\htdocs\email-setup\artisan"" mail:sync", 0, False

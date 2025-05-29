# Export backups as cron jobs

You can schedule backups by running the plugin shell as cron job.

Please refer to [How to use commands](How%20to%20use%20commands.md) and [Running Shells as Cron Jobs](https://book.cakephp.org/5/en/console-commands/cron-jobs.html#running-shells-as-cron-jobs)

Example.

```bash
0 3 * * 1-5 cd /var/www/mysite && bin/cake database_backup.export -c gzip --timeout 120 # Backup for mysite
```

* the backup runs every day from Monday to Friday, at 3 am;
* the backup will be compressed with gzip;
* the timeout for shell commands is 120 seconds.
